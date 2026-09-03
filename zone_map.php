<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'sws_connection.php';
require_once 'db_client.php'; // for core_log()

$section_title = "Smart Warehousing System - Zone Map";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_zone') {
        $stmt = $conn->prepare("INSERT INTO warehouse_zones (zone_code, zone_name, rack_code, total_capacity, status) VALUES (?, ?, ?, ?, ?)");
        $total_capacity = (int)$_POST['total_capacity'];
        $stmt->bind_param("sssis",
            $_POST['zone_code'],
            $_POST['zone_name'],
            $_POST['rack_code'],
            $total_capacity,
            $_POST['status']
        );

        if ($stmt->execute()) {
            $flash = "Zone " . $_POST['zone_code'] . " created successfully.";
            core_log($_SESSION['user_id'], $_SESSION['username'], 'Zone Created', "Created zone " . $_POST['zone_code'] . " (" . $_POST['zone_name'] . ")");
        } else {
            $db_error = "Failed to create zone: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'edit_zone') {
        $zone_id = (int)$_POST['zone_id'];
        $stmt = $conn->prepare("UPDATE warehouse_zones SET zone_code = ?, zone_name = ?, rack_code = ?, total_capacity = ?, used_capacity = ?, status = ? WHERE zone_id = ?");
        $total_capacity = (int)$_POST['total_capacity'];
        $used_capacity = (int)$_POST['used_capacity'];
        $stmt->bind_param("sssiisi",
            $_POST['zone_code'],
            $_POST['zone_name'],
            $_POST['rack_code'],
            $total_capacity,
            $used_capacity,
            $_POST['status'],
            $zone_id
        );

        if ($stmt->execute()) {
            $flash = "Zone updated successfully.";
            core_log($_SESSION['user_id'], $_SESSION['username'], 'Zone Updated', "Updated zone " . $_POST['zone_code']);
        } else {
            $db_error = "Failed to update zone: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'delete_zone') {
        $zone_id = (int)$_POST['zone_id'];
        $stmt = $conn->prepare("DELETE FROM warehouse_zones WHERE zone_id = ?");
        $stmt->bind_param("i", $zone_id);

        if ($stmt->execute()) {
            $flash = "Zone deleted successfully.";
            core_log($_SESSION['user_id'], $_SESSION['username'], 'Zone Deleted', "Deleted zone ID $zone_id");
        } else {
            $db_error = "Failed to delete zone: " . $stmt->error;
        }
        $stmt->close();
    }
}

$zones = [];
if (!$conn->connect_error) {
    $result = $conn->query("SELECT * FROM warehouse_zones ORDER BY zone_code ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['percentage'] = $row['total_capacity'] > 0
                ? round(($row['used_capacity'] / $row['total_capacity']) * 100)
                : 0;
            $zones[] = $row;
        }
    } else {
        $db_error = "Query failed: " . $conn->error;
    }
}

function zoneStatusMeta($status) {
    switch ($status) {
        case 'Verified':           return ['class' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'icon' => 'verified'];
        case 'Pending Audit':      return ['class' => 'bg-amber-100 text-amber-800 border-amber-200', 'icon' => 'pending'];
        case 'Requires Attention': return ['class' => 'bg-red-100 text-red-800 border-red-200', 'icon' => 'warning'];
        default:                  return ['class' => 'bg-slate-100 text-slate-800 border-slate-200', 'icon' => 'help'];
    }
}

