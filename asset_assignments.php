<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Equipment-to-Candidate Matching";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Check if required tables exist
$assets_table_exists = false;
$assignments_table_exists = false;

$check_assets = $conn->query("SHOW TABLES LIKE 'tech_assets'");
if ($check_assets && $check_assets->num_rows > 0) {
    $assets_table_exists = true;
}
$check_assets->free();

$check_assignments = $conn->query("SHOW TABLES LIKE 'asset_assignments'");
if ($check_assignments && $check_assignments->num_rows > 0) {
    $assignments_table_exists = true;
}
$check_assignments->free();

if ($assets_table_exists && $assignments_table_exists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign_asset') {
        $stmt = $conn->prepare("INSERT INTO asset_assignments (asset_id, candidate_name, client_name, assigned_date, expected_return_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
        
        $expected_return = !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : null;
        
        $stmt->bind_param("issss", 
            (int)$_POST['asset_id'],
            $_POST['candidate_name'],
            $_POST['client_name'],
            $_POST['assigned_date'],
            $expected_return,
            $_POST['notes']
        );

        if ($stmt->execute()) {
            // Update asset status to Deployed
            $update_stmt = $conn->prepare("UPDATE tech_assets SET current_status = 'Deployed', assigned_to = ?, client_name = ? WHERE asset_id = ?");
            $update_stmt->bind_param("ssi", $_POST['candidate_name'], $_POST['client_name'], (int)$_POST['asset_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            $flash = "Asset assigned to candidate successfully.";
            $log_msg = "Assigned asset to candidate: " . $_POST['candidate_name'];
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'Asset Assignment', '$log_msg')");
        } else {
            $db_error = "Failed to assign asset: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'return_asset') {
        $assignment_id = (int)$_POST['assignment_id'];
        $return_condition = $_POST['return_condition'];
        $notes = $_POST['notes'] ?? '';
        
        // Get assignment details
        $assign_stmt = $conn->prepare("SELECT asset_id, candidate_name FROM asset_assignments WHERE assignment_id = ?");
        $assign_stmt->bind_param("i", $assignment_id);
        $assign_stmt->execute();
        $assignment = $assign_stmt->get_result()->fetch_assoc();
        $assign_stmt->close();
        
        if ($assignment) {
            $asset_id = $assignment['asset_id'];
            
            // Update assignment with return details
            $update_stmt = $conn->prepare("UPDATE asset_assignments SET actual_return_date = CURDATE(), return_condition = ?, notes = ? WHERE assignment_id = ?");
            $update_stmt->bind_param("ssi", $return_condition, $notes, $assignment_id);
            
            if ($update_stmt->execute()) {
                // Update asset status back to In Storage
                $asset_update = $conn->prepare("UPDATE tech_assets SET current_status = 'In Storage', assigned_to = NULL, client_name = NULL, condition_status = ? WHERE asset_id = ?");
                $asset_update->bind_param("si", $return_condition, $asset_id);
                $asset_update->execute();
                $asset_update->close();
                
                // Log condition change if different
                $current = $conn->query("SELECT condition_status FROM tech_assets WHERE asset_id = $asset_id")->fetch_assoc();
                $old_condition = $current['condition_status'] ?? '';
                
                if ($old_condition !== $return_condition) {
                    $log_stmt = $conn->prepare("INSERT INTO asset_condition_history (asset_id, old_condition, new_condition, changed_by, change_reason) VALUES (?, ?, ?, ?, ?)");
                    $reason = "Asset returned from " . $assignment['candidate_name'];
                    $log_stmt->bind_param("issis", $asset_id, $old_condition, $return_condition, $_SESSION['user_id'], $reason);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
                
                $flash = "Asset returned successfully and condition updated.";
            } else {
                $db_error = "Failed to process return: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $db_error = "Assignment not found.";
        }
    }
}

$assignments = [];
$available_assets = [];

if ($assets_table_exists && $assignments_table_exists && !$conn->connect_error) {
    // Get current assignments
    $sql = "SELECT a.*, ta.asset_name, ta.asset_type, ta.qr_code, ta.condition_status 
            FROM asset_assignments a 
            JOIN tech_assets ta ON a.asset_id = ta.asset_id 
            WHERE a.actual_return_date IS NULL 
            ORDER BY a.assigned_date DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
    }
    
    // Get available assets (not currently deployed)
    $avail_sql = "SELECT * FROM tech_assets WHERE current_status = 'In Storage' ORDER BY asset_name ASC";
    $avail_result = $conn->query($avail_sql);
    if ($avail_result) {
        while ($row = $avail_result->fetch_assoc()) {
            $available_assets[] = $row;
        }
    }
    
    // Get return history
    $return_history = [];
    $history_sql = "SELECT a.*, ta.asset_name, ta.asset_type 
                   FROM asset_assignments a 
                   JOIN tech_assets ta ON a.asset_id = ta.asset_id 
                   WHERE a.actual_return_date IS NOT NULL 
                   ORDER BY a.actual_return_date DESC LIMIT 20";
    $history_result = $conn->query($history_sql);
    if ($history_result) {
        while ($row = $history_result->fetch_assoc()) {
            $return_history[] = $row;
        }
    }
} else {
    if (!$assets_table_exists || !$assignments_table_exists) {
        $db_error = "Required tables not found. Please run the schema_updates.sql file to create them.";
    } else {
        $db_error = "Database connection offline.";
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
                <span class="font-bold text-slate-800 text-sm">Smart Warehousing System</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Equipment-to-Candidate Matching</h1>
                        <p class="s text-slate-500 text-sm mt-1">Assign tech kits to new hires and manage equipment deployment.</p>
                    </div>
                    <?php if ($assets_table_exists && $assignments_table_exists): ?>
                        <button type="button" onclick="openAssignModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">person_add</span> Assign Equipment
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <?php if ($assets_table_exists && $assignments_table_exists): ?>
                    <!-- Current Assignments -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-sm font-bold text-slate-900">Current Equipment Deployments</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Candidate</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Client</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Equipment</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Assigned</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Expected Return</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($assignments)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">No current equipment deployments.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($assignment['candidate_name']); ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($assignment['client_name'] ?: '—'); ?></td>
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($assignment['asset_name']); ?></div>
                                                    <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($assignment['asset_type']); ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y', strtotime($assignment['assigned_date'])); ?></td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo $assignment['expected_return_date'] ? date('M j, Y', strtotime($assignment['expected_return_date'])) : '—'; ?></td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                    <button type="button" onclick='openReturnModal(<?php echo json_encode($assignment); ?>)' class="px-2 py-1 text-[11px] bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 rounded font-semibold">
                                                        Return Equipment
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Return History -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200">
                            <h2 class="text-sm font-bold text-slate-900">Recent Equipment Returns</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Candidate</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Equipment</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Return Date</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Condition</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($return_history)): ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-10 text-center text-slate-400">No equipment returns recorded yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($return_history as $history): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($history['candidate_name']); ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($history['asset_name']); ?></td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y', strtotime($history['actual_return_date'])); ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border bg-slate-100 text-slate-800 border-slate-200">
                                                        <?php echo htmlspecialchars($history['return_condition']); ?>
                                                    </span>
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
                        <p><strong>Equipment assignment is not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Assignment Modal -->
    <div id="assign-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Assign Equipment to Candidate</h3>
                <button type="button" onclick="closeAssignModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="assign_asset"/>

                <div class="form-field">
                    <label>Select Equipment</label>
                    <select name="asset_id" required>
                        <option value="">Choose available equipment...</option>
                        <?php foreach ($available_assets as $asset): ?>
                            <option value="<?php echo $asset['asset_id']; ?>">
                                <?php echo htmlspecialchars($asset['asset_name']); ?> (<?php echo htmlspecialchars($asset['asset_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Candidate Name</label>
                    <input type="text" name="candidate_name" placeholder="John Doe" required/>
                </div>

                <div class="form-field">
                    <label>Client/Company Name</label>
                    <input type="text" name="client_name" placeholder="Acme Corporation"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Assignment Date</label>
                        <input type="date" name="assigned_date" value="<?php echo date('Y-m-d'); ?>" required/>
                    </div>
                    <div class="form-field">
                        <label>Expected Return Date</label>
                        <input type="date" name="expected_return_date"/>
                    </div>
                </div>

                <div class="form-field">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any special instructions..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAssignModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Assign Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="return-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Process Equipment Return</h3>
                <button type="button" onclick="closeReturnModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="return_asset"/>
                <input type="hidden" name="assignment_id" id="return-assignment-id" value=""/>

                <div class="bg-slate-50 p-3 rounded-lg mb-3">
                    <p class="text-sm"><strong>Candidate:</strong> <span id="return-candidate-name"></span></p>
                    <p class="text-sm"><strong>Equipment:</strong> <span id="return-asset-name"></span></p>
                </div>

                <div class="form-field">
                    <label>Equipment Condition on Return</label>
                    <select name="return_condition" required>
                        <option value="Brand New">Brand New</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Defective">Defective</option>
                        <option value="Repaired">Repaired</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Return Notes</label>
                    <textarea name="notes" rows="2" placeholder="Any observations about the returned equipment..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeReturnModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Process Return</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal() {
            document.getElementById('assign-modal').style.display = 'flex';
        }

        function closeAssignModal() {
            document.getElementById('assign-modal').style.display = 'none';
        }

        function openReturnModal(assignment) {
            document.getElementById('return-assignment-id').value = assignment.assignment_id;
            document.getElementById('return-candidate-name').textContent = assignment.candidate_name;
            document.getElementById('return-asset-name').textContent = assignment.asset_name;
            document.getElementById('return-modal').style.display = 'flex';
        }

        function closeReturnModal() {
            document.getElementById('return-modal').style.display = 'none';
        }
    </script>
</body>
</html>