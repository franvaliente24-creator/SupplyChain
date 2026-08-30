<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_connection.php';

$section_title = "Smart Warehousing System (SWS)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_zone') {
        $stmt = $conn->prepare("INSERT INTO warehouse_zones (zone_name, rack_code, capacity, current_stock, audit_status) VALUES (?, ?, ?, ?, ?)");
        $capacity = (int)$_POST['capacity'];
        $current_stock = (int)$_POST['current_stock'];
        $stmt->bind_param("ssiis", $_POST['zone_name'], $_POST['rack_code'], $capacity, $current_stock, $_POST['audit_status']);
        if ($stmt->execute()) {
            $flash = "Warehouse zone added successfully.";
        } else {
            $db_error = "Failed to add zone: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'edit_zone') {
        $zone_id = (int)$_POST['zone_id'];
        $stmt = $conn->prepare("UPDATE warehouse_zones SET zone_name = ?, rack_code = ?, capacity = ?, current_stock = ?, audit_status = ? WHERE zone_id = ?");
        $capacity = (int)$_POST['capacity'];
        $current_stock = (int)$_POST['current_stock'];
        $stmt->bind_param("ssiisi", $_POST['zone_name'], $_POST['rack_code'], $capacity, $current_stock, $_POST['audit_status'], $zone_id);
        if ($stmt->execute()) {
            $flash = "Zone parameters updated successfully.";
        } else {
            $db_error = "Failed to update zone: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'log_movement') {
        $stmt = $conn->prepare("INSERT INTO stock_movement_logs (item_id, from_zone, to_zone, quantity, movement_type) VALUES (?, ?, ?, ?, ?)");
        $item_id = $_POST['item_id'] !== '' ? (int)$_POST['item_id'] : null;
        $qty = (int)$_POST['quantity'];
        $stmt->bind_param("issss", $item_id, $_POST['from_zone'], $_POST['to_zone'], $qty, $_POST['movement_type']);
        if ($stmt->execute()) {
            if ($_POST['from_zone']) {
                $conn->query("UPDATE warehouse_zones SET current_stock = GREATEST(0, current_stock - $qty) WHERE zone_name = '".$conn->real_escape_string($_POST['from_zone'])."'");
            }
            if ($_POST['to_zone']) {
                $conn->query("UPDATE warehouse_zones SET current_stock = current_stock + $qty WHERE zone_name = '".$conn->real_escape_string($_POST['to_zone'])."'");
            }
            $flash = "Stock movement transaction logged and processed.";
        } else {
            $db_error = "Failed to log movement: " . $stmt->error;
        }
        $stmt->close();
    }
}

$zones = [];
$logs = [];
$items_list = [];

