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

    if ($action === 'add_project') {
        $name = trim($_POST['project_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $supplier = trim($_POST['target_supplier'] ?? '');
        $contract_type = $_POST['contract_type'] ?? 'Fixed Price';
        $deadline = $_POST['target_completion'];
        $savings = (float)$_POST['estimated_savings'];

        if ($name === '' || empty($deadline)) {
            $db_error = "Project Name and Target Completion Date are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO sourcing_projects (project_name, category, target_supplier, contract_type, target_completion, status, estimated_savings) VALUES (?, ?, ?, ?, ?, 'Planning', ?)");
            $stmt->bind_param("sssssd", $name, $category, $supplier, $contract_type, $deadline, $savings);
            if ($stmt->execute()) {
                $flash = "Sourcing project '$name' initiated.";
            } else {
                $db_error = "Failed to create sourcing project: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'update_status') {
        $project_id = (int)$_POST['project_id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE sourcing_projects SET status = ? WHERE project_id = ?");
        $stmt->bind_param("si", $status, $project_id);
        if ($stmt->execute()) {
            $flash = "Project milestone updated to '$status'.";
        } else {
            $db_error = "Failed to update project status: " . $stmt->error;
        }
        $stmt->close();
    }
}

$projects = [];
$total_savings = 0;

if (!$conn->connect_error) {
    $res = $conn->query("SELECT * FROM sourcing_projects ORDER BY target_completion ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $total_savings += (float)$r['estimated_savings'];
            $projects[] = $r;
        }
    }
} else {
    $db_error = "Database offline.";
}

function getSourcingBadge($status) {
    switch ($status) {
        case 'Completed': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Contracting': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Evaluating Quotes': return 'bg-purple-100 text-purple-800 border-purple-200';
        default: return 'bg-amber-100 text-amber-800 border-amber-200';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Sourcing Projects</title>
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
                <span class="text-slate-600 text-sm font-medium">Sourcing Pipeline</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Strategic Sourcing Pipeline</h1>
                        <p class="text-slate-500 text-sm mt-1">Manage vendor contract renewals, strategic supply agreements, and cost optimization.</p>
                    </div>
                    <button type="button" onclick="openSourcingModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">handshake</span> New Sourcing Project
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Sourcing Projects</p>
                        <h3 class="text-3xl font-bold text-slate-900 mt-1"><?php echo count($projects); ?></h3>
                    </div>
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Target Projected Savings</p>
                        <h3 class="text-3xl font-bold text-emerald-700 mt-1">&#8369;<?php echo number_format($total_savings, 2); ?></h3>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Project Name</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Contract Type</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Target Completion</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Target Savings</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Milestone</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($projects)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No active sourcing projects recorded.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($projects as $p): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-semibold text-slate-900"><?php echo htmlspecialchars($p['project_name']); ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($p['category']); ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($p['contract_type']); ?></td>
                                            <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y', strtotime($p['target_completion'])); ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-semibold text-emerald-700">&#8369;<?php echo number_format($p['estimated_savings'], 2); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border <?php echo getSourcingBadge($p['status']); ?>">
                                                    <?php echo htmlspecialchars($p['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="update_status"/>
                                                    <input type="hidden" name="project_id" value="<?php echo $p['project_id']; ?>"/>
                                                    <select name="status" class="text-xs py-1 px-2 rounded border border-slate-200 bg-white" onchange="this.form.submit()">
                                                        <option value="Planning" <?php echo $p['status'] === 'Planning' ? 'selected' : ''; ?>>Planning</option>
                                                        <option value="Evaluating Quotes" <?php echo $p['status'] === 'Evaluating Quotes' ? 'selected' : ''; ?>>Evaluating Quotes</option>
                                                        <option value="Contracting" <?php echo $p['status'] === 'Contracting' ? 'selected' : ''; ?>>Contracting</option>
                                                        <option value="Completed" <?php echo $p['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                    </select>
                                                </form>
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

    <!-- Modal -->
    <div id="sourcing-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">New Sourcing Initiative</h3>
                <button type="button" onclick="closeSourcingModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="add_project"/>
                <div class="form-field">
                    <label>Project Name</label>
                    <input type="text" name="project_name" placeholder="e.g. Bulk Consumables Contract 2026" required/>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Category</label>
                        <input type="text" name="category" placeholder="Packaging / Electronics" required/>
                    </div>
                    <div class="form-field">
                        <label>Contract Model</label>
                        <select name="contract_type">
                            <option value="Fixed Price">Fixed Price</option>
                            <option value="Time & Materials">Time & Materials</option>
                            <option value="Recurring Retainer">Recurring Retainer</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Target Supplier (Optional)</label>
                        <input type="text" name="target_supplier" placeholder="Preferred Vendor"/>
                    </div>
                    <div class="form-field">
                        <label>Projected Savings (&#8369;)</label>
                        <input type="number" step="0.01" name="estimated_savings" min="0" value="0.00"/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Target Completion Date</label>
                    <input type="date" name="target_completion" required/>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeSourcingModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Create Initiative</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openSourcingModal() { document.getElementById('sourcing-modal').style.display = 'flex'; }
        function closeSourcingModal() { document.getElementById('sourcing-modal').style.display = 'none'; }
    </script>
</body>
</html>