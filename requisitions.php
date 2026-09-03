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

    if ($action === 'create_requisition') {
        $req_no = 'REQ-' . date('Y') . '-' . str_pad((string)rand(10, 999), 3, '0', STR_PAD_LEFT);
        $dept = trim($_POST['department'] ?? '');
        $requested_by = trim($_POST['requested_by'] ?? $admin_user);
        $item_desc = trim($_POST['item_description'] ?? '');
        $qty = (int)$_POST['quantity'];
        $cost = (float)$_POST['estimated_cost'];
        $priority = $_POST['priority'] ?? 'Normal';
        $justification = trim($_POST['justification'] ?? '');

        if ($dept === '' || $item_desc === '' || $qty <= 0) {
            $db_error = "Department, Item Description, and a valid Quantity are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO purchase_requisitions (requisition_number, department, requested_by, item_description, quantity, estimated_cost, priority, status, justification) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending Approval', ?)");
            $stmt->bind_param("ssssidss", $req_no, $dept, $requested_by, $item_desc, $qty, $cost, $priority, $justification);
            if ($stmt->execute()) {
                $flash = "Requisition $req_no submitted for review.";
            } else {
                $db_error = "Failed to submit requisition: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'update_status') {
        $req_id = (int)$_POST['requisition_id'];
        $new_status = $_POST['status'];
        $approved_by = ($new_status === 'Approved') ? $admin_user : null;

        $stmt = $conn->prepare("UPDATE purchase_requisitions SET status = ?, approved_by = ? WHERE requisition_id = ?");
        $stmt->bind_param("ssi", $new_status, $approved_by, $req_id);
        if ($stmt->execute()) {
            $flash = "Requisition status updated to '$new_status'.";
        } else {
            $db_error = "Failed to update requisition status: " . $stmt->error;
        }
        $stmt->close();
    }
}

$requisitions = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';

if (!$conn->connect_error) {
    $sql = "SELECT * FROM purchase_requisitions WHERE 1=1";
    $params = [];
    $types = '';

    if ($search !== '') {
        $sql .= " AND (requisition_number LIKE ? OR item_description LIKE ? OR requested_by LIKE ? OR department LIKE ?)";
        $like = "%$search%";
        $params = [$like, $like, $like, $like];
        $types = 'ssss';
    }

    if ($filter_status !== '') {
        $sql .= " AND status = ?";
        $params[] = $filter_status;
        $types .= 's';
    }

    $sql .= " ORDER BY created_at DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql);
    }

    if ($res) while ($r = $res->fetch_assoc()) $requisitions[] = $r;
} else {
    $db_error = "Database offline.";
}

function getReqStatusBadge($status) {
    switch ($status) {
        case 'Approved': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Sourced': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Rejected': return 'bg-rose-100 text-rose-800 border-rose-200';
        default: return 'bg-amber-100 text-amber-800 border-amber-200';
    }
}

function getReqPriorityBadge($priority) {
    switch ($priority) {
        case 'Urgent': return 'text-rose-700 bg-rose-50 border-rose-200 font-bold';
        case 'High': return 'text-amber-700 bg-amber-50 border-amber-200';
        default: return 'text-slate-600 bg-slate-50 border-slate-200';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Requisitions</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 1rem; width: 100%; max-width: 32rem; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .form-field { margin-bottom: 0.85rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select, .form-field textarea { width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem; }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Procurement & Sourcing Management</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-600 text-sm font-medium">Requisitions</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Purchase Requisitions Index</h1>
                        <p class="text-slate-500 text-sm mt-1">Submit, evaluate, and approve internal departmental requests for procurement.</p>
                    </div>
                    <button type="button" onclick="openReqModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add</span> New Requisition
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200">
                        <form method="get" class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Requisition No, Item, Requestor, or Department..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                            </div>
                            <select name="status" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="Pending Approval" <?php echo $filter_status === 'Pending Approval' ? 'selected' : ''; ?>>Pending Approval</option>
                                <option value="Approved" <?php echo $filter_status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Sourced" <?php echo $filter_status === 'Sourced' ? 'selected' : ''; ?>>Sourced</option>
                                <option value="Rejected" <?php echo $filter_status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold">Filter</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Requisition #</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Item & Justification</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Department / User</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Est. Cost</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($requisitions)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No purchase requisitions found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($requisitions as $r): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-mono font-bold text-primary"><?php echo htmlspecialchars($r['requisition_number']); ?></td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($r['item_description']); ?> <span class="text-slate-400 font-normal">(&times;<?php echo $r['quantity']; ?>)</span></div>
                                                <div class="text-[10px] text-slate-500 max-w-xs truncate"><?php echo htmlspecialchars($r['justification'] ?: 'No details'); ?></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-800"><?php echo htmlspecialchars($r['department']); ?></div>
                                                <div class="text-[10px] text-slate-500"><?php echo htmlspecialchars($r['requested_by']); ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-semibold text-slate-800">&#8369;<?php echo number_format($r['estimated_cost'], 2); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] border <?php echo getReqPriorityBadge($r['priority']); ?>">
                                                    <?php echo htmlspecialchars($r['priority']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border <?php echo getReqStatusBadge($r['status']); ?>">
                                                    <?php echo htmlspecialchars($r['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <?php if ($r['status'] === 'Pending Approval'): ?>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="update_status"/>
                                                        <input type="hidden" name="requisition_id" value="<?php echo $r['requisition_id']; ?>"/>
                                                        <input type="hidden" name="status" value="Approved"/>
                                                        <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[10px] font-bold">Approve</button>
                                                    </form>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="update_status"/>
                                                        <input type="hidden" name="requisition_id" value="<?php echo $r['requisition_id']; ?>"/>
                                                        <input type="hidden" name="status" value="Rejected"/>
                                                        <button type="submit" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded text-[10px] font-bold">Reject</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 font-semibold"><?php echo htmlspecialchars($r['status']); ?></span>
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

    <!-- Create Modal -->
    <div id="req-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">New Purchase Requisition</h3>
                <button type="button" onclick="closeReqModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_requisition"/>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Department</label>
                        <input type="text" name="department" placeholder="Operations / Logistics" required/>
                    </div>
                    <div class="form-field">
                        <label>Priority</label>
                        <select name="priority">
                            <option value="Normal">Normal</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="form-field">
                    <label>Item Description / Specification</label>
                    <input type="text" name="item_description" placeholder="Item name or required specs" required/>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Quantity</label>
                        <input type="number" name="quantity" min="1" value="1" required/>
                    </div>
                    <div class="form-field">
                        <label>Estimated Total Cost (&#8369;)</label>
                        <input type="number" step="0.01" name="estimated_cost" min="0" value="0.00" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Justification / Purpose</label>
                    <textarea name="justification" rows="2" placeholder="Explain why this purchase is needed..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeReqModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openReqModal() { document.getElementById('req-modal').style.display = 'flex'; }
        function closeReqModal() { document.getElementById('req-modal').style.display = 'none'; }
    </script>
</body>
</html>