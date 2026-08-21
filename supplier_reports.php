<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Supplier Reports Dashboard";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;

// Check if required tables exist
$suppliers_table = false;
$transactions_table = false;
$documents_table = false;

$check_sup = $conn->query("SHOW TABLES LIKE 'suppliers'");
if ($check_sup && $check_sup->num_rows > 0) {
    $suppliers_table = true;
}
$check_sup->free();

$check_trans = $conn->query("SHOW TABLES LIKE 'supplier_transactions'");
if ($check_trans && $check_trans->num_rows > 0) {
    $transactions_table = true;
}
$check_trans->free();

$check_docs = $conn->query("SHOW TABLES LIKE 'supplier_documents'");
if ($check_docs && $check_docs->num_rows > 0) {
    $documents_table = true;
}
$check_docs->free();

$report_data = [];

if ($suppliers_table && !$conn->connect_error) {
    // Supplier status breakdown
    $status_result = $conn->query("SELECT status, COUNT(*) as count FROM suppliers GROUP BY status");
    if ($status_result) {
        while ($row = $status_result->fetch_assoc()) {
            $report_data['status_breakdown'][] = $row;
        }
    }
    
    // Supplier category breakdown
    $category_result = $conn->query("SELECT vendor_type, COUNT(*) as count FROM suppliers GROUP BY vendor_type");
    if ($category_result) {
        while ($row = $category_result->fetch_assoc()) {
            $report_data['category_breakdown'][] = $row;
        }
    }
    
    // Performance rating distribution
    $rating_result = $conn->query("SELECT 
        CASE 
            WHEN rating >= 4.5 THEN 'Excellent (4.5+)'
            WHEN rating >= 4.0 THEN 'Good (4.0-4.4)'
            WHEN rating >= 3.5 THEN 'Average (3.5-3.9)'
            WHEN rating >= 3.0 THEN 'Fair (3.0-3.4)'
            ELSE 'Poor (<3.0)'
        END as rating_range,
        COUNT(*) as count 
        FROM suppliers 
        WHERE rating IS NOT NULL 
        GROUP BY rating_range 
        ORDER BY rating_range");
    if ($rating_result) {
        while ($row = $rating_result->fetch_assoc()) {
            $report_data['rating_distribution'][] = $row;
        }
    }
    
    // Transaction summary if table exists
    if ($transactions_table) {
        $trans_summary = $conn->query("SELECT 
            transaction_type, 
            COUNT(*) as count, 
            SUM(amount) as total_amount 
            FROM supplier_transactions 
            GROUP BY transaction_type");
        if ($trans_summary) {
            while ($row = $trans_summary->fetch_assoc()) {
                $report_data['transaction_summary'][] = $row;
            }
        }
    }
    
    // Document expiry tracking if table exists
    if ($documents_table) {
        $expiry_tracking = $conn->query("SELECT 
            CASE 
                WHEN expiry_date < CURDATE() THEN 'Expired'
                WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
                WHEN expiry_date > DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Valid'
                ELSE 'No Expiry'
            END as expiry_status,
            COUNT(*) as count 
            FROM supplier_documents 
            WHERE expiry_date IS NOT NULL 
            GROUP BY expiry_status");
        if ($expiry_tracking) {
            while ($row = $expiry_tracking->fetch_assoc()) {
                $report_data['document_expiry'][] = $row;
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
        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #3b82f6;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Supplier / Vendor Management</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Supplier Reports Dashboard</h1>
                        <p class="text-slate-500 text-sm mt-1">Comprehensive analytics on supplier accreditation, performance, and transaction summaries.</p>
                    </div>
                </div>

                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <!-- Status Breakdown -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php 
                    $status_colors = [
                        'Active' => 'bg-emerald-500',
                        'Pending Approval' => 'bg-amber-500',
                        'Suspended' => 'bg-red-500',
                        'Blacklisted' => 'bg-slate-500'
                    ];
                    $total_suppliers = array_sum(array_column($report_data['status_breakdown'] ?? [], 'count'));
                    foreach ($report_data['status_breakdown'] ?? [] as $status): 
                        $percentage = $total_suppliers > 0 ? round(($status['count'] / $total_suppliers) * 100) : 0;
                        $color = $status_colors[$status['status']] ?? 'bg-slate-500';
                    ?>
                        <div class="stat-card">
                            <div class="flex items-center justify-between mb-2">
                                <p class="stat-label"><?php echo htmlspecialchars($status['status']); ?></p>
                                <span class="text-2xl font-bold" style="color: <?php echo $color; ?>;"><?php echo $status['count']; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $color; ?>;"></div>
                            </div>
                            <p class="text-xs text-slate-500 mt-1"><?php echo $percentage; ?>% of total</p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Category Breakdown -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-sm font-bold text-slate-900">Supplier Category Distribution</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($report_data['category_breakdown'] ?? [])): ?>
                            <p class="text-slate-500 text-center py-4">No category data available.</p>
                        <?php else: ?>
                            <?php 
                            $total_categories = array_sum(array_column($report_data['category_breakdown'], 'count'));
                            foreach ($report_data['category_breakdown'] as $category): 
                                $percentage = $total_categories > 0 ? round(($category['count'] / $total_categories) * 100) : 0;
                            ?>
                                <div class="mb-4">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($category['vendor_type']); ?></span>
                                        <span class="text-sm text-slate-500"><?php echo $category['count']; ?> suppliers (<?php echo $percentage; ?>%)</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Performance Rating Distribution -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-sm font-bold text-slate-900">Performance Rating Distribution</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($report_data['rating_distribution'] ?? [])): ?>
                                <p class="text-slate-500 text-center py-4">No rating data available.</p>
                            <?php else: ?>
                                <?php foreach ($report_data['rating_distribution'] as $rating): ?>
                                    <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                                        <span class="text-sm text-slate-600"><?php echo htmlspecialchars($rating['rating_range']); ?></span>
                                        <span class="text-sm font-semibold text-slate-900"><?php echo $rating['count']; ?> suppliers</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Transaction Summary -->
                    <?php if ($transactions_table): ?>
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-200">
                                <h2 class="text-sm font-bold text-slate-900">Transaction Summary</h2>
                            </div>
                            <div class="p-6">
                                <?php if (empty($report_data['transaction_summary'] ?? [])): ?>
                                    <p class="text-slate-500 text-center py-4">No transaction data available.</p>
                                <?php else: ?>
                                    <?php foreach ($report_data['transaction_summary'] as $trans): ?>
                                        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0">
                                            <span class="text-sm text-slate-600"><?php echo htmlspecialchars($trans['transaction_type']); ?></span>
                                            <div class="text-right">
                                                <span class="text-sm font-semibold text-slate-900"><?php echo $trans['count']; ?> transactions</span>
                                                <?php if ($trans['total_amount']): ?>
                                                    <span class="text-xs text-slate-500">₱<?php echo number_format($trans['total_amount'], 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Document Expiry Tracking -->
                <?php if ($documents_table): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-sm font-bold text-slate-900">Document Expiry Status</h2>
                        </div>
                        <div class="p-6">
                            <?php if (empty($report_data['document_expiry'] ?? [])): ?>
                                <p class="text-slate-500 text-center py-4">No document expiry data available.</p>
                            <?php else: ?>
                                <div class="grid grid-cols-3 gap-4">
                                    <?php foreach ($report_data['document_expiry'] as $expiry): 
                                        $bg_color = $expiry['expiry_status'] === 'Expired' ? 'bg-red-50 border-red-200' : 
                                                    ($expiry['expiry_status'] === 'Expiring Soon' ? 'bg-amber-50 border-amber-200' : 'bg-emerald-50 border-emerald-200');
                                    ?>
                                        <div class="p-4 rounded-lg border <?php echo $bg_color; ?>">
                                            <p class="text-xs text-slate-500 uppercase tracking-wider"><?php echo htmlspecialchars($expiry['expiry_status']); ?></p>
                                            <p class="text-2xl font-bold text-slate-900"><?php echo $expiry['count']; ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>