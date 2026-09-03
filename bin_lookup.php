<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'sws_connection.php';
$section_title = "Smart Warehousing System (SWS)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';
$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_bin') {
        $zone_id = (int)$_POST['zone_id'];
        $bin_code = strtoupper(trim($_POST['bin_code'] ?? ''));
        $sku = trim($_POST['sku'] ?? '');
        $item_name = trim($_POST['item_name'] ?? '');
        $capacity = (int)$_POST['capacity'];
        $used_units = (int)$_POST['used_units'];
        $status = $used_units > 0 ? 'Occupied' : 'Empty';

        if ($bin_code === '' || $zone_id <= 0) {
            $db_error = "Bin Code and Zone are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO warehouse_bins (zone_id, bin_code, sku, item_name, capacity, used_units, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiis", $zone_id, $bin_code, $sku, $item_name, $capacity, $used_units, $status);
            if ($stmt->execute()) {
                $flash = "Bin '$bin_code' created.";
            } else {
                $db_error = "Failed to add bin: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'update_bin') {
        $bin_id = (int)$_POST['bin_id'];
        $sku = trim($_POST['sku'] ?? '');
        $item_name = trim($_POST['item_name'] ?? '');
        $used_units = (int)$_POST['used_units'];
        $capacity = (int)$_POST['capacity'];
        $status = $_POST['status'] ?? ($used_units > 0 ? 'Occupied' : 'Empty');

        $stmt = $conn->prepare("UPDATE warehouse_bins SET sku = ?, item_name = ?, used_units = ?, capacity = ?, status = ? WHERE bin_id = ?");
        $stmt->bind_param("ssiisi", $sku, $item_name, $used_units, $capacity, $status, $bin_id);
        if ($stmt->execute()) {
            $flash = "Bin details updated.";
        } else {
            $db_error = "Failed to update bin: " . $stmt->error;
        }
        $stmt->close();
    }
}

$bins = [];
$zones_list = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_zone = isset($_GET['zone_id']) ? (int)$_GET['zone_id'] : 0;

if (!$conn->connect_error) {
    $z_res = $conn->query("SELECT zone_id, zone_code, zone_name FROM warehouse_zones ORDER BY zone_code ASC");
    if ($z_res) while ($r = $z_res->fetch_assoc()) $zones_list[] = $r;

    $sql = "SELECT wb.*, wz.zone_code, wz.zone_name 
            FROM warehouse_bins wb 
            JOIN warehouse_zones wz ON wb.zone_id = wz.zone_id 
            WHERE 1=1";
    $params = [];
    $types = '';

    if ($search !== '') {
        $sql .= " AND (wb.bin_code LIKE ? OR wb.sku LIKE ? OR wb.item_name LIKE ?)";
        $like = "%$search%";
        $params = [$like, $like, $like];
        $types = 'sss';
    }

    if ($filter_zone > 0) {
        $sql .= " AND wb.zone_id = ?";
        $params[] = $filter_zone;
        $types .= 'i';
    }

    $sql .= " ORDER BY wb.bin_code ASC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) $bins[] = $row;
    }
} else {
    $db_error = "Database offline.";
}

