<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db_connection.php';

$section_title = "Procurement & Sourcing Management (PSM)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO purchase_requisitions 
            (requisition_number, req_type, candidate_client_name, item_id, quantity, estimated_cost, project_budget_limit, preferred_vendor_id, rfq_quote_amount, requested_by, status, approval_stage) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Recruiter Lead')");
        
        $item_id = $_POST['item_id'] !== '' ? (int)$_POST['item_id'] : null;
        $vendor_id = $_POST['preferred_vendor_id'] !== '' ? (int)$_POST['preferred_vendor_id'] : null;
        $qty = (int)$_POST['quantity'];
        $cost = (float)$_POST['estimated_cost'];
        $budget_limit = (float)$_POST['project_budget_limit'];
        $rfq_quote = (float)$_POST['rfq_quote_amount'];

        $stmt->bind_param("sssiidddsss", 
            $_POST['requisition_number'],
            $_POST['req_type'],
            $_POST['candidate_client_name'],
            $item_id, 
            $qty, 
            $cost, 
            $budget_limit,
            $vendor_id,
            $rfq_quote,
            $_POST['requested_by'], 
            $_POST['status']
        );

        if ($stmt->execute()) {
            $flash = "IT Requisition logged successfully and routed to Recruiter Lead for approval.";
            $log_msg = "Sourced Requisition " . $_POST['requisition_number'] . " for candidate/project: " . $_POST['candidate_client_name'];
            $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'Pending', 'status-pill-warning')");
        } else {
            $db_error = "Failed to create requisition: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'advance_approval') {
        $req_id = (int)$_POST['requisition_id'];
        $current_stage = $_POST['current_stage'];
        
        $next_stage = 'Recruiter Lead';
        $new_status = 'Pending Approval';

        if ($current_stage === 'Recruiter Lead') {
            $next_stage = 'IT Director';
        } elseif ($current_stage === 'IT Director') {
            $next_stage = 'Finance';
        } elseif ($current_stage === 'Finance') {
            $next_stage = 'Fully Approved';
            $new_status = 'Sourced';
        }

        $stmt = $conn->prepare("UPDATE purchase_requisitions SET approval_stage = ?, status = ? WHERE requisition_id = ?");
        $stmt->bind_param("ssi", $next_stage, $new_status, $req_id);
        
        if ($stmt->execute()) {
            $flash = "Requisition advanced to stage: $next_stage.";
            $log_msg = "Requisition #" . $req_id . " advanced to " . $next_stage;
            $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'Approved', 'status-pill-success')");
        } else {
            $db_error = "Failed to update approval stage: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'reject_req') {
        $req_id = (int)$_POST['requisition_id'];
        $stmt = $conn->prepare("UPDATE purchase_requisitions SET status = 'Rejected', approval_stage = 'Rejected' WHERE requisition_id = ?");
        $stmt->bind_param("i", $req_id);
        
        if ($stmt->execute()) {
            $flash = "Requisition marked as Rejected.";
        } else {
            $db_error = "Failed to reject requisition: " . $stmt->error;
        }
        $stmt->close();
    }
}

$requisitions = [];
$items_list = [];
$vendors_list = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$conn->connect_error) {
    $sql = "SELECT r.*, i.item_name, i.sku, i.unit_price, s.supplier_name 
            FROM purchase_requisitions r 
            LEFT JOIN inventory_items i ON r.item_id = i.item_id
            LEFT JOIN suppliers s ON r.preferred_vendor_id = s.supplier_id";
    if ($search !== '') {
        $sql .= " WHERE r.requisition_number LIKE ? OR i.item_name LIKE ? OR r.candidate_client_name LIKE ? OR r.requested_by LIKE ?";
    }
    $sql .= " ORDER BY r.created_at DESC";

    if ($search !== '') {
        $stmt = $conn->prepare($sql);
        $like = "%$search%";
        $stmt->bind_param("ssss", $like, $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $requisitions[] = $row;
        }
    }

    $items_res = $conn->query("SELECT item_id, item_name, sku, unit_price FROM inventory_items WHERE status != 'Archived'");
    if ($items_res) {
        while ($row = $items_res->fetch_assoc()) {
            $items_list[] = $row;
        }
    }

    $vendors_res = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    if ($vendors_res) {
        while ($row = $vendors_res->fetch_assoc()) {
            $vendors_list[] = $row;
        }
    }
} else {
    $db_error = "Database offline.";
}

