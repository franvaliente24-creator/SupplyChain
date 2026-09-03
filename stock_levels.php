<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'ims_connection.php';
$section_title = "Inventory Management System (IMS)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';
$db_error = null;
$flash = null;

$stock_records = [];
$total_inventory_value = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;
$filter_status = $_GET['status'] ?? '';

if (!$conn->connect_error) {
    $sql = "SELECT im.item_id, im.sku, im.item_name, im.category, im.unit, im.unit_cost, im.reorder_level, im.safety_stock, im.status,
                   si.stock_id, COALESCE(si.quantity_on_hand, 0) AS on_hand, COALESCE(si.quantity_reserved, 0) AS reserved,
                   COALESCE(si.warehouse_location, 'Main Hub') AS warehouse_location, si.last_restocked_at
            FROM item_master im
            JOIN stock_inventory si ON im.item_id = si.item_id
            WHERE 1=1";
    
    if ($filter_status === 'low') {
        $sql .= " AND si.quantity_on_hand > 0 AND si.quantity_on_hand <= im.reorder_level";
    } elseif ($filter_status === 'out') {
        $sql .= " AND si.quantity_on_hand <= 0";
    }

    $sql .= " ORDER BY si.quantity_on_hand ASC, im.item_name ASC";
    $res = $conn->query($sql);

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $total_inventory_value += ((int)$row['on_hand'] * (float)$row['unit_cost']);
            if ((int)$row['on_hand'] <= 0) $out_of_stock_count++;
            elseif ((int)$row['on_hand'] <= (int)$row['reorder_level']) $low_stock_count++;
            $stock_records[] = $row;
        }
    }
} else {
    $db_error = "Database offline.";
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Stock Levels</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Inventory Management System</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-600 text-sm font-medium">Stock Levels</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Stock Levels & Valuation</h1>
                        <p class="text-slate-500 text-sm mt-1">Real-time balances, available vs. reserved units, and automated reorder alerts.</p>
                    </div>
                </div>

                <!-- KPI Overview Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Stock Valuation</p>
                        <h3 class="text-3xl font-bold text-slate-900 mt-1">&#8369;<?php echo number_format($total_inventory_value, 2); ?></h3>
                    </div>
                    <a href="?status=low" class="bg-white rounded-2xl p-5 shadow-sm border border-amber-200 hover:border-amber-300 transition block">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wider text-amber-700">Low Stock Alerts</p>
                            <span class="material-symbols-outlined text-amber-600">warning</span>
                        </div>
                        <h3 class="text-3xl font-bold text-amber-900 mt-1"><?php echo $low_stock_count; ?> <span class="text-xs font-normal text-amber-600">SKUs</span></h3>
                    </a>
                    <a href="?status=out" class="bg-white rounded-2xl p-5 shadow-sm border border-rose-200 hover:border-rose-300 transition block">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold uppercase tracking-wider text-rose-700">Out of Stock</p>
                            <span class="material-symbols-outlined text-rose-600">error</span>
                        </div>
                        <h3 class="text-3xl font-bold text-rose-900 mt-1"><?php echo $out_of_stock_count; ?> <span class="text-xs font-normal text-rose-600">SKUs</span></h3>
                    </a>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200 flex justify-between items-center">
                        <h2 class="text-sm font-bold text-slate-800">Warehouse Balances</h2>
                        <a href="stock_levels.php" class="text-xs text-primary font-semibold hover:underline">Reset View</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">SKU</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Storage Bay</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">On Hand</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Reserved</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Available</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Total Value</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Stock Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($stock_records)): ?>
                                    <tr><td colspan="8" class="px-6 py-10 text-center text-slate-400">No stock records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($stock_records as $s): 
                                        $avail = (int)$s['on_hand'] - (int)$s['reserved'];
                                        $val = (int)$s['on_hand'] * (float)$s['unit_cost'];
                                        $isLow = ((int)$s['on_hand'] <= (int)$s['reorder_level']);
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-mono font-bold text-primary"><?php echo htmlspecialchars($s['sku']); ?></td>
                                            <td class="px-4 py-3 font-semibold text-slate-900"><?php echo htmlspecialchars($s['item_name']); ?></td>
                                            <td class="px-4 py-3 text-slate-600 font-mono text-[11px]"><?php echo htmlspecialchars($s['warehouse_location']); ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900"><?php echo number_format($s['on_hand']); ?></td>
                                            <td class="px-4 py-3 text-right font-mono text-slate-500"><?php echo number_format($s['reserved']); ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-bold <?php echo $avail <= 0 ? 'text-rose-600' : 'text-emerald-700'; ?>"><?php echo number_format($avail); ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-semibold text-slate-800">&#8369;<?php echo number_format($val, 2); ?></td>
                                            <td class="px-4 py-3">
                                                <?php if ((int)$s['on_hand'] <= 0): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Out of Stock</span>
                                                <?php elseif ($isLow): ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Reorder Reqd</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">Optimal</span>
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