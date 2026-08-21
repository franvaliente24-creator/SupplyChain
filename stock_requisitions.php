<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Stock Requisition Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Check if stock_requisitions table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'stock_requisitions'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}
$check_table->free();

if ($table_exists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_requisition') {
        $requisition_number = 'REQ-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT INTO stock_requisitions (requisition_number, requested_by, department, item_id, quantity_requested, request_date, approval_status, notes) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)");
        
        $item_id = !empty($_POST['item_id']) ? (int)$_POST['item_id'] : null;
        
        $stmt->bind_param("sisiiss", 
            $requisition_number,
            (int)$_SESSION['user_id'],
            $_POST['department'],
            $item_id,
            (int)$_POST['quantity_requested'],
            $_POST['request_date'],
            $_POST['notes']
        );

        if ($stmt->execute()) {
            $flash = "Stock requisition created successfully: $requisition_number";
            $log_msg = "Created stock requisition: $requisition_number";
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'Stock Requisition', '$log_msg')");
        } else {
            $db_error = "Failed to create requisition: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'approve_requisition') {
        $req_id = (int)$_POST['requisition_id'];
        $quantity_approved = (int)$_POST['quantity_approved'];
        
        // Get requisition details
        $req_stmt = $conn->prepare("SELECT * FROM stock_requisitions WHERE requisition_id = ?");
        $req_stmt->bind_param("i", $req_id);
        $req_stmt->execute();
        $requisition = $req_stmt->get_result()->fetch_assoc();
        $req_stmt->close();
        
        if ($requisition) {
            $conn->begin_transaction();
            try {
                // Update requisition
                $update_stmt = $conn->prepare("UPDATE stock_requisitions SET approval_status = 'Approved', quantity_approved = ?, approved_by = ?, approval_date = CURDATE() WHERE requisition_id = ?");
                $update_stmt->bind_param("iii", $quantity_approved, (int)$_SESSION['user_id'], $req_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Create inventory transaction
                if ($requisition['item_id']) {
                    $trans_stmt = $conn->prepare("INSERT INTO inventory_transactions (item_id, transaction_type, quantity_change, previous_quantity, new_quantity, performed_by, reason) VALUES (?, 'Requisition', ?, ?, ?, ?, ?)");
                    
                    // Get current quantity
                    $item_stmt = $conn->prepare("SELECT quantity FROM inventory_items WHERE item_id = ?");
                    $item_stmt->bind_param("i", $requisition['item_id']);
                    $item_stmt->execute();
                    $current_qty = $item_stmt->get_result()->fetch_assoc()['quantity'] ?? 0;
                    $item_stmt->close();
                    
                    $new_qty = max(0, $current_qty - $quantity_approved);
                    
                    $trans_stmt->bind_param("iiiis", $requisition['item_id'], $quantity_approved, $current_qty, $new_qty, (int)$_SESSION['user_id'], "Stock requisition: " . $requisition['requisition_number']);
                    $trans_stmt->execute();
                    $trans_stmt->close();
                    
                    // Update inventory quantity
                    $inv_update = $conn->prepare("UPDATE inventory_items SET quantity = ? WHERE item_id = ?");
                    $inv_update->bind_param("ii", $new_qty, $requisition['item_id']);
                    $inv_update->execute();
                    $inv_update->close();
                }
                
                $conn->commit();
                $flash = "Stock requisition approved and inventory updated.";
            } catch (Exception $e) {
                $conn->rollback();
                $db_error = "Failed to approve requisition: " . $e->getMessage();
            }
        } else {
            $db_error = "Requisition not found.";
        }
    }

    if ($action === 'reject_requisition') {
        $req_id = (int)$_POST['requisition_id'];
        $stmt = $conn->prepare("UPDATE stock_requisitions SET approval_status = 'Rejected' WHERE requisition_id = ?");
        $stmt->bind_param("i", $req_id);
        
        if ($stmt->execute()) {
            $flash = "Stock requisition rejected.";
        } else {
            $db_error = "Failed to reject requisition: " . $stmt->error;
        }
        $stmt->close();
    }
}

$requisitions = [];
$inventory_items = [];

