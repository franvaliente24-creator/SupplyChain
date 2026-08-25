<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connection.php';

$section_title = "Supplier / Vendor Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$vendors = [];

if (!$conn->connect_error) {
    $sql = "SELECT
                s.supplier_id,
                s.supplier_name,
                s.rating,
                s.is_active,
                COUNT(DISTINCT CASE WHEN o.status != 'Cancelled' THEN o.order_id END) AS total_orders,
                COALESCE(SUM(CASE WHEN o.status != 'Cancelled' THEN o.total_amount END), 0) AS total_value,
                COUNT(DISTINCT CASE WHEN m.delivery_status = 'Delivered' THEN m.manifest_id END) AS delivered_count,
                COUNT(DISTINCT CASE WHEN m.delivery_status = 'Delayed' THEN m.manifest_id END) AS delayed_count,
                COUNT(DISTINCT CASE WHEN m.delivery_status = 'Delivered' AND m.actual_delivery_date <= o.expected_date THEN m.manifest_id END) AS on_time_count
            FROM suppliers s
            LEFT JOIN orders o ON o.supplier_id = s.supplier_id
            LEFT JOIN logistics_manifests m ON m.order_id = o.order_id
            GROUP BY s.supplier_id, s.supplier_name, s.rating, s.is_active
            ORDER BY s.supplier_name ASC";

    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $delivered = (int)$row['delivered_count'];
            $onTime = (int)$row['on_time_count'];
            $row['on_time_pct'] = $delivered > 0 ? round(($onTime / $delivered) * 100, 1) : null;
            $vendors[] = $row;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Performance Scorecard</title>
    <link href="app.css?v=<?php echo @filemtime('app.css'); ?>" rel="stylesheet"/>
    <script src="app.js?v=<?php echo @filemtime('app.js'); ?>" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'header.php'; ?>
<main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Vendor Performance Scorecard</h1>
                    <p class="text-slate-500 text-sm mt-1">On-time delivery, order volume, and fulfillment history per vendor.</p>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-sm font-bold text-slate-900">Vendors</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Vendor</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Rating</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Orders</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Total Value</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">On-Time %</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Delayed Shipments</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($vendors)): ?>
                                    <tr><td colspan="6" class="px-6 py-8 text-center text-xs text-slate-400">No vendors found yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($vendors as $v): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 text-xs font-medium text-slate-900">
                                                <?php echo htmlspecialchars($v['supplier_name']); ?>
                                                <?php if (!$v['is_active']): ?>
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold text-rose-700 bg-rose-50 border border-rose-200">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-xs text-slate-600">★ <?php echo htmlspecialchars($v['rating']); ?>/5.0</td>
                                            <td class="px-6 py-4 text-xs text-slate-600"><?php echo (int)$v['total_orders']; ?></td>
                                            <td class="px-6 py-4 text-xs text-slate-600">₱<?php echo number_format((float)$v['total_value'], 2); ?></td>
                                            <td class="px-6 py-4 text-xs">
                                                <?php if ($v['on_time_pct'] === null): ?>
                                                    <span class="text-slate-400">No deliveries yet</span>
                                                <?php else: ?>
                                                    <?php
                                                        $pct = $v['on_time_pct'];
                                                        $pillClass = $pct >= 90 ? 'text-emerald-700 bg-emerald-50 border-emerald-200'
                                                            : ($pct >= 70 ? 'text-amber-700 bg-amber-50 border-amber-200'
                                                            : 'text-rose-700 bg-rose-50 border-rose-200');
                                                    ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $pillClass; ?>">
                                                        <?php echo $pct; ?>%
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-xs text-slate-600"><?php echo (int)$v['delayed_count']; ?></td>
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