<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Purchase Order Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Once a PO leaves Draft/Pending, its line items are locked here —
// further changes to what was actually received happen in Goods Receipt,
// not by silently editing the PO.
$editable_statuses = ['Draft', 'Pending'];

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $item_ids    = $_POST['item_id'] ?? [];
        $quantities  = $_POST['quantity'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];

        $lines = [];
        for ($i = 0; $i < count($item_ids); $i++) {
            $iid   = (int)($item_ids[$i] ?? 0);
            $qty   = (int)($quantities[$i] ?? 0);
            $price = (float)($unit_prices[$i] ?? 0);
            if ($iid > 0 && $qty > 0) {
                $lines[] = ['item_id' => $iid, 'quantity' => $qty, 'unit_price' => $price];
            }
        }

        if (empty($lines)) {
            $db_error = "Add at least one valid line item (item + quantity greater than 0).";
        } else {
            $total_amount = 0.0;
            foreach ($lines as $l) {
                $total_amount += $l['quantity'] * $l['unit_price'];
            }

            $supplier_id   = $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
            $expected_date = $_POST['expected_date'] !== '' ? $_POST['expected_date'] : null;

            $conn->begin_transaction();
            try {
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO orders (order_number, supplier_id, order_date, expected_date, status, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$stmt) throw new Exception("Could not prepare order insert: " . $conn->error);
                    $stmt->bind_param("sisssd", $_POST['order_number'], $supplier_id, $_POST['order_date'], $expected_date, $_POST['status'], $total_amount);
                    if (!$stmt->execute()) throw new Exception("Failed to create order: " . $stmt->error);
                    $order_id = $stmt->insert_id;
                    $stmt->close();
                } else {
                    $order_id = (int)$_POST['order_id'];

                    $check = $conn->prepare("SELECT status FROM orders WHERE order_id = ?");
                    $check->bind_param("i", $order_id);
                    $check->execute();
                    $cur = $check->get_result()->fetch_assoc();
                    $check->close();
                    if (!$cur) throw new Exception("Order not found.");
                    if (!in_array($cur['status'], $editable_statuses, true)) {
                        throw new Exception("This order can no longer be edited (status: " . $cur['status'] . "). Approved orders are locked — use Goods Receipt instead.");
                    }

                    $stmt = $conn->prepare("UPDATE orders SET order_number=?, supplier_id=?, order_date=?, expected_date=?, status=?, total_amount=? WHERE order_id=?");
                    if (!$stmt) throw new Exception("Could not prepare order update: " . $conn->error);
                    $stmt->bind_param("sisssdi", $_POST['order_number'], $supplier_id, $_POST['order_date'], $expected_date, $_POST['status'], $total_amount, $order_id);
                    if (!$stmt->execute()) throw new Exception("Failed to update order: " . $stmt->error);
                    $stmt->close();

                    // Nothing can have been received yet on a Draft/Pending order,
                    // so wipe-and-reinsert is safe and keeps the edit form simple.
                    $del = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                    if (!$del) throw new Exception("order_items table not found — run schema_po_line_items.sql first.");
                    $del->bind_param("i", $order_id);
                    $del->execute();
                    $del->close();
                }

                $ins = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                if (!$ins) throw new Exception("order_items table not found — run schema_po_line_items.sql first.");
                foreach ($lines as $l) {
                    $ins->bind_param("iiid", $order_id, $l['item_id'], $l['quantity'], $l['unit_price']);
                    if (!$ins->execute()) throw new Exception("Failed to save line item: " . $ins->error);
                }
                $ins->close();

                $conn->commit();
                $flash = $action === 'add' ? "Order created successfully." : "Order updated successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $db_error = $e->getMessage();
            }
        }
    }

    if ($action === 'delete') {
        $order_id = (int)$_POST['order_id'];
        $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $flash = "Order deleted.";
        } else {
            $db_error = "Failed to delete order: " . $stmt->error;
        }
        $stmt->close();
    }
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$orders = [];
$suppliers = [];
$inventory_items = [];
$total_count = 0;

