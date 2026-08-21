<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Request for Proposal (RFP) Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Check if rfps table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'rfps'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}
$check_table->free();

if ($table_exists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_rfp') {
        $rfp_number = 'RFP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO rfps (rfp_number, title, description, category, budget_limit, deadline, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Draft')");
        
        $budget_limit = !empty($_POST['budget_limit']) ? (float)$_POST['budget_limit'] : null;
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        
        $stmt->bind_param("ssssdsi", 
            $rfp_number,
            $_POST['title'],
            $_POST['description'],
            $_POST['category'],
            $budget_limit,
            $deadline,
            (int)$_SESSION['user_id']
        );

        if ($stmt->execute()) {
            $flash = "RFP created successfully: $rfp_number";
            $log_msg = "Created RFP: $rfp_number - " . $_POST['title'];
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'RFP Creation', '$log_msg')");
        } else {
            $db_error = "Failed to create RFP: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'publish_rfp') {
        $rfp_id = (int)$_POST['rfp_id'];
        $stmt = $conn->prepare("UPDATE rfps SET status = 'Published' WHERE rfp_id = ?");
        $stmt->bind_param("i", $rfp_id);
        
        if ($stmt->execute()) {
            $flash = "RFP published and visible to suppliers.";
        } else {
            $db_error = "Failed to publish RFP: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'close_rfp') {
        $rfp_id = (int)$_POST['rfp_id'];
        $stmt = $conn->prepare("UPDATE rfps SET status = 'Closed' WHERE rfp_id = ?");
        $stmt->bind_param("i", $rfp_id);
        
        if ($stmt->execute()) {
            $flash = "RFP closed and no longer accepting responses.";
        } else {
            $db_error = "Failed to close RFP: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'submit_response') {
        $stmt = $conn->prepare("INSERT INTO rfp_responses (rfp_id, supplier_id, quote_amount, proposal_document, submitted_date, status) VALUES (?, ?, ?, ?, CURDATE(), 'Submitted')");
        
        $quote_amount = !empty($_POST['quote_amount']) ? (float)$_POST['quote_amount'] : null;
        
        $stmt->bind_param("iids", 
            (int)$_POST['rfp_id'],
            (int)$_POST['supplier_id'],
            $quote_amount,
            $_POST['proposal_document']
        );

        if ($stmt->execute()) {
            $flash = "RFP response submitted successfully.";
        } else {
            $db_error = "Failed to submit response: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'accept_response') {
        $response_id = (int)$_POST['response_id'];
        $rfp_id = (int)$_POST['rfp_id'];
        
        $conn->begin_transaction();
        try {
            // Accept the response
            $accept_stmt = $conn->prepare("UPDATE rfp_responses SET status = 'Accepted' WHERE response_id = ?");
            $accept_stmt->bind_param("i", $response_id);
            $accept_stmt->execute();
            $accept_stmt->close();
            
            // Reject other responses for this RFP
            $reject_stmt = $conn->prepare("UPDATE rfp_responses SET status = 'Rejected' WHERE rfp_id = ? AND response_id != ?");
            $reject_stmt->bind_param("ii", $rfp_id, $response_id);
            $reject_stmt->execute();
            $reject_stmt->close();
            
            // Mark RFP as awarded
            $award_stmt = $conn->prepare("UPDATE rfps SET status = 'Awarded' WHERE rfp_id = ?");
            $award_stmt->bind_param("i", $rfp_id);
            $award_stmt->execute();
            $award_stmt->close();
            
            $conn->commit();
            $flash = "RFP response accepted and other responses rejected.";
        } catch (Exception $e) {
            $conn->rollback();
            $db_error = "Failed to accept response: " . $e->getMessage();
        }
    }
}

$rfps = [];
$suppliers = [];

if ($table_exists && !$conn->connect_error) {
    $sql = "SELECT r.*, u.username as created_by_name 
            FROM rfps r 
            LEFT JOIN users u ON r.created_by = u.user_id 
            ORDER BY r.created_at DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rfps[] = $row;
        }
    }
    
    // Get supplier responses for each RFP
    foreach ($rfps as &$rfp) {
        $resp_stmt = $conn->prepare("SELECT rr.*, s.supplier_name 
                                      FROM rfp_responses rr 
                                      LEFT JOIN suppliers s ON rr.supplier_id = s.supplier_id 
                                      WHERE rr.rfp_id = ?");
        $resp_stmt->bind_param("i", $rfp['rfp_id']);
        $resp_stmt->execute();
        $resp_result = $resp_stmt->get_result();
        $responses = [];
        while ($row = $resp_result->fetch_assoc()) {
            $responses[] = $row;
        }
        $rfp['responses'] = $responses;
        $resp_stmt->close();
    }
    unset($rfp);
    
    // Get available suppliers
    $sup_result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    if ($sup_result) {
        while ($row = $sup_result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
} else {
    if (!$table_exists) {
        $db_error = "RFP tables not found. Please run the schema_updates.sql file to create them.";
    } else {
        $db_error = "Database connection offline.";
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Published': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Draft': return 'bg-slate-100 text-slate-800 border-slate-200';
        case 'Closed': return 'bg-red-100 text-red-800 border-red-200';
        case 'Awarded': return 'bg-blue-100 text-blue-800 border-blue-200';
        default: return 'bg-slate-100 text-slate-800 border-slate-200';
    }
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
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 100; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 1rem; width: 100%; max-width: 40rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-height: 90vh; overflow-y: auto;
        }
        .form-field { margin-bottom: 1rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select, .form-field textarea {
            width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Procurement & Sourcing Management</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Request for Proposals (RFP)</h1>
                        <p class="text-slate-500 text-sm mt-1">Create and manage RFPs for sourcing new vendors and competitive bidding.</p>
                    </div>
                    <?php if ($table_exists): ?>
                        <button type="button" onclick="openCreateModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">description</span> Create RFP
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <?php if ($table_exists): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">RFP #</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Title</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Category</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Budget</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Deadline</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Responses</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($rfps)): ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-10 text-center text-slate-400">No RFPs found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($rfps as $rfp): 
                                            $status_badge = getStatusBadgeClass($rfp['status']);
                                            $response_count = count($rfp['responses']);
                                        ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3 font-mono font-semibold text-slate-900"><?php echo htmlspecialchars($rfp['rfp_number']); ?></td>
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($rfp['title']); ?></div>
                                                    <div class="text-[10px] text-slate-400">By: <?php echo htmlspecialchars($rfp['created_by_name']); ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($rfp['category'] ?: '—'); ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo $rfp['budget_limit'] ? '₱' . number_format($rfp['budget_limit'], 2) : '—'; ?></td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo $rfp['deadline'] ? date('M j, Y', strtotime($rfp['deadline'])) : '—'; ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="font-semibold"><?php echo $response_count; ?></span>
                                                    <?php if ($response_count > 0): ?>
                                                        <button type="button" onclick='viewResponses(<?php echo json_encode($rfp); ?>)' class="text-blue-600 hover:underline text-xs ml-1">View</button>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $status_badge; ?>">
                                                        <?php echo htmlspecialchars($rfp['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                    <div class="inline-flex gap-1">
                                                        <?php if ($rfp['status'] === 'Draft'): ?>
                                                            <form method="post" style="display:inline;">
                                                                <input type="hidden" name="action" value="publish_rfp"/>
                                                                <input type="hidden" name="rfp_id" value="<?php echo (int)$rfp['rfp_id']; ?>"/>
                                                                <button type="submit" class="px-2 py-1 text-[11px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded font-semibold">
                                                                    Publish
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if ($rfp['status'] === 'Published'): ?>
                                                            <form method="post" style="display:inline;">
                                                                <input type="hidden" name="action" value="close_rfp"/>
                                                                <input type="hidden" name="rfp_id" value="<?php echo (int)$rfp['rfp_id']; ?>"/>
                                                                <button type="submit" class="px-2 py-1 text-[11px] bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 rounded font-semibold">
                                                                    Close
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <button type="button" onclick='openResponseModal(<?php echo (int)$rfp['rfp_id']; ?>)' class="px-2 py-1 text-[11px] bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded font-semibold">
                                                            Add Response
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3 rounded-lg">
                        <p><strong>RFP management is not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Create RFP Modal -->
    <div id="create-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Create Request for Proposal</h3>
                <button type="button" onclick="closeCreateModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_rfp"/>

                <div class="form-field">
                    <label>RFP Title</label>
                    <input type="text" name="title" placeholder="Office Equipment Procurement - Q4 2026" required/>
                </div>

                <div class="form-field">
                    <label>Category</label>
                    <select name="category">
                        <option value="IT Equipment">IT Equipment</option>
                        <option value="Office Supplies">Office Supplies</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Services">Services</option>
                        <option value="Software">Software Licenses</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Detailed description of requirements..." required></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Budget Limit (₱)</label>
                        <input type="number" step="0.01" name="budget_limit" placeholder="500000.00"/>
                    </div>
                    <div class="form-field">
                        <label>Submission Deadline</label>
                        <input type="date" name="deadline"/>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Create RFP</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="response-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Submit Supplier Response</h3>
                <button type="button" onclick="closeResponseModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="submit_response"/>
                <input type="hidden" name="rfp_id" id="response-rfp-id" value=""/>

                <div class="form-field">
                    <label>Supplier</label>
                    <select name="supplier_id" required>
                        <option value="">Select supplier...</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?php echo $sup['supplier_id']; ?>"><?php echo htmlspecialchars($sup['supplier_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Quote Amount (₱)</label>
                    <input type="number" step="0.01" name="quote_amount" placeholder="250000.00"/>
                </div>

                <div class="form-field">
                    <label>Proposal Document/Details</label>
                    <textarea name="proposal_document" rows="3" placeholder="Paste proposal details or document reference..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeResponseModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Submit Response</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Responses Modal -->
    <div id="responses-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">RFP Responses</h3>
                <button type="button" onclick="closeResponsesModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <div id="responses-content" class="space-y-3">
                <!-- Responses will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('create-modal').style.display = 'flex';
        }

        function closeCreateModal() {
            document.getElementById('create-modal').style.display = 'none';
        }

        function openResponseModal(rfpId) {
            document.getElementById('response-rfp-id').value = rfpId;
            document.getElementById('response-modal').style.display = 'flex';
        }

        function closeResponseModal() {
            document.getElementById('response-modal').style.display = 'none';
        }

        function viewResponses(rfp) {
            const content = document.getElementById('responses-content');
            if (rfp.responses.length === 0) {
                content.innerHTML = '<p class="text-slate-500 text-center py-4">No responses submitted yet.</p>';
            } else {
                let html = '';
                rfp.responses.forEach(function(resp) {
                    const statusClass = resp.status === 'Accepted' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 
                                      (resp.status === 'Rejected' ? 'bg-red-100 text-red-800 border-red-200' : 
                                      'bg-slate-100 text-slate-800 border-slate-200');
                    
                    html += `
                        <div class="bg-slate-50 p-3 rounded-lg">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-semibold text-slate-900">${resp.supplier_name || 'Unknown Supplier'}</p>
                                    <p class="text-sm text-slate-600">Quote: ₱${resp.quote_amount ? parseFloat(resp.quote_amount).toFixed(2) : 'N/A'}</p>
                                    <p class="text-xs text-slate-400">Submitted: ${new Date(resp.submitted_date).toLocaleDateString()}</p>
                                </div>
                                <div class="flex flex-col gap-1 items-end">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border ${statusClass}">
                                        ${resp.status}
                                    </span>
                                    ${resp.status === 'Submitted' ? `
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="accept_response"/>
                                            <input type="hidden" name="response_id" value="${resp.response_id}"/>
                                            <input type="hidden" name="rfp_id" value="${rfp.rfp_id}"/>
                                            <button type="submit" class="px-2 py-1 text-[10px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded font-semibold">
                                                Accept
                                            </button>
                                        </form>
                                    ` : ''}
                                </div>
                            </div>
                            ${resp.proposal_document ? `<p class="text-xs text-slate-500 mt-2 bg-white p-2 rounded">${resp.proposal_document}</p>` : ''}
                        </div>
                    `;
                });
                content.innerHTML = html;
            }
            document.getElementById('responses-modal').style.display = 'flex';
        }

        function closeResponsesModal() {
            document.getElementById('responses-modal').style.display = 'none';
        }
    </script>
</body>
</html>