function getReqBadgeClass($status) {
    switch ($status) {
        case 'Sourced': return 'status-badge-active';
        case 'Pending Approval': return 'status-badge-maintenance';
        case 'Rejected': return 'status-badge-critical';
        default: return 'status-badge-archived';
    }
}

function getStageBadgeClass($stage) {
    switch ($stage) {
        case 'Recruiter Lead': return 'bg-amber-100 text-amber-800 border-amber-200 px-2.5 py-1 inline-flex items-center justify-center rounded-md font-bold text-[10px] whitespace-nowrap';
        case 'IT Director': return 'bg-blue-100 text-blue-800 border-blue-200 px-2.5 py-1 inline-flex items-center justify-center rounded-md font-bold text-[10px] whitespace-nowrap';
        case 'Finance': return 'bg-purple-100 text-purple-800 border-purple-200 px-2.5 py-1 inline-flex items-center justify-center rounded-md font-bold text-[10px] whitespace-nowrap';
        case 'Fully Approved': return 'bg-emerald-100 text-emerald-800 border-emerald-200 px-2.5 py-1 inline-flex items-center justify-center rounded-md font-bold text-[10px] whitespace-nowrap';
        default: return 'bg-slate-100 text-slate-800 px-2.5 py-1 inline-flex items-center justify-center rounded-md font-bold text-[10px] whitespace-nowrap';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Workspace</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        .status-badge-active      { background: #dcfce7; color: #166534; }
        .status-badge-maintenance { background: #fef3c7; color: #92400e; }
        .status-badge-critical    { background: #fee2e2; color: #991b1b; }
        .status-badge-archived    { background: #e2e8f0; color: #475569; }
        
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 100; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 1rem; width: 100%; max-width: 36rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-height: 90vh; overflow-y: auto;
        }
        .form-field { margin-bottom: 0.85rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select {
            width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">ISMERS Sourcing & Procurement Portal</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">IT & Equipment Requisition Portal</h1>
                        <p class="text-slate-500 text-sm mt-1">Request candidate welcome packs, evaluate vendor RFQ quotes, check budget allocations, and process multi-stage approvals.</p>
                    </div>
                    <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">post_add</span> Create Requisition
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <form method="get" class="relative w-full sm:max-w-sm">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search PR code, candidate, item, or source..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">PR Code & Type</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Candidate / Project</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Requested Gear</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Cost vs Budget</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Vendor RFQ Quote</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Approval Stage</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Approval Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($requisitions)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-slate-400">No purchase requisitions found on queue.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requisitions as $req): 
                                        $badge = getReqBadgeClass($req['status']);
                                        $stage_badge = getStageBadgeClass($req['approval_stage']);
                                        $est_cost = (float)$req['estimated_cost'];
                                        $budget_limit = (float)$req['project_budget_limit'];
                                        $over_budget = ($budget_limit > 0 && $est_cost > $budget_limit);
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-4">
                                                <div class="font-mono font-bold text-slate-900"><?php echo htmlspecialchars($req['requisition_number']); ?></div>
                                                <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 rounded-md">
                                                    <?php echo htmlspecialchars($req['req_type'] ?? 'Candidate Placement'); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($req['candidate_client_name'] ?: 'N/A'); ?></div>
                                                <div class="text-[10px] text-slate-400">By: <?php echo htmlspecialchars($req['requested_by']); ?></div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($req['item_name'] ?: 'Custom Hardware'); ?></div>
                                                <div class="text-[10px] text-slate-400">Qty: <?php echo number_format($req['quantity']); ?> units</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="font-bold text-slate-900">₱<?php echo number_format($est_cost, 2); ?></div>
                                                <?php if ($budget_limit > 0): ?>
                                                    <div class="text-[10px] font-semibold <?php echo $over_budget ? 'text-rose-600' : 'text-emerald-600'; ?>">
                                                        Limit: ₱<?php echo number_format($budget_limit, 2); ?> 
                                                        <?php echo $over_budget ? '⚠️ Over' : '✓ OK'; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-[10px] text-slate-400">No Limit Set</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-slate-800"><?php echo htmlspecialchars($req['supplier_name'] ?: 'Direct RFQ'); ?></div>
                                                <div class="text-[10px] text-slate-500 font-mono">Quote: ₱<?php echo number_format((float)$req['rfq_quote_amount'], 2); ?></div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="<?php echo $stage_badge; ?>">
                                                    <?php echo htmlspecialchars($req['approval_stage']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="status-badge <?php echo $badge; ?>" style="padding: 2px 8px; font-size: 0.65rem; border-radius: 999px;">
                                                    <?php echo htmlspecialchars($req['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-right whitespace-nowrap">
                                                <?php if ($req['status'] !== 'Sourced' && $req['status'] !== 'Rejected'): ?>
                                                    <div class="inline-flex gap-1">
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="action" value="advance_approval"/>
                                                            <input type="hidden" name="requisition_id" value="<?php echo (int)$req['requisition_id']; ?>"/>
                                                            <input type="hidden" name="current_stage" value="<?php echo htmlspecialchars($req['approval_stage']); ?>"/>
                                                            <button type="submit" class="px-2 py-1 text-[11px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded font-semibold flex items-center gap-1" title="Approve & Advance Stage">
                                                                <span class="material-symbols-outlined text-xs">done</span>
                                                                <span>Approve</span>
                                                            </button>
                                                        </form>
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="action" value="reject_req"/>
                                                            <input type="hidden" name="requisition_id" value="<?php echo (int)$req['requisition_id']; ?>"/>
                                                            <button type="submit" class="px-2 py-1 text-[11px] bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 rounded font-semibold flex items-center gap-1" title="Reject Request">
                                                                <span class="material-symbols-outlined text-xs">close</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 font-medium">Archived</span>
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

    <div id="requisition-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Create IT Equipment Requisition</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="add"/>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Requisition PR Code</label>
                        <input type="text" name="requisition_number" placeholder="PR-2026-003" required/>
                    </div>
                    <div class="form-field">
                        <label>Requisition Category</label>
                        <select name="req_type" required>
                            <option value="Candidate Placement">Candidate Welcome Pack / Hire</option>
                            <option value="Hardware Upgrade">Hardware Upgrade</option>
                            <option value="Internal Staff">Internal Staff Request</option>
                            <option value="Office Supplies">Office Supplies</option>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label>Candidate / Client Placement Target Name</label>
                    <input type="text" name="candidate_client_name" placeholder="John Doe (Placement: Acme Corp)" required/>
                </div>

                <div class="form-field">
                    <label>Target Hardware / Software Item</label>
                    <select name="item_id" id="f-item" onchange="calculateBudget()" required>
                        <option value="">Select item catalog...</option>
                        <?php foreach ($items_list as $it): ?>
                            <option value="<?php echo $it['item_id']; ?>" data-price="<?php echo $it['unit_price']; ?>">
                                <?php echo htmlspecialchars($it['sku'] . ' — ' . $it['item_name']); ?> (₱<?php echo number_format($it['unit_price'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="form-field">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="f-qty" min="1" value="1" oninput="calculateBudget()" required/>
                    </div>
                    <div class="form-field">
                        <label>Estimated Total (₱)</label>
                        <input type="number" step="0.01" name="estimated_cost" id="f-cost" readonly required/>
                    </div>
                    <div class="form-field">
                        <label>Budget Allocation (₱)</label>
                        <input type="number" step="0.01" name="project_budget_limit" placeholder="50000.00" required/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Wholesale Vendor (RFQ)</label>
                        <select name="preferred_vendor_id">
                            <option value="">Select preferred IT vendor...</option>
                            <?php foreach ($vendors_list as $v): ?>
                                <option value="<?php echo $v['supplier_id']; ?>"><?php echo htmlspecialchars($v['supplier_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Vendor RFQ Quote (₱)</label>
                        <input type="number" step="0.01" name="rfq_quote_amount" placeholder="48000.00"/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Requesting Lead / Recruiter</label>
                        <input type="text" name="requested_by" placeholder="Recruiter Lead - Jane" required/>
                    </div>
                    <div class="form-field">
                        <label>Initial Status</label>
                        <select name="status">
                            <option value="Pending Approval">Pending Approval Pipeline</option>
                            <option value="Draft">Draft Stage</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Submit Requisition</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('requisition-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('requisition-modal').style.display = 'none';
        }

        function calculateBudget() {
            const select = document.getElementById('f-item');
            const qtyInput = document.getElementById('f-qty');
            const costInput = document.getElementById('f-cost');
            
            if (!select || !qtyInput || !costInput) return;

            const selectedOption = select.options[select.selectedIndex];
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const qty = parseInt(qtyInput.value) || 0;

            costInput.value = (price * qty).toFixed(2);
        }
    </script>
</body>
</html>