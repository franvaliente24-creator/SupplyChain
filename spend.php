<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'psm_connection.php';
$section_title = "Procurement & Sourcing Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';
$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'log_spend') {
        $category = trim($_POST['category'] ?? '');
        $dept = trim($_POST['department'] ?? '');
        $po_no = trim($_POST['po_number'] ?? '');
        $vendor = trim($_POST['vendor_name'] ?? '');
        $amount = (float)$_POST['amount'];
        $spend_date = !empty($_POST['spend_date']) ? $_POST['spend_date'] : date('Y-m-d');

        if ($category === '' || $vendor === '' || $amount <= 0) {
            $db_error = "Category, Vendor Name, and a valid Amount are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO spend_logs (category, department, po_number, vendor_name, amount, spend_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssds", $category, $dept, $po_no, $vendor, $amount, $spend_date);
            if ($stmt->execute()) {
                $flash = "Procurement expenditure logged.";
            } else {
                $db_error = "Failed to log spend: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$spends = [];
$total_spend = 0;
$category_breakdown = [];

if (!$conn->connect_error) {
    $c_res = $conn->query("SELECT category, SUM(amount) as cat_total FROM spend_logs GROUP BY category ORDER BY cat_total DESC");
    if ($c_res) while ($r = $c_res->fetch_assoc()) $category_breakdown[] = $r;

    $sql = "SELECT * FROM spend_logs ORDER BY spend_date DESC LIMIT 100";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $total_spend += (float)$r['amount'];
            $spends[] = $r;
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
    <title><?php echo $section_title; ?> — Spend Analytics</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 1rem; width: 100%; max-width: 32rem; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .form-field { margin-bottom: 0.85rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select { width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem; }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Procurement & Sourcing Management</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-600 text-sm font-medium">Spend Analysis</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Spend Analytics & Procurement Outlays</h1>
                        <p class="text-slate-500 text-sm mt-1">Audit department expenditures, category allocations, and PO disbursements.</p>
                    </div>
                    <button type="button" onclick="openSpendModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">payments</span> Log Expenditure
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Outlay Tracked</p>
                        <h3 class="text-3xl font-bold text-slate-900 mt-1">&#8369;<?php echo number_format($total_spend, 2); ?></h3>
                    </div>
                    <?php foreach (array_slice($category_breakdown, 0, 2) as $cat): ?>
                        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?php echo htmlspecialchars($cat['category']); ?></p>
                            <h3 class="text-3xl font-bold text-primary mt-1">&#8369;<?php echo number_format($cat['cat_total'], 2); ?></h3>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">PO Reference</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Supplier / Vendor</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Department</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Disbursed Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($spends)): ?>
                                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No spend records logged.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($spends as $s): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?php echo date('M j, Y', strtotime($s['spend_date'])); ?></td>
                                            <td class="px-4 py-3 font-mono font-bold text-primary"><?php echo htmlspecialchars($s['po_number'] ?: 'Direct Outlay'); ?></td>
                                            <td class="px-4 py-3 font-semibold text-slate-900"><?php echo htmlspecialchars($s['vendor_name']); ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($s['category']); ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($s['department']); ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">&#8369;<?php echo number_format($s['amount'], 2); ?></td>
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

    <!-- Modal -->
    <div id="spend-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Log Procurement Expenditure</h3>
                <button type="button" onclick="closeSpendModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="log_spend"/>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>PO Number (Optional)</label>
                        <input type="text" name="po_number" placeholder="PO-2026-095"/>
                    </div>
                    <div class="form-field">
                        <label>Spend Date</label>
                        <input type="date" name="spend_date" value="<?php echo date('Y-m-d'); ?>" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Supplier / Vendor Name</label>
                    <input type="text" name="vendor_name" placeholder="Supplier Company" required/>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Category</label>
                        <input type="text" name="category" placeholder="Electronics / Equipment" required/>
                    </div>
                    <div class="form-field">
                        <label>Department</label>
                        <input type="text" name="department" placeholder="IT / Operations" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Total Disbursed Amount (&#8369;)</label>
                    <input type="number" step="0.01" name="amount" min="0" required/>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeSpendModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Log Spend</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openSpendModal() { document.getElementById('spend-modal').style.display = 'flex'; }
        function closeSpendModal() { document.getElementById('spend-modal').style.display = 'none'; }
    </script>
</body>
</html>