function usageBarColor($pct) {
    if ($pct >= 90) return 'bg-red-500';
    if ($pct >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
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
            background: #fff; border-radius: 1rem; width: 100%; max-width: 32rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .form-field { margin-bottom: 1rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select {
            width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem;
        }
        .zone-card {
            background:#fff; border-radius:1rem; padding:1.25rem;
            box-shadow:0 1px 3px rgba(0,0,0,0.1); border:1px solid #e2e8f0;
        }
        .usage-track { height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
        .usage-fill { height:100%; transition: width 0.3s ease; }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'header.php'; ?>
        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Zone Map</h1>
                        <p class="text-slate-500 text-sm mt-1">Warehouse zone capacity, rack layout, and audit status at a glance.</p>
                    </div>
                    <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add_circle</span> Add Zone
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php if (empty($zones)): ?>
                        <div class="col-span-full text-center text-slate-400 py-10">No zones configured yet. Click "Add Zone" to create one.</div>
                    <?php else: ?>
                        <?php foreach ($zones as $zone): $meta = zoneStatusMeta($zone['status']); ?>
                            <div class="zone-card">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Zone</p>
                                        <p class="text-2xl font-bold text-slate-900"><?php echo htmlspecialchars($zone['zone_code']); ?></p>
                                        <p class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($zone['zone_name'] ?: '—'); ?></p>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $meta['class']; ?>">
                                        <span class="material-symbols-outlined text-[12px]"><?php echo $meta['icon']; ?></span>
                                        <?php echo htmlspecialchars($zone['status']); ?>
                                    </span>
                                </div>

                                <p class="text-xs text-slate-500 mb-1">Rack: <span class="font-mono font-semibold text-slate-700"><?php echo htmlspecialchars($zone['rack_code'] ?: '—'); ?></span></p>

                                <div class="mt-3">
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-slate-500">Capacity Usage</span>
                                        <span class="font-semibold text-slate-900"><?php echo $zone['percentage']; ?>%</span>
                                    </div>
                                    <div class="usage-track">
                                        <div class="usage-fill <?php echo usageBarColor($zone['percentage']); ?>" style="width: <?php echo $zone['percentage']; ?>%;"></div>
                                    </div>
                                    <p class="text-[11px] text-slate-400 mt-1"><?php echo (int)$zone['used_capacity']; ?> / <?php echo (int)$zone['total_capacity']; ?> units</p>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 flex justify-end gap-2">
                                    <button type="button" onclick='openEditModal(<?php echo json_encode($zone); ?>)' class="px-3 py-1.5 border border-slate-200 rounded-lg text-[11px] font-semibold text-slate-600 hover:bg-slate-50">Edit</button>
                                    <form method="post" class="inline" onsubmit="return confirm('Delete this zone? This cannot be undone.');">
                                        <input type="hidden" name="action" value="delete_zone"/>
                                        <input type="hidden" name="zone_id" value="<?php echo (int)$zone['zone_id']; ?>"/>
                                        <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-700 border border-red-200 rounded-lg text-[11px] font-semibold hover:bg-red-100">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add/Edit Zone Modal -->
    <div id="zone-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900" id="modal-title">Add Zone</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" id="form-action" value="add_zone"/>
                <input type="hidden" name="zone_id" id="form-zone-id" value=""/>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Zone Code</label>
                        <input type="text" name="zone_code" id="f-zone-code" placeholder="A" maxlength="10" required/>
                    </div>
                    <div class="form-field">
                        <label>Zone Name</label>
                        <input type="text" name="zone_name" id="f-zone-name" placeholder="Zone A"/>
                    </div>
                </div>

                <div class="form-field">
                    <label>Rack Code</label>
                    <input type="text" name="rack_code" id="f-rack-code" placeholder="RACK-A1"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Total Capacity (units)</label>
                        <input type="number" name="total_capacity" id="f-total-capacity" min="0" required/>
                    </div>
                    <div class="form-field" id="used-capacity-field" style="display:none;">
                        <label>Used Capacity (units)</label>
                        <input type="number" name="used_capacity" id="f-used-capacity" min="0"/>
                    </div>
                </div>

                <div class="form-field">
                    <label>Status</label>
                    <select name="status" id="f-status" required>
                        <option value="Verified">Verified</option>
                        <option value="Pending Audit">Pending Audit</option>
                        <option value="Requires Attention">Requires Attention</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save Zone</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add Zone';
            document.getElementById('form-action').value = 'add_zone';
            document.getElementById('form-zone-id').value = '';
            document.getElementById('f-zone-code').value = '';
            document.getElementById('f-zone-name').value = '';
            document.getElementById('f-rack-code').value = '';
            document.getElementById('f-total-capacity').value = '';
            document.getElementById('f-status').value = 'Pending Audit';
            document.getElementById('used-capacity-field').style.display = 'none';
            document.getElementById('zone-modal').style.display = 'flex';
        }

        function openEditModal(zone) {
            document.getElementById('modal-title').textContent = 'Edit Zone';
            document.getElementById('form-action').value = 'edit_zone';
            document.getElementById('form-zone-id').value = zone.zone_id;
            document.getElementById('f-zone-code').value = zone.zone_code;
            document.getElementById('f-zone-name').value = zone.zone_name || '';
            document.getElementById('f-rack-code').value = zone.rack_code || '';
            document.getElementById('f-total-capacity').value = zone.total_capacity;
            document.getElementById('f-used-capacity').value = zone.used_capacity;
            document.getElementById('f-status').value = zone.status;
            document.getElementById('used-capacity-field').style.display = 'block';
            document.getElementById('zone-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('zone-modal').style.display = 'none';
        }
    </script>
</body>
</html>