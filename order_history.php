<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'pom_connection.php';

$section_title = "Purchase Order Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;

// Read-only view of finished orders — the "Delivered" / "Cancelled" endpoints
// of the workflow that orders.php (Active Purchase Orders) intentionally excludes.
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$validStatuses = ['Delivered', 'Cancelled'];
if (!in_array($filterStatus, $validStatuses, true)) $filterStatus = '';

$orders = [];
$totalDeliveredValue = 0;
$deliveredCount = 0;
$cancelledCount = 0;

if (!$conn->connect_error) {
    $conditions = ["o.status IN ('Delivered', 'Cancelled')"];
    $types = '';
    $params = [];

    if ($search !== '') {
        $conditions[] = "(o.order_number LIKE ? OR s.supplier_name LIKE ?)";
        $like = "%$search%";
        $types .= 'ss';
        array_push($params, $like, $like);
    }
    if ($filterStatus !== '') {
        $conditions[] = "o.status = ?";
        $types .= 's';
        $params[] = $filterStatus;
    }

    $sql = "SELECT o.order_id, o.order_number, o.supplier_id, o.order_date, o.expected_date,
                   o.status, o.total_amount, s.supplier_name
            FROM orders o
            LEFT JOIN suppliers s ON o.supplier_id = s.supplier_id
            WHERE " . implode(" AND ", $conditions) . "
            ORDER BY o.order_date DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
            if ($row['status'] === 'Delivered') {
                $deliveredCount++;
                $totalDeliveredValue += (float)$row['total_amount'];
            } else {
                $cancelledCount++;
            }
        }
    } else {
        $db_error = "Query failed: " . $conn->error;
    }

    // Attach line items per order, same pattern as orders.php, so a finished
    // order's history is still fully inspectable (not just the header row).
    $itemsStmt = $conn->prepare("SELECT oi.quantity, oi.unit_price, oi.quantity_received,
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
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Order History</title>
    <link href="app.css?v=<?php echo @filemtime('app.css'); ?>" rel="stylesheet"/>
    <script src="app.js?v=<?php echo @filemtime('app.js'); ?>" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Order History</h1>
                    <p class="text-slate-500 text-sm mt-1">Delivered and cancelled purchase orders — the completed endpoints of the PO workflow. Read-only.</p>
                </div>

                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl p-4 border border-slate-200">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Delivered Orders</p>
                        <p class="text-xl font-bold text-emerald-600 mt-1"><?php echo $deliveredCount; ?></p>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-slate-200">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Cancelled Orders</p>
                        <p class="text-xl font-bold text-rose-600 mt-1"><?php echo $cancelledCount; ?></p>
                    </div>
                    <div class="bg-white rounded-xl p-4 border border-slate-200">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Delivered Value</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">₱<?php echo number_format($totalDeliveredValue, 2); ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <form method="get" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                            <div class="relative w-full sm:max-w-sm">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search PO # or supplier..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                            </div>
                            <select name="status" class="py-2 px-3 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none" onchange="this.form.submit()">
                                <option value="">All Finished Orders</option>
                                <option value="Delivered" <?php echo $filterStatus === 'Delivered' ? 'selected' : ''; ?>>Delivered Only</option>
                                <option value="Cancelled" <?php echo $filterStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled Only</option>
                            </select>
                            <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold text-slate-600 transition-colors">Filter</button>
                            <?php if ($search !== '' || $filterStatus !== ''): ?>
                                <a href="order_history.php" class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">PO Number</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Order Date</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Items</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No finished orders found yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors align-top">
                                            <td class="px-6 py-4 font-mono font-semibold text-slate-900"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($order['supplier_name'] ?: 'Unassigned'); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td class="px-6 py-4 font-semibold text-slate-900">₱<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                            <td class="px-6 py-4">
                                                <?php if ($order['status'] === 'Delivered'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200">Delivered</span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold text-rose-700 bg-rose-50 border border-rose-200">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-slate-500">
                                                <?php if (empty($order['items'])): ?>
                                                    —
                                                <?php else: ?>
                                                    <details>
                                                        <summary class="cursor-pointer text-primary font-semibold"><?php echo count($order['items']); ?> line item<?php echo count($order['items']) > 1 ? 's' : ''; ?></summary>
                                                        <ul class="mt-2 space-y-1">
                                                            <?php foreach ($order['items'] as $item): ?>
                                                                <li class="text-slate-500">
                                                                    <?php echo htmlspecialchars($item['item_name'] ?? $item['sku'] ?? 'Unknown item'); ?>
                                                                    — <?php echo (int)$item['quantity']; ?> @ ₱<?php echo number_format((float)$item['unit_price'], 2); ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </details>
                                                <?php endif; ?>
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
</body>
</html>