<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Inventory Management Dashboard";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;

// Get inventory statistics
$stats = [];
$low_stock_items = [];
$out_of_stock_items = [];
$category_stats = [];
$recent_transactions = [];

if (!$conn->connect_error) {
    // Total inventory value and count
    $total_result = $conn->query("SELECT 
        COUNT(*) as total_items,
        SUM(quantity) as total_quantity,
        SUM(quantity * unit_price) as total_value
        FROM inventory_items WHERE status != 'Archived'");
    if ($total_result) {
        $stats = $total_result->fetch_assoc();
    }
    
    // Low stock items
    $low_stock = $conn->query("SELECT item_id, sku, item_name, quantity, reorder_level, unit 
        FROM inventory_items 
        WHERE quantity <= reorder_level AND quantity > 0 AND status != 'Archived'
        ORDER BY quantity ASC LIMIT 10");
    if ($low_stock) {
        while ($row = $low_stock->fetch_assoc()) {
            $low_stock_items[] = $row;
        }
    }
    
    // Out of stock items
    $out_stock = $conn->query("SELECT item_id, sku, item_name, unit 
        FROM inventory_items 
        WHERE quantity = 0 AND status != 'Archived'
        ORDER BY item_name ASC LIMIT 10");
    if ($out_stock) {
        while ($row = $out_stock->fetch_assoc()) {
            $out_of_stock_items[] = $row;
        }
    }
    
    // Category breakdown
    $cat_result = $conn->query("SELECT category, COUNT(*) as item_count, SUM(quantity) as total_qty, SUM(quantity * unit_price) as category_value
        FROM inventory_items 
        WHERE category IS NOT NULL AND category != '' AND status != 'Archived'
        GROUP BY category
        ORDER BY category_value DESC");
    if ($cat_result) {
        while ($row = $cat_result->fetch_assoc()) {
            $category_stats[] = $row;
        }
    }
    
    // Check if inventory_transactions table exists for recent activity
    $trans_table_exists = false;
    $check_trans = $conn->query("SHOW TABLES LIKE 'inventory_transactions'");
    if ($check_trans && $check_trans->num_rows > 0) {
        $trans_table_exists = true;
    }
    $check_trans->free();
    
    if ($trans_table_exists) {
        $trans_result = $conn->query("SELECT it.*, ii.item_name, ii.sku 
            FROM inventory_transactions it 
            JOIN inventory_items ii ON it.item_id = ii.item_id 
            ORDER BY it.created_at DESC LIMIT 10");
        if ($trans_result) {
            while ($row = $trans_result->fetch_assoc()) {
                $recent_transactions[] = $row;
            }
        }
    }
} else {
    $db_error = "Database connection offline.";
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Console</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        .stat-card {
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
        }
        .alert-item {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            border-left: 3px solid;
        }
        .alert-warning {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        .alert-critical {
            background: #fee2e2;
            border-color: #ef4444;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Inventory Management System</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Inventory Dashboard</h1>
                        <p class="text-slate-500 text-sm mt-1">Real-time overview of stock levels, values, and inventory status.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="inventory.php" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">inventory_2</span> Manage Inventory
                        </a>
                        <a href="qr_scanner.php" class="px-4 py-2 border border-slate-200 rounded-lg text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">qr_code_scanner</span> QR Scanner
                        </a>
                    </div>
                </div>

                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="stat-label">Total Products</p>
                                <p class="stat-value"><?php echo number_format($stats['total_items'] ?? 0); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="material-symbols-outlined text-blue-600 text-2xl">inventory</span>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="stat-label">Total Quantity</p>
                                <p class="stat-value"><?php echo number_format($stats['total_quantity'] ?? 0); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center">
                                <span class="material-symbols-outlined text-emerald-600 text-2xl">stacks</span>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="stat-label">Total Value</p>
                                <p class="stat-value">₱<?php echo number_format($stats['total_value'] ?? 0, 2); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center">
                                <span class="material-symbols-outlined text-purple-600 text-2xl">payments</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Low Stock Alerts -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                            <h2 class="text-sm font-bold text-slate-900">Low Stock Alerts</h2>
                            <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2 py-1 rounded-full"><?php echo count($low_stock_items); ?> items</span>
                        </div>
                        <div class="p-4">
                            <?php if (empty($low_stock_items)): ?>
                                <p class="text-slate-500 text-sm text-center py-4">No items at low stock levels.</p>
                            <?php else: ?>
                                <?php foreach ($low_stock_items as $item): ?>
                                    <div class="alert-item alert-warning">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                                <p class="text-xs text-slate-500">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-amber-700"><?php echo $item['quantity']; ?> / <?php echo $item['reorder_level']; ?> <?php echo htmlspecialchars($item['unit']); ?></p>
                                                <p class="text-xs text-slate-500">Reorder point</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Out of Stock Alerts -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                            <h2 class="text-sm font-bold text-slate-900">Out of Stock</h2>
                            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded-full"><?php echo count($out_of_stock_items); ?> items</span>
                        </div>
                        <div class="p-4">
                            <?php if (empty($out_of_stock_items)): ?>
                                <p class="text-slate-500 text-sm text-center py-4">No items out of stock.</p>
                            <?php else: ?>
                                <?php foreach ($out_of_stock_items as $item): ?>
                                    <div class="alert-item alert-critical">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($item['item_name']); ?></p>
                                                <p class="text-xs text-slate-500">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-red-700">0 <?php echo htmlspecialchars($item['unit']); ?></p>
                                                <p class="text-xs text-slate-500">Stock depleted</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-sm font-bold text-slate-900">Category Breakdown</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Items</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Total Quantity</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Category Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($category_stats)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-slate-400">No category data available.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($category_stats as $cat): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($cat['category']); ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?php echo number_format($cat['item_count']); ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?php echo number_format($cat['total_qty']); ?></td>
                                            <td class="px-4 py-3 font-semibold text-slate-900">₱<?php echo number_format($cat['category_value'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <?php if (!empty($recent_transactions)): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-sm font-bold text-slate-900">Recent Inventory Transactions</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Timestamp</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Item</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Quantity Change</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($recent_transactions as $trans): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y g:i A', strtotime($trans['created_at'])); ?></td>
                                            <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($trans['item_name']); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border 
                                                    <?php echo $trans['transaction_type'] === 'Stock In' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 
                                                        ($trans['transaction_type'] === 'Stock Out' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-blue-100 text-blue-800 border-blue-200'); ?>">
                                                    <?php echo htmlspecialchars($trans['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 font-semibold <?php echo $trans['quantity_change'] > 0 ? 'text-emerald-600' : 'text-red-600'; ?>">
                                                <?php echo ($trans['quantity_change'] > 0 ? '+' : '') . number_format($trans['quantity_change']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>