if ($table_exists && !$conn->connect_error) {
    $sql = "SELECT sr.*, ii.item_name, ii.sku, u.username as requested_by_name 
            FROM stock_requisitions sr 
            LEFT JOIN inventory_items ii ON sr.item_id = ii.item_id 
            LEFT JOIN users u ON sr.requested_by = u.user_id 
            ORDER BY sr.request_date DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $requisitions[] = $row;
        }
    }
    
    // Get available inventory items
    $items_result = $conn->query("SELECT item_id, sku, item_name, quantity FROM inventory_items WHERE status != 'Archived' ORDER BY item_name");
    if ($items_result) {
        while ($row = $items_result->fetch_assoc()) {
            $inventory_items[] = $row;
        }
    }
} else {
    if (!$table_exists) {
        $db_error = "Stock requisitions table not found. Please run the schema_updates.sql file to create it.";
    } else {
        $db_error = "Database connection offline.";
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Approved': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Pending': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Rejected': return 'bg-red-100 text-red-800 border-red-200';
        case 'Partially Fulfilled': return 'bg-blue-100 text-blue-800 border-blue-200';
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
            background: #fff; border-radius: 1rem; width: 100%; max-width: 36rem;
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
                <span class="font-bold text-slate-800 text-sm">Inventory Management System</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Stock Requisitions</h1>
                        <p class="text-slate-500 text-sm mt-1">Internal stock requests and approval workflow for departmental equipment needs.</p>
                    </div>
                    <?php if ($table_exists): ?>
                        <button type="button" onclick="openCreateModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">add_circle</span> New Requisition
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
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Requisition #</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Department</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Requested Item</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Requested By</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($requisitions)): ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-10 text-center text-slate-400">No stock requisitions found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($requisitions as $req): 
                                            $status_badge = getStatusBadgeClass($req['approval_status']);
                                        ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3 font-mono font-semibold text-slate-900"><?php echo htmlspecialchars($req['requisition_number']); ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($req['department']); ?></td>
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($req['item_name'] ?: 'Custom Request'); ?></div>
                                                    <?php if ($req['sku']): ?>
                                                        <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($req['sku']); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="font-semibold"><?php echo number_format($req['quantity_requested']); ?></span>
                                                    <?php if ($req['quantity_approved']): ?>
                                                        <span class="text-slate-400">/ <?php echo number_format($req['quantity_approved']); ?> approved</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($req['requested_by_name']); ?></td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y', strtotime($req['request_date'])); ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $status_badge; ?>">
                                                        <?php echo htmlspecialchars($req['approval_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                    <?php if ($req['approval_status'] === 'Pending'): ?>
                                                        <div class="inline-flex gap-1">
                                                            <button type="button" onclick='openApproveModal(<?php echo json_encode($req); ?>)' class="px-2 py-1 text-[11px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded font-semibold">
                                                                Approve
                                                            </button>
                                                            <form method="post" style="display:inline;">
                                                                <input type="hidden" name="action" value="reject_requisition"/>
                                                                <input type="hidden" name="requisition_id" value="<?php echo (int)$req['requisition_id']; ?>"/>
                                                                <button type="submit" class="px-2 py-1 text-[11px] bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 rounded font-semibold">
                                                                    Reject
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-[10px] text-slate-400">Completed</span>
                                                    <?php endif; ?>
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
                        <p><strong>Stock requisitions are not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Create Requisition Modal -->
    <div id="create-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Create Stock Requisition</h3>
                <button type="button" onclick="closeCreateModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_requisition"/>

                <div class="form-field">
                    <label>Department</label>
                    <input type="text" name="department" placeholder="IT Department" required/>
                </div>

                <div class="form-field">
                    <label>Inventory Item</label>
                    <select name="item_id">
                        <option value="">Custom / Other Item</option>
                        <?php foreach ($inventory_items as $item): ?>
                            <option value="<?php echo $item['item_id']; ?>">
                                <?php echo htmlspecialchars($item['item_name']); ?> (<?php echo htmlspecialchars($item['sku']); ?>) - <?php echo $item['quantity']; ?> available
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Quantity Requested</label>
                    <input type="number" name="quantity_requested" min="1" required/>
                </div>

                <div class="form-field">
                    <label>Request Date</label>
                    <input type="date" name="request_date" value="<?php echo date('Y-m-d'); ?>" required/>
                </div>

                <div class="form-field">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Reason for request, urgent needs, etc."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Submit Requisition</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approve-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Approve Requisition</h3>
                <button type="button" onclick="closeApproveModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="approve_requisition"/>
                <input type="hidden" name="requisition_id" id="approve-req-id" value=""/>

                <div class="bg-slate-50 p-3 rounded-lg mb-3">
                    <p class="text-sm"><strong>Requisition:</strong> <span id="approve-req-number"></span></p>
                    <p class="text-sm"><strong>Department:</strong> <span id="approve-department"></span></p>
                    <p class="text-sm"><strong>Requested:</strong> <span id="approve-quantity"></span></p>
                </div>

                <div class="form-field">
                    <label>Quantity to Approve</label>
                    <input type="number" name="quantity_approved" id="approve-qty" min="1" required/>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeApproveModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Approve & Update Stock</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('create-modal').style.display = 'flex';
        }

        function closeCreateModal() {
            document.getElementById('create-modal').style.display = 'none';
        }

        function openApproveModal(req) {
            document.getElementById('approve-req-id').value = req.requisition_id;
            document.getElementById('approve-req-number').textContent = req.requisition_number;
            document.getElementById('approve-department').textContent = req.department;
            document.getElementById('approve-quantity').textContent = req.quantity_requested;
            document.getElementById('approve-qty').value = req.quantity_requested;
            document.getElementById('approve-modal').style.display = 'flex';
        }

        function closeApproveModal() {
            document.getElementById('approve-modal').style.display = 'none';
        }
    </script>
</body>
</html>