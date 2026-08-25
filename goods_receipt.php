<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db_connection.php';

$section_title = "Purchase Order Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

$receivable_statuses = ['Approved', 'In Transit', 'Partially Received'];

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'receive') {
        $order_id      = (int)($_POST['order_id'] ?? 0);
        $received_by   = trim($_POST['received_by'] ?? '');
        $notes         = trim($_POST['notes'] ?? '');
        $order_item_ids = $_POST['order_item_id'] ?? [];
        $receive_qtys   = $_POST['receive_qty'] ?? [];

        if ($order_id <= 0 || $received_by === '') {
            $db_error = "Receiver name is required.";
        } else {
            $conn->begin_transaction();
            try {
                $check = $conn->prepare("SELECT order_id, order_number, status FROM orders WHERE order_id = ? FOR UPDATE");
                if (!$check) throw new Exception("order_items schema not found — run schema_po_line_items.sql first.");
                $check->bind_param("i", $order_id);
                $check->execute();
                $order = $check->get_result()->fetch_assoc();
                $check->close();

                if (!$order || !in_array($order['status'], $receivable_statuses, true)) {
                    throw new Exception("This order is not currently awaiting receipt.");
                }

                $anyReceived = false;

                $recStmt = $conn->prepare("INSERT INTO goods_receipts (order_id, received_by, notes) VALUES (?, ?, ?)");
                if (!$recStmt) throw new Exception("goods_receipts table not found — run schema_po_line_items.sql first.");
                $recStmt->bind_param("iss", $order_id, $received_by, $notes);
                $recStmt->execute();
                $receipt_id = $recStmt->insert_id;
                $recStmt->close();

                $lineStmt = $conn->prepare(
                    "SELECT order_item_id, item_id, quantity, quantity_received
                     FROM order_items WHERE order_item_id = ? AND order_id = ? FOR UPDATE"
                );
                $riStmt = $conn->prepare("INSERT INTO goods_receipt_items (receipt_id, order_item_id, quantity_received) VALUES (?, ?, ?)");
                $updOi = $conn->prepare("UPDATE order_items SET quantity_received = quantity_received + ? WHERE order_item_id = ?");
                $updInv = $conn->prepare("UPDATE inventory_items SET quantity = quantity + ? WHERE item_id = ?");

                for ($i = 0; $i < count($order_item_ids); $i++) {
                    $oiId = (int)$order_item_ids[$i];
                    $qty  = (int)($receive_qtys[$i] ?? 0);
                    if ($oiId <= 0 || $qty <= 0) continue;

                    $lineStmt->bind_param("ii", $oiId, $order_id);
                    $lineStmt->execute();
                    $line = $lineStmt->get_result()->fetch_assoc();
                    if (!$line) continue;

                    $remaining = (int)$line['quantity'] - (int)$line['quantity_received'];
                    if ($qty > $remaining) {
                        $qty = $remaining; // clamp — never over-receive
                    }
                    if ($qty <= 0) continue;

                    $riStmt->bind_param("iii", $receipt_id, $oiId, $qty);
                    $riStmt->execute();

                    $updOi->bind_param("ii", $qty, $oiId);
                    $updOi->execute();

                    $updInv->bind_param("ii", $qty, $line['item_id']);
                    $updInv->execute();

                    $anyReceived = true;
                }

                $lineStmt->close();
                $riStmt->close();
                $updOi->close();
                $updInv->close();

                if (!$anyReceived) {
                    throw new Exception("Enter a quantity greater than 0 for at least one item.");
                }

                // Recompute order status from the line items' totals.
                $sumStmt = $conn->prepare("SELECT SUM(quantity) AS total_qty, SUM(quantity_received) AS total_received FROM order_items WHERE order_id = ?");
                $sumStmt->bind_param("i", $order_id);
                $sumStmt->execute();
                $sums = $sumStmt->get_result()->fetch_assoc();
                $sumStmt->close();

                $newStatus = ((int)$sums['total_received'] >= (int)$sums['total_qty'])
                    ? 'Delivered'
                    : 'Partially Received';

                $updOrder = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $updOrder->bind_param("si", $newStatus, $order_id);
                $updOrder->execute();
                $updOrder->close();

                $msg = "PO " . $order['order_number'] . " — receipt logged by " . $received_by . " (" . $newStatus . ")"
                     . ($notes !== '' ? " — Note: " . $notes : '');
                $safe = $conn->real_escape_string($msg);
                $safeStatus = $conn->real_escape_string($newStatus);
                $statusClass = $newStatus === 'Delivered' ? 'status-pill-success' : 'status-pill-info';
                $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$safe', '$safeStatus', '$statusClass')");

                $conn->commit();
                $flash = "PO " . $order['order_number'] . " updated to " . $newStatus . ".";
            } catch (Exception $e) {
                $conn->rollback();
                $db_error = $e->getMessage();
            }
        }
    }
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$orders = [];
if (!$conn->connect_error) {
    $placeholders = implode(',', array_fill(0, count($receivable_statuses), '?'));
    $sql = "SELECT o.order_id, o.order_number, o.supplier_id, o.order_date, o.expected_date,
                   o.status, o.total_amount, s.supplier_name
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.supplier_id
            WHERE o.status IN ($placeholders)";
    $types = str_repeat('s', count($receivable_statuses));
    $params = $receivable_statuses;

    if ($search !== '') {
        $sql .= " AND (o.order_number LIKE ? OR s.supplier_name LIKE ?)";
        $types .= 'ss';
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= " ORDER BY o.expected_date ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        $stmt->close();
    } else {
        $db_error = $db_error ?? ("Query failed: " . $conn->error);
    }

    // Attach line items (guarded — table may not exist yet).
    $itemsStmt = $conn->prepare(
        "SELECT oi.order_item_id, oi.item_id, oi.quantity, oi.unit_price, oi.quantity_received,
                i.item_name, i.sku
         FROM order_items oi
         LEFT JOIN inventory_items i ON oi.item_id = i.item_id
         WHERE oi.order_id = ?"
    );
    foreach ($orders as &$order) {
        $order['items'] = [];
        if ($itemsStmt) {
            $itemsStmt->bind_param("i", $order['order_id']);
            $itemsStmt->execute();
            $res = $itemsStmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $row['remaining'] = (int)$row['quantity'] - (int)$row['quantity_received'];
                $order['items'][] = $row;
            }
        } else {
            $db_error = $db_error ?? "order_items table not found — run schema_po_line_items.sql first.";
        }
    }
    unset($order);
    if ($itemsStmt) $itemsStmt->close();
} else {
    $db_error = "Database offline.";
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Goods Receipt</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        .status-badge { display:inline-block; padding:0.2rem 0.65rem; border-radius:9999px; font-size:0.7rem; font-weight:600; }
        .status-badge-active  { background:#dcfce7; color:#166534; }
        .status-badge-transit { background:#dbeafe; color:#1e40af; }
        .status-badge-partial { background:#fef3c7; color:#92400e; }
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 100; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 1rem; width: 100%; max-width: 34rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-height: 90vh; overflow-y: auto;
        }
        .form-field { margin-bottom: 1rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field textarea {
            width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem;
        }
        .receive-row { display:grid; grid-template-columns: 1fr 4.5rem 5.5rem 5.5rem; gap:0.5rem; align-items:center; margin-bottom:0.4rem; font-size:0.8rem; }
        .receive-row input { padding:0.35rem 0.5rem; border:1px solid #cbd5e1; border-radius:0.4rem; width:100%; font-size:0.8rem; }
        .receive-header { display:grid; grid-template-columns: 1fr 4.5rem 5.5rem 5.5rem; gap:0.5rem; font-size:0.65rem; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:0.35rem; }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">ISMERS Purchase Order Cluster</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Goods Receipt</h1>
                        <p class="text-slate-500 text-sm mt-1">Record what arrived against each purchase order, per line item. Updates inventory automatically.</p>
                    </div>
                    <form method="get" class="relative w-full sm:max-w-xs">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search PO # or supplier..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                    </form>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900">Awaiting Receipt</h2>
                        <span class="text-xs text-slate-400"><?php echo count($orders); ?> order<?php echo count($orders) === 1 ? '' : 's'; ?></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">PO Number</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Expected Date</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Items</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No orders awaiting receipt.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): $badgeClass = $order['status'] === 'In Transit' ? 'status-badge-transit' : ($order['status'] === 'Partially Received' ? 'status-badge-partial' : 'status-badge-active'); ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-mono font-semibold text-slate-900"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($order['supplier_name'] ?: 'Unassigned'); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo $order['expected_date'] ? date('M j, Y', strtotime($order['expected_date'])) : '—'; ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo count($order['items']); ?> line<?php echo count($order['items']) === 1 ? '' : 's'; ?></td>
                                            <td class="px-6 py-4"><span class="status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($order['status']); ?></span></td>
                                            <td class="px-6 py-4 text-right">
                                                <button type="button"
                                                    onclick='openReceiveModal(<?php echo json_encode($order); ?>)'
                                                    class="px-3 py-1.5 bg-primary text-white rounded-lg text-[11px] font-bold inline-flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-[14px]">inventory</span> Receive Items
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="receive-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Receive Items — <span id="modal-po-label"></span></h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" id="receive-form">
                <input type="hidden" name="action" value="receive"/>
                <input type="hidden" name="order_id" id="modal-order-id"/>

                <div class="receive-header"><span>Item</span><span>Ordered</span><span>Remaining</span><span>Receive Now</span></div>
                <div id="receive-rows"></div>

                <div class="form-field mt-3">
                    <label>Received By</label>
                    <input type="text" name="received_by" placeholder="Full name of receiver" required/>
                </div>
                <div class="form-field">
                    <label>Notes (optional)</label>
                    <textarea name="notes" rows="2" placeholder="e.g. 2 units short, box damaged on arrival..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Confirm Receipt</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReceiveModal(order) {
            document.getElementById('modal-order-id').value = order.order_id;
            document.getElementById('modal-po-label').textContent = order.order_number;

            const container = document.getElementById('receive-rows');
            container.innerHTML = '';

            (order.items || []).forEach(function(it) {
                if (it.remaining <= 0) return; // fully received line, nothing to show
                const row = document.createElement('div');
                row.className = 'receive-row';
                row.innerHTML = `
                    <span>${it.item_name || ('Item #' + it.item_id)}</span>
                    <span>${it.quantity}</span>
                    <span>${it.remaining}</span>
                    <input type="hidden" name="order_item_id[]" value="${it.order_item_id}"/>
                    <input type="number" name="receive_qty[]" min="0" max="${it.remaining}" value="${it.remaining}"/>
                `;
                container.appendChild(row);
            });

            if (container.innerHTML === '') {
                container.innerHTML = '<p class="text-xs text-slate-400">All line items on this order have already been fully received.</p>';
            }

            document.getElementById('receive-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('receive-modal').style.display = 'none';
        }
    </script>
</body>
</html>