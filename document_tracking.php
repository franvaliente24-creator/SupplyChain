<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Document Tracking System";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Check if document_tracking table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'document_tracking'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}
$check_table->free();

if ($table_exists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_tracking') {
        $tracking_number = 'DOC-' . date('Ymd') . '-' . strtoupper(uniqid());
        
        $stmt = $conn->prepare("INSERT INTO document_tracking (tracking_number, document_type, recipient_name, recipient_address, current_status, created_by, expected_delivery_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $expected_delivery = !empty($_POST['expected_delivery_date']) ? $_POST['expected_delivery_date'] : null;
        $initial_status = 'Created';
        $created_by = (int)$_SESSION['user_id'];
        
        $stmt->bind_param("sssssiss", 
            $tracking_number,
            $_POST['document_type'],
            $_POST['recipient_name'],
            $_POST['recipient_address'],
            $initial_status,
            $created_by,
            $expected_delivery,
            $_POST['notes']
        );

        if ($stmt->execute()) {
            $flash = "Document tracking created successfully: $tracking_number";
            $log_msg = "Created document tracking: $tracking_number for " . $_POST['recipient_name'];
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'Document Tracking', '$log_msg')");
        } else {
            $db_error = "Failed to create tracking: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'update_status') {
        $tracking_id = (int)$_POST['tracking_id'];
        $new_status = $_POST['new_status'];
        $event_location = $_POST['event_location'] ?? '';
        $event_notes = $_POST['event_notes'] ?? '';
        
        $conn->begin_transaction();
        try {
            // Update tracking status
            $update_stmt = $conn->prepare("UPDATE document_tracking SET current_status = ? WHERE tracking_id = ?");
            $update_stmt->bind_param("si", $new_status, $tracking_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Log the event
            $event_stmt = $conn->prepare("INSERT INTO delivery_events (tracking_id, event_type, event_location, event_notes, recorded_by) VALUES (?, ?, ?, ?, ?)");
            $event_type = 'Status Update';
            $event_stmt->bind_param("issss", $tracking_id, $event_type, $event_location, $event_notes, (int)$_SESSION['user_id']);
            $event_stmt->execute();
            $event_stmt->close();
            
            $conn->commit();
            $flash = "Document status updated to $new_status.";
        } catch (Exception $e) {
            $conn->rollback();
            $db_error = "Failed to update status: " . $e->getMessage();
        }
    }

    if ($action === 'log_event') {
        $tracking_id = (int)$_POST['tracking_id'];
        $event_type = $_POST['event_type'];
        $event_location = $_POST['event_location'] ?? '';
        $event_notes = $_POST['event_notes'] ?? '';
        
        $conn->begin_transaction();
        try {
            // Log the event
            $event_stmt = $conn->prepare("INSERT INTO delivery_events (tracking_id, event_type, event_location, event_notes, recorded_by) VALUES (?, ?, ?, ?, ?)");
            $event_stmt->bind_param("issss", $tracking_id, $event_type, $event_location, $event_notes, (int)$_SESSION['user_id']);
            $event_stmt->execute();
            $event_stmt->close();
            
            // Update tracking status based on event type
            $status_map = [
                'Out for Delivery' => 'Out for Delivery',
                'In Transit' => 'In Transit',
                'Delivered' => 'Delivered',
                'Failed' => 'Failed',
                'Delayed' => 'Delayed'
            ];
            
            if (isset($status_map[$event_type])) {
                $update_stmt = $conn->prepare("UPDATE document_tracking SET current_status = ? WHERE tracking_id = ?");
                $update_stmt->bind_param("si", $status_map[$event_type], $tracking_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Update actual delivery date if delivered
                if ($event_type === 'Delivered') {
                    $date_stmt = $conn->prepare("UPDATE document_tracking SET actual_delivery_date = CURDATE() WHERE tracking_id = ?");
                    $date_stmt->bind_param("i", $tracking_id);
                    $date_stmt->execute();
                    $date_stmt->close();
                }
            }
            
            $conn->commit();
            $flash = "Delivery event logged successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $db_error = "Failed to log event: " . $e->getMessage();
        }
    }
}

$tracked_documents = [];

if ($table_exists && !$conn->connect_error) {
    $sql = "SELECT dt.*, u.username as created_by_name 
            FROM document_tracking dt 
            LEFT JOIN users u ON dt.created_by = u.user_id 
            ORDER BY dt.created_at DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tracked_documents[] = $row;
        }
    }
} else {
    if (!$table_exists) {
        $db_error = "Document tracking tables not found. Please run the schema_updates.sql file to create them.";
    } else {
        $db_error = "Database connection offline.";
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Created': return 'bg-slate-100 text-slate-800 border-slate-200';
        case 'Out for Delivery': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'In Transit': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'Delivered': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Failed': return 'bg-red-100 text-red-800 border-red-200';
        case 'Delayed': return 'bg-amber-100 text-amber-800 border-amber-200';
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
        <?php include 'header.php'; ?>
<main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Document Tracking System</h1>
                        <p class="text-slate-500 text-sm mt-1">Generate tracking numbers, monitor document delivery status, and manage logistics records.</p>
                    </div>
                    <?php if ($table_exists): ?>
                        <button type="button" onclick="openCreateModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">assignment</span> Create Tracking
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
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Tracking #</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Document Type</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Recipient</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Expected Delivery</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($tracked_documents)): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-10 text-center text-slate-400">No tracked documents found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tracked_documents as $doc): 
                                            $status_badge = getStatusBadgeClass($doc['current_status']);
                                        ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3 font-mono font-semibold text-slate-900"><?php echo htmlspecialchars($doc['tracking_number']); ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($doc['recipient_name']); ?></div>
                                                    <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($doc['recipient_address'] ?: '—'); ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo $doc['expected_delivery_date'] ? date('M j, Y', strtotime($doc['expected_delivery_date'])) : '—'; ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $status_badge; ?>">
                                                        <?php echo htmlspecialchars($doc['current_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                    <div class="inline-flex gap-1">
                                                        <button type="button" onclick='openEventModal(<?php echo json_encode($doc); ?>)' class="px-2 py-1 text-[11px] bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded font-semibold">
                                                            Log Event
                                                        </button>
                                                        <button type="button" onclick='openStatusModal(<?php echo json_encode($doc); ?>)' class="p-1 rounded hover:bg-slate-100 text-slate-600" title="Update Status">
                                                            <span class="material-symbols-outlined text-sm">edit</span>
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
                        <p><strong>Document tracking is not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Create Tracking Modal -->
    <div id="create-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Create Document Tracking</h3>
                <button type="button" onclick="closeCreateModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_tracking"/>

                <div class="form-field">
                    <label>Document Type</label>
                    <select name="document_type" required>
                        <option value="Employment Contract">Employment Contract</option>
                        <option value="Equipment Delivery">Equipment Delivery</option>
                        <option value="Legal Document">Legal Document</option>
                        <option value="Certificate">Certificate</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Recipient Name</label>
                    <input type="text" name="recipient_name" placeholder="John Doe" required/>
                </div>

                <div class="form-field">
                    <label>Recipient Address</label>
                    <textarea name="recipient_address" rows="2" placeholder="Full delivery address..."></textarea>
                </div>

                <div class="form-field">
                    <label>Expected Delivery Date</label>
                    <input type="date" name="expected_delivery_date"/>
                </div>

                <div class="form-field">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Special delivery instructions..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Create Tracking</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Event Logging Modal -->
    <div id="event-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Log Delivery Event</h3>
                <button type="button" onclick="closeEventModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="log_event"/>
                <input type="hidden" name="tracking_id" id="event-tracking-id" value=""/>

                <div class="bg-slate-50 p-3 rounded-lg mb-3">
                    <p class="text-sm"><strong>Tracking:</strong> <span id="event-tracking-number"></span></p>
                    <p class="text-sm"><strong>Recipient:</strong> <span id="event-recipient"></span></p>
                </div>

                <div class="form-field">
                    <label>Event Type</label>
                    <select name="event_type" required>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Failed">Failed Delivery</option>
                        <option value="Delayed">Delayed</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Event Location</label>
                    <input type="text" name="event_location" placeholder="Current location or checkpoint"/>
                </div>

                <div class="form-field">
                    <label>Event Notes</label>
                    <textarea name="event_notes" rows="2" placeholder="Details about the delivery event..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeEventModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Log Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="status-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Update Delivery Status</h3>
                <button type="button" onclick="closeStatusModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="update_status"/>
                <input type="hidden" name="tracking_id" id="status-tracking-id" value=""/>

                <div class="bg-slate-50 p-3 rounded-lg mb-3">
                    <p class="text-sm"><strong>Tracking:</strong> <span id="status-tracking-number"></span></p>
                </div>

                <div class="form-field">
                    <label>New Status</label>
                    <select name="new_status" required>
                        <option value="Created">Created</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Failed">Failed</option>
                        <option value="Delayed">Delayed</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Event Location</label>
                    <input type="text" name="event_location" placeholder="Current location"/>
                </div>

                <div class="form-field">
                    <label>Notes</label>
                    <textarea name="event_notes" rows="2" placeholder="Reason for status change..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Update Status</button>
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

        function openEventModal(doc) {
            document.getElementById('event-tracking-id').value = doc.tracking_id;
            document.getElementById('event-tracking-number').textContent = doc.tracking_number;
            document.getElementById('event-recipient').textContent = doc.recipient_name;
            document.getElementById('event-modal').style.display = 'flex';
        }

        function closeEventModal() {
            document.getElementById('event-modal').style.display = 'none';
        }

        function openStatusModal(doc) {
            document.getElementById('status-tracking-id').value = doc.tracking_id;
            document.getElementById('status-tracking-number').textContent = doc.tracking_number;
            document.getElementById('status-modal').style.display = 'flex';
        }

        function closeStatusModal() {
            document.getElementById('status-modal').style.display = 'none';
        }
    </script>
</body>
</html>