if (!$conn->connect_error) {
    $sql = "SELECT o.order_id, o.order_number, o.supplier_id, o.order_date, o.expected_date,
                   o.status, o.total_amount, s.supplier_name
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.supplier_id";
    if ($search !== '') {
        $sql .= " WHERE o.order_number LIKE ? OR s.supplier_name LIKE ?";
    }
    $sql .= " ORDER BY o.order_date DESC";

    if ($search !== '') {
        $stmt = $conn->prepare($sql);
        $like = "%$search%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $total_count = count($orders);
    } else {
        $db_error = $db_error ?? ("Query failed: " . $conn->error);
    }

    // Attach line items to each order (guarded — table may not exist yet if
    // schema_po_line_items.sql hasn't been run, in which case items stay empty).
    $itemsStmt = $conn->prepare("SELECT oi.order_item_id, oi.item_id, oi.quantity, oi.unit_price, oi.quantity_received,
                                         i.item_name, i.sku
                                  FROM order_items oi
                                  LEFT JOIN inventory_items i ON oi.item_id = i.item_id
                                  WHERE oi.order_id = ?");
    foreach ($orders as &$order) {
        $order['items'] = [];
        if ($itemsStmt) {
            $itemsStmt->bind_param("i", $order['order_id']);
            $itemsStmt->execute();
            $itemsRes = $itemsStmt->get_result();
            while ($itemRow = $itemsRes->fetch_assoc()) {
                $order['items'][] = $itemRow;
            }
        }
    }
    unset($order);
    if ($itemsStmt) $itemsStmt->close();

    $sup_result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    if ($sup_result) {
        while ($row = $sup_result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }

    $item_result = $conn->query("SELECT item_id, sku, item_name, unit_price FROM inventory_items WHERE status != 'Archived' ORDER BY item_name");
    if ($item_result) {
        while ($row = $item_result->fetch_assoc()) {
            $inventory_items[] = $row;
        }
    }
}

function orderStatusMeta($status) {
    switch ($status) {
        case 'Draft':              return ['label' => 'Draft',              'class' => 'status-badge-archived'];
        case 'Pending':            return ['label' => 'Pending',            'class' => 'status-badge-maintenance'];
        case 'Approved':           return ['label' => 'Approved',           'class' => 'status-badge-active'];
        case 'In Transit':         return ['label' => 'In Transit',         'class' => 'status-badge-transit'];
        case 'Partially Received': return ['label' => 'Partially Received', 'class' => 'status-badge-maintenance'];
        case 'Delivered':          return ['label' => 'Delivered',          'class' => 'status-badge-active'];
        case 'Cancelled':          return ['label' => 'Cancelled',          'class' => 'status-badge-critical'];
        default:                   return ['label' => $status,              'class' => 'status-badge-archived'];
    }
}

$editable_statuses_json = json_encode($editable_statuses);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Purchase Order Management — Orders</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        * { box-sizing: border-box !important; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.65rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .status-badge-active      { background: #dcfce7; color: #166534; }
        .status-badge-maintenance { background: #fef3c7; color: #92400e; }
        .status-badge-critical    { background: #fee2e2; color: #991b1b; }
        .status-badge-archived    { background: #e2e8f0; color: #475569; }
        .status-badge-transit     { background: #dbeafe; color: #1e40af; }
        @media (max-width: 767px) {
            .desktop-table-view { display: none !important; }
            .mobile-card-view { display: block !important; }
        }
        @media (min-width: 768px) {
            .desktop-table-view { display: block !important; }
            .mobile-card-view { display: none !important; }
        }
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 50; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 1rem; width: 100%; max-width: 42rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-height: 90vh; overflow-y: auto;
        }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select {
            width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem;
        }
        .item-row { display:grid; grid-template-columns: 1fr 5.5rem 6rem 5.5rem 2rem; gap:0.5rem; align-items:center; margin-bottom:0.5rem; }
        .item-row select, .item-row input { padding:0.4rem 0.5rem; border:1px solid #cbd5e1; border-radius:0.4rem; font-size:0.8rem; width:100%; }
        .item-row .line-total { font-size:0.75rem; font-weight:600; color:#0f172a; text-align:right; }
        .item-row .remove-row { background:none; border:none; color:#dc2626; cursor:pointer; padding:0.25rem; }
        .items-header { display:grid; grid-template-columns: 1fr 5.5rem 6rem 5.5rem 2rem; gap:0.5rem; font-size:0.65rem; font-weight:700; text-transform:uppercase; color:#94a3b8; margin-bottom:0.35rem; }
        .locked-note { font-size: 0.7rem; color: #94a3b8; font-style: italic; }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'header.php'; ?>
        <main class="flex-1 overflow-y-auto bg-surface-dim p-3 sm:p-6 md:p-10 text-on-surface antialiased overflow-x-hidden w-full max-w-full">
            <div class="w-full max-w-7xl mx-auto space-y-6 sm:space-y-8 min-w-0">

                <header class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-outline-variant/60 w-full max-w-full">
                    <div class="space-y-1.5 min-w-0 flex-1">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-primary/10 text-primary text-xs font-semibold uppercase tracking-wider">
                            <span class="material-symbols-outlined text-[16px]">receipt_long</span>
                            Purchase Order Management
                        </div>
                        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-on-surface tracking-tight break-words">Purchase Orders</h1>
                        <p class="text-on-surface-variant text-xs sm:text-sm md:text-base leading-relaxed break-words">
                            Line items from <code>order_items</code>, joined with suppliers and inventory. Total is computed automatically.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
                        <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition-colors shadow-sm inline-flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">add_circle</span>
                            New Order
                        </button>
                    </div>
                </header>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">
                        ✅ <?php echo htmlspecialchars($flash); ?>
                    </div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
                        ⚠️ <?php echo htmlspecialchars($db_error); ?>
                    </div>
                <?php endif; ?>

                <div class="bg-surface-container-lowest rounded-xl sm:rounded-2xl shadow-md border border-outline-variant/60 overflow-hidden w-full max-w-full min-w-0">

                    <div class="bg-surface-container-lowest border-b border-outline-variant/60 p-3 sm:px-6 sm:py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <form method="get" class="relative w-full sm:max-w-sm">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search PO number or supplier..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-outline-variant/80 bg-surface-container-lowest text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"/>
                        </form>
                        <div class="flex items-center gap-2">
                            <?php if ($search !== ''): ?>
                                <a href="orders.php" class="px-3 py-2 rounded-lg border border-outline-variant/80 text-xs sm:text-sm font-medium text-on-surface hover:bg-surface-container transition-colors inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[18px]">close</span>
                                    Clear
                                </a>
                            <?php endif; ?>
                            <a href="orders.php<?php echo $search !== '' ? '?q=' . urlencode($search) : ''; ?>" class="px-3 py-2 rounded-lg border border-outline-variant/80 text-xs sm:text-sm font-medium text-on-surface hover:bg-surface-container transition-colors inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px]">refresh</span>
                                Refresh
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto desktop-table-view">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="bg-surface-container">
                                    <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">PO Number</th>
                                    <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Supplier</th>
                                    <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Items</th>
                                    <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Order Date</th>
                                    <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Total</th>
                                    <th class="px-4 py-3 text-xs font-bold text-on-surface-variant uppercase tracking-wider">Status</th>
                                    <th class="py-3 pl-3 pr-6 text-xs font-bold text-on-surface-variant uppercase tracking-wider">QR</th>
                                    <th class="py-3 pl-3 pr-6 text-xs font-bold text-on-surface-variant uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/40">
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-on-surface-variant text-sm">
                                            <?php echo $search !== '' ? 'No orders match your search.' : 'No purchase orders found yet.'; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): $meta = orderStatusMeta($order['status']); $isEditable = in_array($order['status'], $editable_statuses, true); $itemCount = count($order['items']); ?>
                                        <tr class="hover:bg-surface-container-low transition-colors group">
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <span class="font-mono text-xs font-semibold px-2 py-1 rounded bg-surface-container text-on-surface border border-outline-variant/60">
                                                    <?php echo htmlspecialchars($order['order_number']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3.5 px-4 whitespace-nowrap text-on-surface"><?php echo htmlspecialchars($order['supplier_name'] ?: 'Unassigned'); ?></td>
                                            <td class="py-3.5 px-4 whitespace-nowrap text-on-surface-variant"><?php echo $itemCount; ?> item<?php echo $itemCount === 1 ? '' : 's'; ?></td>
                                            <td class="py-3.5 px-4 whitespace-nowrap text-on-surface-variant"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td class="py-3.5 px-4 whitespace-nowrap font-medium text-on-surface">₱<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                            <td class="py-3.5 px-4 whitespace-nowrap">
                                                <a href="qr_generator.php?order_id=<?php echo (int)$order['order_id']; ?>" target="_blank" class="text-blue-600 hover:underline text-xs">View QR</a>
                                            </td>
                                            <td class="py-3.5 pl-3 pr-6 whitespace-nowrap">
                                                <span class="status-badge <?php echo $meta['class']; ?>"><?php echo htmlspecialchars($meta['label']); ?></span>
                                            </td>
                                            <td class="py-3.5 pl-3 pr-6 whitespace-nowrap text-right">
                                                <?php if ($isEditable): ?>
                                                    <div class="inline-flex items-center gap-0.5">
                                                        <button type="button" title="Edit Order"
                                                            onclick='openEditModal(<?php echo json_encode($order); ?>)'
                                                            class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors">
                                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                                        </button>
                                                        <form method="post" onsubmit="return confirm('Delete this order? This cannot be undone.');" class="inline">
                                                            <input type="hidden" name="action" value="delete"/>
                                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>"/>
                                                            <button type="submit" title="Delete Order" class="p-1.5 rounded-lg text-on-surface-variant hover:text-red-600 hover:bg-red-50 transition-colors">
                                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <button type="button" title="View Order" onclick='openViewModal(<?php echo json_encode($order); ?>)' class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-primary/10 transition-colors">
                                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                                    </button>
                                                    <span class="locked-note block">Locked</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mobile-card-view divide-y divide-outline-variant/40">
                        <?php foreach ($orders as $order): $meta = orderStatusMeta($order['status']); $isEditable = in_array($order['status'], $editable_statuses, true); ?>
                            <div class="p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-xs font-semibold px-2 py-1 rounded bg-surface-container border border-outline-variant/60"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                    <span class="status-badge <?php echo $meta['class']; ?>"><?php echo htmlspecialchars($meta['label']); ?></span>
                                </div>
                                <div class="text-sm font-medium text-on-surface"><?php echo htmlspecialchars($order['supplier_name'] ?: 'Unassigned'); ?></div>
                                <div class="text-xs text-on-surface-variant">
                                    <?php echo count($order['items']); ?> items · <?php echo date('M j, Y', strtotime($order['order_date'])); ?> · ₱<?php echo number_format((float)$order['total_amount'], 2); ?>
                                </div>
                                <div class="text-xs text-blue-600 hover:underline">
                                    <a href="qr_generator.php?order_id=<?php echo (int)$order['order_id']; ?>" target="_blank">View QR</a>
                                </div>
                                <?php if ($isEditable): ?>
                                    <button type="button" onclick='openEditModal(<?php echo json_encode($order); ?>)' class="text-xs font-medium text-primary">Edit</button>
                                <?php else: ?>
                                    <button type="button" onclick='openViewModal(<?php echo json_encode($order); ?>)' class="text-xs font-medium text-primary">View</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-surface-container-lowest p-3 sm:px-6 sm:py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-outline-variant/60">
                        <div class="text-xs text-on-surface-variant">
                            Showing <span class="font-semibold text-on-surface"><?php echo $total_count > 0 ? 1 : 0; ?></span> – <span class="font-semibold text-on-surface"><?php echo $total_count; ?></span> of <span class="font-semibold text-on-surface"><?php echo $total_count; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="order-modal" class="modal-overlay" style="display:none;">
                <div class="modal-box">
                    <div class="flex items-center justify-between mb-4">
                        <h3 id="modal-title" class="text-lg font-bold text-on-surface">New Purchase Order</h3>
                        <button type="button" onclick="closeModal()" class="p-1.5 rounded-full hover:bg-surface-container">
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>
                    <form method="post" class="space-y-3" id="order-form">
                        <input type="hidden" name="action" id="form-action" value="add"/>
                        <input type="hidden" name="order_id" id="form-order-id" value=""/>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="form-field col-span-2">
                                <label>PO Number</label>
                                <input type="text" name="order_number" id="f-order-number" placeholder="PO-2026-001" required/>
                            </div>
                            <div class="form-field col-span-2">
                                <label>Supplier</label>
                                <select name="supplier_id" id="f-supplier">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?php echo (int)$s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label>Order Date</label>
                                <input type="date" name="order_date" id="f-order-date" required/>
                            </div>
                            <div class="form-field">
                                <label>Expected Date</label>
                                <input type="date" name="expected_date" id="f-expected-date"/>
                            </div>
                            <div class="form-field col-span-2">
                                <label>Status</label>
                                <select name="status" id="f-status">
                                    <option value="Draft">Draft</option>
                                    <option value="Pending">Pending</option>
                                </select>
                                <p class="text-[10px] text-on-surface-variant mt-1">Approval happens in PO Approvals once the order is ready.</p>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-outline-variant/40">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-on-surface uppercase tracking-wide">Line Items</label>
                                <button type="button" onclick="addItemRow()" class="text-xs font-semibold text-primary inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[16px]">add</span> Add Item
                                </button>
                            </div>
                            <div class="items-header">
                                <span>Item</span><span>Qty</span><span>Unit Price</span><span>Line Total</span><span></span>
                            </div>
                            <div id="item-rows"></div>
                            <div class="flex justify-end pt-2 text-sm font-bold text-on-surface">
                                Total: ₱<span id="order-total-display">0.00</span>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-3 border-t border-outline-variant/30">
                            <button type="button" onclick="closeModal()" class="px-4 py-2 rounded-lg border border-outline-variant/60 text-sm font-medium">Cancel</button>
                            <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-on-primary text-sm font-semibold">Save Order</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="view-modal" class="modal-overlay" style="display:none;">
                <div class="modal-box">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-on-surface">Order Details — <span id="view-po-number"></span></h3>
                        <button type="button" onclick="closeViewModal()" class="p-1.5 rounded-full hover:bg-surface-container">
                            <span class="material-symbols-outlined text-xl">close</span>
                        </button>
                    </div>
                    <p class="text-xs text-on-surface-variant mb-3">This order is locked from editing. Line items are read-only.</p>
                    <div class="items-header"><span>Item</span><span>Qty</span><span>Unit Price</span><span>Received</span><span></span></div>
                    <div id="view-item-rows" class="text-sm"></div>
                    <div class="flex justify-end pt-3 border-t border-outline-variant/30 mt-2 text-sm font-bold text-on-surface">
                        Total: ₱<span id="view-total-display">0.00</span>
                    </div>
                </div>
            </div>

            <script>
                const INVENTORY_ITEMS = <?php echo json_encode($inventory_items); ?>;

                function buildItemSelect(selectedItemId) {
                    let opts = '<option value="">Select item...</option>';
                    INVENTORY_ITEMS.forEach(function(it) {
                        const sel = (String(it.item_id) === String(selectedItemId)) ? 'selected' : '';
                        opts += `<option value="${it.item_id}" data-price="${it.unit_price}" ${sel}>${it.item_name} (${it.sku})</option>`;
                    });
                    return opts;
                }

                function addItemRow(prefill) {
                    prefill = prefill || {};
                    const container = document.getElementById('item-rows');
                    const row = document.createElement('div');
                    row.className = 'item-row';
                    row.innerHTML = `
                        <select name="item_id[]" onchange="onItemSelect(this)">${buildItemSelect(prefill.item_id)}</select>
                        <input type="number" name="quantity[]" min="1" value="${prefill.quantity || ''}" oninput="updateRowTotal(this)" placeholder="Qty"/>
                        <input type="number" step="0.01" name="unit_price[]" min="0" value="${prefill.unit_price || ''}" oninput="updateRowTotal(this)" placeholder="0.00"/>
                        <span class="line-total">0.00</span>
                        <button type="button" class="remove-row" onclick="removeItemRow(this)">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    `;
                    container.appendChild(row);
                    updateRowTotal(row.querySelector('input[name="quantity[]"]'));
                }

                function onItemSelect(select) {
                    const row = select.closest('.item-row');
                    const priceInput = row.querySelector('input[name="unit_price[]"]');
                    const opt = select.options[select.selectedIndex];
                    const price = opt ? opt.getAttribute('data-price') : null;
                    if (price !== null && price !== '') {
                        priceInput.value = parseFloat(price).toFixed(2);
                    }
                    updateRowTotal(select);
                }

                function updateRowTotal(el) {
                    const row = el.closest('.item-row');
                    const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
                    const price = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
                    row.querySelector('.line-total').textContent = (qty * price).toFixed(2);
                    updateOrderTotal();
                }

                function removeItemRow(btn) {
                    btn.closest('.item-row').remove();
                    updateOrderTotal();
                }

                function updateOrderTotal() {
                    let total = 0;
                    document.querySelectorAll('#item-rows .item-row').forEach(function(row) {
                        const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
                        const price = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
                        total += qty * price;
                    });
                    document.getElementById('order-total-display').textContent = total.toFixed(2);
                }

                function resetItemRows() {
                    document.getElementById('item-rows').innerHTML = '';
                }

                function openAddModal() {
                    document.getElementById('modal-title').textContent = 'New Purchase Order';
                    document.getElementById('form-action').value = 'add';
                    document.getElementById('form-order-id').value = '';
                    document.getElementById('f-order-number').value = '';
                    document.getElementById('f-supplier').value = '';
                    document.getElementById('f-order-date').value = '';
                    document.getElementById('f-expected-date').value = '';
                    document.getElementById('f-status').value = 'Draft';
                    resetItemRows();
                    addItemRow();
                    document.getElementById('order-modal').style.display = 'flex';
                }

                function openEditModal(order) {
                    document.getElementById('modal-title').textContent = 'Edit Purchase Order';
                    document.getElementById('form-action').value = 'edit';
                    document.getElementById('form-order-id').value = order.order_id;
                    document.getElementById('f-order-number').value = order.order_number;
                    document.getElementById('f-supplier').value = order.supplier_id || '';
                    document.getElementById('f-order-date').value = order.order_date;
                    document.getElementById('f-expected-date').value = order.expected_date || '';
                    document.getElementById('f-status').value = order.status;
                    resetItemRows();
                    if (order.items && order.items.length > 0) {
                        order.items.forEach(function(it) {
                            addItemRow({ item_id: it.item_id, quantity: it.quantity, unit_price: it.unit_price });
                        });
                    } else {
                        addItemRow();
                    }
                    document.getElementById('order-modal').style.display = 'flex';
                }

                function closeModal() {
                    document.getElementById('order-modal').style.display = 'none';
                }

                function openViewModal(order) {
                    document.getElementById('view-po-number').textContent = order.order_number;
                    const container = document.getElementById('view-item-rows');
                    container.innerHTML = '';
                    let total = 0;
                    (order.items || []).forEach(function(it) {
                        total += it.quantity * it.unit_price;
                        const row = document.createElement('div');
                        row.className = 'item-row';
                        row.innerHTML = `
                            <span>${it.item_name || ('Item #' + it.item_id)}</span>
                            <span>${it.quantity}</span>
                            <span>₱${parseFloat(it.unit_price).toFixed(2)}</span>
                            <span>${it.quantity_received || 0}</span>
                            <span>₱${(it.quantity_received || 0) * parseFloat(it.unit_price).toFixed(2)}</span>
                        `;
                        container.appendChild(row);
                    });
                    document.getElementById('view-total-display').textContent = total.toFixed(2);
                    document.getElementById('view-modal').style.display = 'flex';
                }

                function closeViewModal() {
                    document.getElementById('view-modal').style.display = 'none';
                }
            </script>
        </main>
    </div>
</body>
</html>