function getBinBadge($status) {
    switch ($status) {
        case 'Empty': return 'bg-slate-100 text-slate-700 border-slate-200';
        case 'Occupied': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Reserved': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Blocked': return 'bg-rose-100 text-rose-800 border-rose-200';
        default: return 'bg-slate-100 text-slate-800 border-slate-200';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Bin Lookup</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 1rem; width: 100%; max-width: 32rem; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .form-field { margin-bottom: 0.85rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select { width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem; }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Smart Warehousing System</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-600 text-sm font-medium">Bin Lookup</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Bin Location & SKU Lookup</h1>
                        <p class="text-slate-500 text-sm mt-1">Look up bin codes, current item occupancy, and fill ratios across storage zones.</p>
                    </div>
                    <button type="button" onclick="openAddBinModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add_box</span> Add Bin
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
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">qr_code_scanner</span>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Scan QR or search Bin Code, SKU, or item..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                            </div>
                            <select name="zone_id" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none" onchange="this.form.submit()">
                                <option value="0">All Zones</option>
                                <?php foreach ($zones_list as $z): ?>
                                    <option value="<?php echo $z['zone_id']; ?>" <?php echo $filter_zone === (int)$z['zone_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($z['zone_name'] ?: 'Zone ' . $z['zone_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold">Search</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Bin Code</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Zone</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Stored SKU / Item</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Occupancy</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($bins)): ?>
                                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No bins found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($bins as $bin): 
                                        $binPct = $bin['capacity'] > 0 ? min(100, round(($bin['used_units'] / $bin['capacity']) * 100)) : 0;
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-mono font-bold text-primary flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[16px] text-slate-400">shelves</span>
                                                <?php echo htmlspecialchars($bin['bin_code']); ?>
                                            </td>
                                            <td class="px-4 py-3 font-medium text-slate-800">
                                                <?php echo htmlspecialchars($bin['zone_name'] ?: 'Zone ' . $bin['zone_code']); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php if ($bin['sku'] || $bin['item_name']): ?>
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($bin['item_name'] ?: 'Item'); ?></div>
                                                    <div class="text-[10px] font-mono text-indigo-600"><?php echo htmlspecialchars($bin['sku']); ?></div>
                                                <?php else: ?>
                                                    <span class="text-slate-400 italic">Empty Location</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-[11px] font-medium text-slate-700"><?php echo $bin['used_units']; ?> / <?php echo $bin['capacity']; ?> (<?php echo $binPct; ?>%)</div>
                                                <div class="w-24 bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                                    <div class="h-1.5 bg-primary rounded-full" style="width: <?php echo $binPct; ?>%"></div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border <?php echo getBinBadge($bin['status']); ?>">
                                                    <?php echo htmlspecialchars($bin['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button type="button" onclick='openEditBinModal(<?php echo json_encode($bin); ?>)' class="px-2 py-1 text-[11px] bg-slate-100 text-slate-700 hover:bg-slate-200 rounded font-semibold">
                                                    Edit
                                                </button>
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

    <!-- Modals -->
    <div id="add-bin-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Add Bin Location</h3>
                <button type="button" onclick="closeAddBinModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="add_bin"/>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Warehouse Zone</label>
                        <select name="zone_id" required>
                            <?php foreach ($zones_list as $z): ?>
                                <option value="<?php echo $z['zone_id']; ?>"><?php echo htmlspecialchars($z['zone_name'] ?: 'Zone ' . $z['zone_code']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Bin Code</label>
                        <input type="text" name="bin_code" placeholder="BIN-A1-01" required/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>SKU (Optional)</label>
                        <input type="text" name="sku" placeholder="SKU-1001"/>
                    </div>
                    <div class="form-field">
                        <label>Item Name</label>
                        <input type="text" name="item_name" placeholder="Item Name"/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Capacity</label>
                        <input type="number" name="capacity" min="1" value="100" required/>
                    </div>
                    <div class="form-field">
                        <label>Used Units</label>
                        <input type="number" name="used_units" min="0" value="0"/>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddBinModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Add Bin</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-bin-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Edit Bin</h3>
                <button type="button" onclick="closeEditBinModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="update_bin"/>
                <input type="hidden" name="bin_id" id="edit-bin-id"/>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>SKU</label>
                        <input type="text" name="sku" id="edit-bin-sku"/>
                    </div>
                    <div class="form-field">
                        <label>Item Name</label>
                        <input type="text" name="item_name" id="edit-bin-name"/>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="form-field">
                        <label>Used Units</label>
                        <input type="number" name="used_units" id="edit-bin-units" min="0" required/>
                    </div>
                    <div class="form-field">
                        <label>Capacity</label>
                        <input type="number" name="capacity" id="edit-bin-cap" min="1" required/>
                    </div>
                    <div class="form-field">
                        <label>Status</label>
                        <select name="status" id="edit-bin-status">
                            <option value="Empty">Empty</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Blocked">Blocked</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditBinModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddBinModal() { document.getElementById('add-bin-modal').style.display = 'flex'; }
        function closeAddBinModal() { document.getElementById('add-bin-modal').style.display = 'none'; }
        function openEditBinModal(bin) {
            document.getElementById('edit-bin-id').value = bin.bin_id;
            document.getElementById('edit-bin-sku').value = bin.sku || '';
            document.getElementById('edit-bin-name').value = bin.item_name || '';
            document.getElementById('edit-bin-units').value = bin.used_units;
            document.getElementById('edit-bin-cap').value = bin.capacity;
            document.getElementById('edit-bin-status').value = bin.status;
            document.getElementById('edit-bin-modal').style.display = 'flex';
        }
        function closeEditBinModal() { document.getElementById('edit-bin-modal').style.display = 'none'; }
    </script>
</body>
</html>