if (!$conn->connect_error) {
    $zone_result = $conn->query("SELECT * FROM warehouse_zones ORDER BY zone_name ASC");
    if ($zone_result) {
        while ($row = $zone_result->fetch_assoc()) {
            $zones[] = $row;
        }
    }

    $log_result = $conn->query("SELECT l.*, i.item_name, i.sku FROM stock_movement_logs l LEFT JOIN inventory_items i ON l.item_id = i.item_id ORDER BY l.logged_at DESC LIMIT 15");
    if ($log_result) {
        while ($row = $log_result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    $item_res = $conn->query("SELECT item_id, item_name, sku FROM inventory_items WHERE status != 'Archived'");
    if ($item_res) {
        while ($row = $item_res->fetch_assoc()) {
            $items_list[] = $row;
        }
    }
} else {
    $db_error = "Database connection offline. Verify db_connection.php.";
}

function getAuditBadgeClass($status) {
    switch ($status) {
        case 'Verified': return 'status-badge-active';
        case 'Pending Audit': return 'status-badge-maintenance';
        case 'Requires Attention': return 'status-badge-critical';
        default: return 'status-badge-archived';
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
            background: #fff; border-radius: 1rem; width: 100%; max-width: 30rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .form-field { margin-bottom: 1rem; }
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
                <span class="font-bold text-slate-800 text-sm">Smart Warehousing Ecosystem</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">SWS Floor Plan & Inventory Tracker</h1>
                        <p class="text-slate-500 text-sm mt-1">Configure physical racks, monitor usage capacity metrics, and log transfer movements.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" onclick="openMovementModal()" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-indigo-700 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">swap_horiz</span> Log Movement
                        </button>
                        <button type="button" onclick="openZoneModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">add_box</span> Create Zone
                        </button>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($zones as $z): 
                        $util_pct = $z['capacity'] > 0 ? min(100, round(($z['current_stock'] / $z['capacity']) * 100)) : 0;
                        $audit_class = getAuditBadgeClass($z['audit_status']);
                    ?>
                        <div class="zone-card-container bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900"><?php echo htmlspecialchars($z['zone_name']); ?></h3>
                                    <span class="text-xs text-slate-400 font-mono"><?php echo htmlspecialchars($z['rack_code']); ?></span>
                                </div>
                                <span class="status-badge <?php echo $audit_class; ?>" style="padding: 2px 8px; font-size: 0.65rem; border-radius: 999px;">
                                    <?php echo htmlspecialchars($z['audit_status']); ?>
                                </span>
                            </div>

                            <div class="space-y-2 my-3">
                                <div class="flex justify-between text-xs font-semibold text-slate-600">
                                    <span>Utilization</span>
                                    <span><?php echo $util_pct; ?>%</span>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill <?php echo $util_pct > 85 ? 'bg-red-500' : ($util_pct > 50 ? 'bg-amber-500' : 'bg-emerald-500'); ?>" style="width: <?php echo $util_pct; ?>%;"></div>
                                </div>
                                <p class="text-[11px] text-slate-400 text-right font-medium"><?php echo number_format($z['current_stock']); ?> / <?php echo number_format($z['capacity']); ?> Units</p>
                            </div>

                            <div class="border-t border-slate-100 pt-3 flex justify-between items-center text-xs">
                                <span class="text-slate-400">Last Audited: <?php echo !empty($z['last_audited_at']) ? date('M j', strtotime($z['last_audited_at'])) : (!empty($z['created_at']) ? date('M j', strtotime($z['created_at'])) : 'N/A'); ?></span>
                                <button type="button" class="text-primary font-bold hover:underline" onclick='openEditZoneModal(<?php echo json_encode($z); ?>)'>Configure</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                        <h2 class="text-sm font-bold text-slate-900">Stock Movement Audit Log</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Timestamp</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">SKU / Item</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">From</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">To</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Movement</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">No stock movements found in this hub sequence.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $l): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 text-slate-400"><?php echo date('M j, Y g:i A', strtotime($l['logged_at'])); ?></td>
                                            <td class="px-6 py-4 font-semibold text-slate-900">
                                                <span class="bg-slate-100 px-1.5 py-0.5 rounded mr-1 font-mono text-[10px]"><?php echo htmlspecialchars($l['sku'] ?: 'N/A'); ?></span>
                                                <?php echo htmlspecialchars($l['item_name'] ?: 'External Dispatch'); ?>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($l['from_zone'] ?: '— (Inbound Entry)'); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($l['to_zone'] ?: '— (Outbound Exit)'); ?></td>
                                            <td class="px-6 py-4 font-bold"><?php echo number_format($l['quantity']); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] 
                                                    <?php echo $l['movement_type'] === 'Inbound' ? 'bg-emerald-50 text-emerald-700' : ($l['movement_type'] === 'Outbound' ? 'bg-rose-50 text-rose-700' : 'bg-blue-50 text-blue-700'); ?>">
                                                    <?php echo htmlspecialchars($l['movement_type']); ?>
                                                </span>
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

    <div id="zone-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 id="zone-modal-title" class="text-base font-bold text-slate-900">Configure Warehouse Zone</h3>
                <button type="button" onclick="closeZoneModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" id="zone-action" value="add_zone"/>
                <input type="hidden" name="zone_id" id="zone-id" value=""/>
                
                <div class="form-field">
                    <label>Zone Name</label>
                    <input type="text" name="zone_name" id="f-zone-name" placeholder="Zone E" required/>
                </div>
                <div class="form-field">
                    <label>Rack Code Layout Reference</label>
                    <input type="text" name="rack_code" id="f-rack-code" placeholder="RACK-E1-E2" required/>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Max Storage Capacity</label>
                        <input type="number" name="capacity" id="f-capacity" required min="1"/>
                    </div>
                    <div class="form-field">
                        <label>Current Stock Occupancy</label>
                        <input type="number" name="current_stock" id="f-current-stock" required min="0"/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Zone Audit Evaluation</label>
                    <select name="audit_status" id="f-audit-status">
                        <option value="Verified">Verified (Accurate)</option>
                        <option value="Pending Audit">Pending Audit Cycle</option>
                        <option value="Requires Attention">Requires Attention (Miscount Alert)</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeZoneModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save Configuration</button>
                </div>
            </form>
        </div>
    </div>

    <div id="movement-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">New Physical Transfer Order</h3>
                <button type="button" onclick="closeMovementModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="log_movement"/>

                <div class="form-field">
                    <label>Item Profile</label>
                    <select name="item_id" required>
                        <option value="">Select an active SKU...</option>
                        <?php foreach ($items_list as $it): ?>
                            <option value="<?php echo $it['item_id']; ?>"><?php echo htmlspecialchars($it['sku'] . ' — ' . $it['item_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Action Category</label>
                    <select name="movement_type" required>
                        <option value="Transfer">Transfer (Zone to Zone)</option>
                        <option value="Inbound">Inbound (Dock to Rack)</option>
                        <option value="Outbound">Outbound (Pick to Ship)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Origin Zone</label>
                        <select name="from_zone">
                            <option value="">None (Entry / Outside)</option>
                            <?php foreach ($zones as $z): ?>
                                <option value="<?php echo htmlspecialchars($z['zone_name']); ?>"><?php echo htmlspecialchars($z['zone_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Destination Zone</label>
                        <select name="to_zone">
                            <option value="">None (Shipping Yard / Outbound)</option>
                            <?php foreach ($zones as $z): ?>
                                <option value="<?php echo htmlspecialchars($z['zone_name']); ?>"><?php echo htmlspecialchars($z['zone_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label>Quantity Moved</label>
                    <input type="number" name="quantity" min="1" required/>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeMovementModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-xs font-bold">Process Dispatch</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openZoneModal() {
            document.getElementById('zone-modal-title').textContent = 'Create New Warehouse Zone';
            document.getElementById('zone-action').value = 'add_zone';
            document.getElementById('zone-id').value = '';
            document.getElementById('f-zone-name').value = '';
            document.getElementById('f-rack-code').value = '';
            document.getElementById('f-capacity').value = '500';
            document.getElementById('f-current-stock').value = '0';
            document.getElementById('f-audit-status').value = 'Verified';
            document.getElementById('zone-modal').style.display = 'flex';
        }

        function openEditZoneModal(zone) {
            document.getElementById('zone-modal-title').textContent = 'Configure Warehouse Zone';
            document.getElementById('zone-action').value = 'edit_zone';
            document.getElementById('zone-id').value = zone.zone_id;
            document.getElementById('f-zone-name').value = zone.zone_name;
            document.getElementById('f-rack-code').value = zone.rack_code;
            document.getElementById('f-capacity').value = zone.capacity;
            document.getElementById('f-current-stock').value = zone.current_stock;
            document.getElementById('f-audit-status').value = zone.audit_status;
            document.getElementById('zone-modal').style.display = 'flex';
        }

        function closeZoneModal() {
            document.getElementById('zone-modal').style.display = 'none';
        }

        function openMovementModal() {
            document.getElementById('movement-modal').style.display = 'flex';
        }

        function closeMovementModal() {
            document.getElementById('movement-modal').style.display = 'none';
        }
    </script>
</body>
</html>