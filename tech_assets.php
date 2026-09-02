<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'sws_connection.php';

$section_title = "Smart Warehousing System - Tech Assets";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Check if tech_assets table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'tech_assets'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}
$check_table->free();

if ($table_exists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_asset') {
        $stmt = $conn->prepare("INSERT INTO tech_assets (asset_name, asset_type, serial_number, qr_code, brand, model, purchase_date, warranty_expiry, condition_status, current_status, zone_location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Generate QR code if not provided
        $qr_code = !empty($_POST['qr_code']) ? $_POST['qr_code'] : 'ASSET-' . strtoupper(uniqid());
        
        $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
        $warranty_expiry = !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null;
        
        $stmt->bind_param("sssssssssss", 
            $_POST['asset_name'],
            $_POST['asset_type'],
            $_POST['serial_number'],
            $qr_code,
            $_POST['brand'],
            $_POST['model'],
            $purchase_date,
            $warranty_expiry,
            $_POST['condition_status'],
            $_POST['current_status'],
            $_POST['zone_location']
        );

        if ($stmt->execute()) {
            $flash = "Tech asset registered successfully with QR code: $qr_code";
            $log_msg = "Registered new tech asset: " . $_POST['asset_name'] . " (" . $_POST['asset_type'] . ")";
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'Asset Registration', '$log_msg')");
        } else {
            $db_error = "Failed to register asset: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'edit_asset') {
        $asset_id = (int)$_POST['asset_id'];
        $stmt = $conn->prepare("UPDATE tech_assets SET asset_name = ?, asset_type = ?, serial_number = ?, brand = ?, model = ?, purchase_date = ?, warranty_expiry = ?, condition_status = ?, current_status = ?, zone_location = ? WHERE asset_id = ?");
        
        $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
        $warranty_expiry = !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null;
        
        $stmt->bind_param("ssssssssssi", 
            $_POST['asset_name'],
            $_POST['asset_type'],
            $_POST['serial_number'],
            $_POST['brand'],
            $_POST['model'],
            $purchase_date,
            $warranty_expiry,
            $_POST['condition_status'],
            $_POST['current_status'],
            $_POST['zone_location'],
            $asset_id
        );

        if ($stmt->execute()) {
            $flash = "Tech asset updated successfully.";
            $log_msg = "Updated tech asset: " . $_POST['asset_name'];
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'Asset Update', '$log_msg')");
        } else {
            $db_error = "Failed to update asset: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'update_condition') {
        $asset_id = (int)$_POST['asset_id'];
        $new_condition = $_POST['new_condition'];
        $change_reason = $_POST['change_reason'] ?? '';
        
        // Get current condition
        $current = $conn->query("SELECT condition_status FROM tech_assets WHERE asset_id = $asset_id")->fetch_assoc();
        $old_condition = $current['condition_status'] ?? '';
        
        // Update asset condition
        $stmt = $conn->prepare("UPDATE tech_assets SET condition_status = ? WHERE asset_id = ?");
        $stmt->bind_param("si", $new_condition, $asset_id);
        
        if ($stmt->execute()) {
            // Log condition change
            $log_stmt = $conn->prepare("INSERT INTO asset_condition_history (asset_id, old_condition, new_condition, changed_by, change_reason) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->bind_param("issis", $asset_id, $old_condition, $new_condition, $_SESSION['user_id'], $change_reason);
            $log_stmt->execute();
            $log_stmt->close();
            
            $flash = "Asset condition updated successfully.";
        } else {
            $db_error = "Failed to update condition: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'delete_asset') {
        $asset_id = (int)$_POST['asset_id'];
        $stmt = $conn->prepare("DELETE FROM tech_assets WHERE asset_id = ?");
        $stmt->bind_param("i", $asset_id);
        
        if ($stmt->execute()) {
            $flash = "Tech asset deleted successfully.";
        } else {
            $db_error = "Failed to delete asset: " . $stmt->error;
        }
        $stmt->close();
    }
}

$assets = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_type = isset($_GET['type']) ? trim($_GET['type']) : '';

if ($table_exists && !$conn->connect_error) {
    $sql = "SELECT * FROM tech_assets WHERE 1=1";
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $sql .= " AND (asset_name LIKE ? OR serial_number LIKE ? OR brand LIKE ? OR model LIKE ?)";
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'ssss';
    }
    
    if ($filter_status !== '') {
        $sql .= " AND current_status = ?";
        $params[] = $filter_status;
        $types .= 's';
    }
    
    if ($filter_type !== '') {
        $sql .= " AND asset_type = ?";
        $params[] = $filter_type;
        $types .= 's';
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $assets[] = $row;
        }
    }
    
    // Get unique asset types for filter
    $types_result = $conn->query("SELECT DISTINCT asset_type FROM tech_assets ORDER BY asset_type");
    $asset_types = [];
    if ($types_result) {
        while ($row = $types_result->fetch_assoc()) {
            $asset_types[] = $row['asset_type'];
        }
    }
} else {
    if (!$table_exists) {
        $db_error = "Tech assets table not found. Please run the schema_updates.sql file to create it.";
    } else {
        $db_error = "Database connection offline.";
    }
}

