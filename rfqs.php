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

    if ($action === 'create_rfq') {
        $rfq_no = 'RFQ-' . date('Y') . '-' . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $budget = (float)$_POST['budget_limit'];
        $deadline = $_POST['deadline'];
        $req_id = !empty($_POST['requisition_id']) ? (int)$_POST['requisition_id'] : null;
        $specs = trim($_POST['specifications'] ?? '');

        if ($title === '' || empty($deadline)) {
            $db_error = "RFQ Title and Deadline Date are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO rfqs (rfq_number, requisition_id, title, category, budget_limit, deadline, status, specifications, created_by) VALUES (?, ?, ?, ?, ?, ?, 'Open', ?, ?)");
            $stmt->bind_param("sisssdss", $rfq_no, $req_id, $title, $category, $budget, $deadline, $specs, $admin_user);
            if ($stmt->execute()) {
                $flash = "RFQ $rfq_no published successfully.";
            } else {
                $db_error = "Failed to publish RFQ: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'add_bid') {
        $rfq_id = (int)$_POST['rfq_id'];
        $vendor_name = trim($_POST['vendor_name'] ?? '');
        $quote_amount = (float)$_POST['quote_amount'];
        $lead_days = (int)$_POST['lead_time_days'];
        $notes = trim($_POST['proposal_notes'] ?? '');

        if ($rfq_id <= 0 || $vendor_name === '' || $quote_amount <= 0) {
            $db_error = "Valid RFQ, Vendor Name, and Quote Amount are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO rfq_bids (rfq_id, vendor_name, quote_amount, lead_time_days, proposal_notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isdis", $rfq_id, $vendor_name, $quote_amount, $lead_days, $notes);
            if ($stmt->execute()) {
                $flash = "Bid submitted for RFQ #$rfq_id.";
            } else {
                $db_error = "Failed to submit bid: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'select_winner') {
        $bid_id = (int)$_POST['bid_id'];
        $rfq_id = (int)$_POST['rfq_id'];

        $conn->begin_transaction();
        try {
            $conn->query("UPDATE rfq_bids SET is_selected = 0 WHERE rfq_id = $rfq_id");
            $conn->query("UPDATE rfq_bids SET is_selected = 1 WHERE bid_id = $bid_id");
            $conn->query("UPDATE rfqs SET status = 'Awarded' WHERE rfq_id = $rfq_id");
            $conn->commit();
            $flash = "Bid selected and RFQ awarded.";
        } catch (Exception $e) {
            $conn->rollback();
            $db_error = "Failed to award RFQ: " . $e->getMessage();
        }
    }
}

$rfqs = [];
$approved_requisitions = [];

if (!$conn->connect_error) {
    $r_res = $conn->query("SELECT requisition_id, requisition_number, item_description FROM purchase_requisitions WHERE status = 'Approved' ORDER BY created_at DESC");
    if ($r_res) while ($r = $r_res->fetch_assoc()) $approved_requisitions[] = $r;

    $sql = "SELECT r.*, pr.requisition_number,
            (SELECT COUNT(*) FROM rfq_bids b WHERE b.rfq_id = r.rfq_id) AS bid_count,
            (SELECT MIN(quote_amount) FROM rfq_bids b WHERE b.rfq_id = r.rfq_id) AS lowest_bid
            FROM rfqs r
            LEFT JOIN purchase_requisitions pr ON r.requisition_id = pr.requisition_id
            ORDER BY r.created_at DESC";
    $res = $conn->query($sql);
    if ($res) while ($r = $res->fetch_assoc()) $rfqs[] = $r;
} else {
    $db_error = "Database offline.";
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — RFQs</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 1rem; width: 100%; max-width: 34rem; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
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
                <span class="text-slate-600 text-sm font-medium">RFQs & Tenders</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Request for Quotations (RFQs)</h1>
                        <p class="text-slate-500 text-sm mt-1">Publish tender inquiries, gather competitive supplier bids, and award purchase quotes.</p>
                    </div>
                    <button type="button" onclick="openRfqModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">post_add</span> Publish RFQ
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">RFQ Number</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Title & Category</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Budget Limit</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Deadline</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Bids Received</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($rfqs)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No active RFQs published.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rfqs as $rfq): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-mono font-bold text-primary"><?php echo htmlspecialchars($rfq['rfq_number']); ?></td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($rfq['title']); ?></div>
                                                <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($rfq['category']); ?> &bull; Ref: <?php echo htmlspecialchars($rfq['requisition_number'] ?: 'Direct RFQ'); ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-semibold text-slate-800">&#8369;<?php echo number_format($rfq['budget_limit'], 2); ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?php echo date('M j, Y', strtotime($rfq['deadline'])); ?></td>
                                            <td class="px-4 py-3 text-right font-mono">
                                                <span class="font-bold text-slate-900"><?php echo $rfq['bid_count']; ?> bids</span>
                                                <?php if ($rfq['lowest_bid']): ?>
                                                    <div class="text-[10px] text-emerald-600 font-semibold">Low: &#8369;<?php echo number_format($rfq['lowest_bid'], 2); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border <?php echo $rfq['status'] === 'Awarded' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200'; ?>">
                                                    <?php echo htmlspecialchars($rfq['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <?php if ($rfq['status'] === 'Open'): ?>
                                                    <button type="button" onclick="openBidModal(<?php echo $rfq['rfq_id']; ?>, '<?php echo htmlspecialchars($rfq['rfq_number'], ENT_QUOTES); ?>')" class="px-2.5 py-1 bg-primary text-white rounded text-[11px] font-bold">
                                                        Add Quote
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 font-semibold">Closed</span>
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

    <!-- Create RFQ Modal -->
    <div id="rfq-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Publish Request for Quotation</h3>
                <button type="button" onclick="closeRfqModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_rfq"/>
                <div class="form-field">
                    <label>Link Approved Requisition (Optional)</label>
                    <select name="requisition_id">
                        <option value="">Direct RFQ (No Requisition)</option>
                        <?php foreach ($approved_requisitions as $ar): ?>
                            <option value="<?php echo $ar['requisition_id']; ?>"><?php echo htmlspecialchars($ar['requisition_number'] . ' — ' . $ar['item_description']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label>RFQ Title</label>
                    <input type="text" name="title" placeholder="e.g. Procurement of 15x Barcode Scanners" required/>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Category</label>
                        <input type="text" name="category" placeholder="Electronics / Supplies" required/>
                    </div>
                    <div class="form-field">
                        <label>Budget Limit (&#8369;)</label>
                        <input type="number" step="0.01" name="budget_limit" min="0" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Submission Deadline</label>
                    <input type="date" name="deadline" required/>
                </div>
                <div class="form-field">
                    <label>Technical Specifications</label>
                    <textarea name="specifications" rows="2" placeholder="Detail deliverables and specs..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeRfqModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Publish Tender</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Bid Modal -->
    <div id="bid-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Record Vendor Proposal: <span id="bid-rfq-label"></span></h3>
                <button type="button" onclick="closeBidModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="add_bid"/>
                <input type="hidden" name="rfq_id" id="bid-rfq-id"/>
                <div class="form-field">
                    <label>Vendor / Supplier Name</label>
                    <input type="text" name="vendor_name" placeholder="Supplier Company Name" required/>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Quoted Amount (&#8369;)</label>
                        <input type="number" step="0.01" name="quote_amount" min="0" required/>
                    </div>
                    <div class="form-field">
                        <label>Lead Time (Days)</label>
                        <input type="number" name="lead_time_days" min="1" value="7" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Proposal Notes / Terms</label>
                    <textarea name="proposal_notes" rows="2" placeholder="Warranty, payment terms..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeBidModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Submit Bid</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRfqModal() { document.getElementById('rfq-modal').style.display = 'flex'; }
        function closeRfqModal() { document.getElementById('rfq-modal').style.display = 'none'; }
        function openBidModal(rfqId, rfqNo) {
            document.getElementById('bid-rfq-id').value = rfqId;
            document.getElementById('bid-rfq-label').textContent = rfqNo;
            document.getElementById('bid-modal').style.display = 'flex';
        }
        function closeBidModal() { document.getElementById('bid-modal').style.display = 'none'; }
    </script>
</body>
</html>