function getConditionBadgeClass($condition) {
    switch ($condition) {
        case 'Brand New': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Good': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Fair': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Defective': return 'bg-red-100 text-red-800 border-red-200';
        case 'Repaired': return 'bg-purple-100 text-purple-800 border-purple-200';
        default: return 'bg-slate-100 text-slate-800 border-slate-200';
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'In Storage': return 'bg-slate-100 text-slate-800 border-slate-200';
        case 'Deployed': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'In Transit': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Maintenance': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Retired': return 'bg-red-100 text-red-800 border-red-200';
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
                <span class="font-bold text-slate-800 text-sm">Smart Warehousing System</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Tech Infrastructure Assets</h1>
                        <p class="text-slate-500 text-sm mt-1">Register and manage laptops, headsets, webcams, and other tech equipment with QR tracking.</p>
                    </div>
                    <?php if ($table_exists): ?>
                        <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">devices</span> Register New Asset
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
                        <div class="p-4 border-b border-slate-200">
                            <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search assets..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                                </div>
                                <select name="type" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none">
                                    <option value="">All Types</option>
                                    <?php foreach ($asset_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none">
                                    <option value="">All Status</option>
                                    <option value="In Storage" <?php echo $filter_status === 'In Storage' ? 'selected' : ''; ?>>In Storage</option>
                                    <option value="Deployed" <?php echo $filter_status === 'Deployed' ? 'selected' : ''; ?>>Deployed</option>
                                    <option value="In Transit" <?php echo $filter_status === 'In Transit' ? 'selected' : ''; ?>>In Transit</option>
                                    <option value="Maintenance" <?php echo $filter_status === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    <option value="Retired" <?php echo $filter_status === 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                </select>
                                <button type="submit" class="px-3 py-2 bg-primary text-white rounded-lg text-sm font-semibold">Filter</button>
                            </form>
                            <?php if ($search !== '' || $filter_status !== '' || $filter_type !== ''): ?>
                                <a href="tech_assets.php" class="text-xs font-semibold text-slate-500 hover:text-primary mt-2 inline-block">Reset Filters</a>
                            <?php endif; ?>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Asset</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Serial/QR</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Condition</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Location</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($assets)): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-10 text-center text-slate-400">No tech assets found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($assets as $asset): 
                                            $condition_badge = getConditionBadgeClass($asset['condition_status']);
                                            $status_badge = getStatusBadgeClass($asset['current_status']);
                                        ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($asset['asset_name']); ?></div>
                                                    <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($asset['brand'] . ' ' . $asset['model']); ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($asset['asset_type']); ?></td>
                                                <td class="px-4 py-3">
                                                    <div class="font-mono text-[10px] text-slate-500"><?php echo htmlspecialchars($asset['serial_number'] ?: '—'); ?></div>
                                                    <div class="font-mono text-[10px] text-primary"><?php echo htmlspecialchars($asset['qr_code']); ?></div>
                                                    <a href="qr_generator.php?asset_id=<?php echo (int)$asset['asset_id']; ?>" target="_blank" class="text-[10px] text-blue-600 hover:underline">View QR</a>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $condition_badge; ?>">
                                                        <?php echo htmlspecialchars($asset['condition_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $status_badge; ?>">
                                                        <?php echo htmlspecialchars($asset['current_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($asset['zone_location'] ?: '—'); ?></td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                    <div class="inline-flex gap-1">
                                                        <button type="button" onclick='openEditModal(<?php echo json_encode($asset); ?>)' class="p-1 rounded hover:bg-slate-100 text-slate-600" title="Edit Asset">
                                                            <span class="material-symbols-outlined text-sm">edit</span>
                                                        </button>
                                                        <button type="button" onclick='openConditionModal(<?php echo json_encode($asset); ?>)' class="p-1 rounded hover:bg-blue-50 text-blue-600" title="Update Condition">
                                                            <span class="material-symbols-outlined text-sm">build</span>
                                                        </button>
                                                        <form method="post" onsubmit="return confirm('Delete this asset permanently?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_asset"/>
                                                            <input type="hidden" name="asset_id" value="<?php echo (int)$asset['asset_id']; ?>"/>
                                                            <button type="submit" class="p-1 rounded hover:bg-red-50 text-red-600" title="Delete Asset">
                                                                <span class="material-symbols-outlined text-sm">delete</span>
                                                            </button>
                                                        </form>
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
                        <p><strong>Tech asset management is not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Asset Modal -->
    <div id="asset-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modal-title" class="text-base font-bold text-slate-900">Register New Tech Asset</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" id="form-action" value="add_asset"/>
                <input type="hidden" name="asset_id" id="form-asset-id" value=""/>

                <div class="form-field">
                    <label>Asset Name</label>
                    <input type="text" name="asset_name" id="f-asset-name" placeholder="Dell Latitude 5520 Laptop" required/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Asset Type</label>
                        <select name="asset_type" id="f-asset-type" required>
                            <option value="Laptop">Laptop</option>
                            <option value="Headset">Headset</option>
                            <option value="Webcam">Webcam</option>
                            <option value="Monitor">Monitor</option>
                            <option value="Keyboard">Keyboard</option>
                            <option value="Mouse">Mouse</option>
                            <option value="Docking Station">Docking Station</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Serial Number</label>
                        <input type="text" name="serial_number" id="f-serial-number" placeholder="SN123456789"/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Brand</label>
                        <input type="text" name="brand" id="f-brand" placeholder="Dell"/>
                    </div>
                    <div class="form-field">
                        <label>Model</label>
                        <input type="text" name="model" id="f-model" placeholder="Latitude 5520"/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Purchase Date</label>
                        <input type="date" name="purchase_date" id="f-purchase-date"/>
                    </div>
                    <div class="form-field">
                        <label>Warranty Expiry</label>
                        <input type="date" name="warranty_expiry" id="f-warranty-expiry"/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Condition</label>
                        <select name="condition_status" id="f-condition-status" required>
                            <option value="Brand New">Brand New</option>
                            <option value="Good">Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Defective">Defective</option>
                            <option value="Repaired">Repaired</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Current Status</label>
                        <select name="current_status" id="f-current-status" required>
                            <option value="In Storage">In Storage</option>
                            <option value="Deployed">Deployed</option>
                            <option value="In Transit">In Transit</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Retired">Retired</option>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label>Zone Location</label>
                    <input type="text" name="zone_location" id="f-zone-location" placeholder="Zone A, Rack 1"/>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save Asset</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Condition Update Modal -->
    <div id="condition-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Update Asset Condition</h3>
                <button type="button" onclick="closeConditionModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="update_condition"/>
                <input type="hidden" name="asset_id" id="cond-asset-id" value=""/>

                <div class="form-field">
                    <label>New Condition</label>
                    <select name="new_condition" id="f-new-condition" required>
                        <option value="Brand New">Brand New</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Defective">Defective</option>
                        <option value="Repaired">Repaired</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Reason for Change</label>
                    <textarea name="change_reason" id="f-change-reason" rows="3" placeholder="Describe why the condition is being updated..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeConditionModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Update Condition</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Register New Tech Asset';
            document.getElementById('form-action').value = 'add_asset';
            document.getElementById('form-asset-id').value = '';
            document.getElementById('f-asset-name').value = '';
            document.getElementById('f-asset-type').value = 'Laptop';
            document.getElementById('f-serial-number').value = '';
            document.getElementById('f-brand').value = '';
            document.getElementById('f-model').value = '';
            document.getElementById('f-purchase-date').value = '';
            document.getElementById('f-warranty-expiry').value = '';
            document.getElementById('f-condition-status').value = 'Brand New';
            document.getElementById('f-current-status').value = 'In Storage';
            document.getElementById('f-zone-location').value = '';
            document.getElementById('asset-modal').style.display = 'flex';
        }

        function openEditModal(asset) {
            document.getElementById('modal-title').textContent = 'Edit Tech Asset';
            document.getElementById('form-action').value = 'edit_asset';
            document.getElementById('form-asset-id').value = asset.asset_id;
            document.getElementById('f-asset-name').value = asset.asset_name;
            document.getElementById('f-asset-type').value = asset.asset_type;
            document.getElementById('f-serial-number').value = asset.serial_number || '';
            document.getElementById('f-brand').value = asset.brand || '';
            document.getElementById('f-model').value = asset.model || '';
            document.getElementById('f-purchase-date').value = asset.purchase_date || '';
            document.getElementById('f-warranty-expiry').value = asset.warranty_expiry || '';
            document.getElementById('f-condition-status').value = asset.condition_status;
            document.getElementById('f-current-status').value = asset.current_status;
            document.getElementById('f-zone-location').value = asset.zone_location || '';
            document.getElementById('asset-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('asset-modal').style.display = 'none';
        }

        function openConditionModal(asset) {
            document.getElementById('cond-asset-id').value = asset.asset_id;
            document.getElementById('f-new-condition').value = asset.condition_status;
            document.getElementById('f-change-reason').value = '';
            document.getElementById('condition-modal').style.display = 'flex';
        }

        function closeConditionModal() {
            document.getElementById('condition-modal').style.display = 'none';
        }
    </script>
</body>
</html>