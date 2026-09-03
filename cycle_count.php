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

    if ($action === 'create_count') {
        $zone_id = (int)$_POST['zone_id'];
        $bin_id = !empty($_POST['bin_id']) ? (int)$_POST['bin_id'] : null;
        $expected_qty = (int)$_POST['expected_quantity'];
        $counted_qty = ($_POST['counted_quantity'] !== '') ? (int)$_POST['counted_quantity'] : null;
        $notes = trim($_POST['notes'] ?? '');
        $counted_by = (int)$_SESSION['user_id'];

        $count_status = 'Scheduled';
        if ($counted_qty !== null) {
            $count_status = ($counted_qty === $expected_qty) ? 'Completed' : 'Variance Found';
        }

        $stmt = $conn->prepare("INSERT INTO cycle_counts (zone_id, bin_id, expected_quantity, counted_quantity, counted_by, count_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiss", $zone_id, $bin_id, $expected_qty, $counted_qty, $counted_by, $count_status, $notes);
        if ($stmt->execute()) {
            $flash = "Audit record initialized.";
        } else {
            $db_error = "Failed to record audit: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'resolve_count') {
        $count_id = (int)$_POST['count_id'];
        $counted_qty = (int)$_POST['counted_quantity'];
        $notes = trim($_POST['notes'] ?? '');
        
        $c_res = $conn->query("SELECT expected_quantity FROM cycle_counts WHERE count_id = $count_id");
        $expected_qty = 0;
        if ($c_res && $row = $c_res->fetch_assoc()) {
            $expected_qty = (int)$row['expected_quantity'];
        }

        $new_status = ($counted_qty === $expected_qty) ? 'Completed' : 'Variance Found';

        $stmt = $conn->prepare("UPDATE cycle_counts SET counted_quantity = ?, count_status = ?, notes = ?, completed_at = NOW() WHERE count_id = ?");
        $stmt->bind_param("issi", $counted_qty, $new_status, $notes, $count_id);
        if ($stmt->execute()) {
            $flash = "Audit status updated to '$new_status'.";
        } else {
            $db_error = "Failed to resolve count: " . $stmt->error;
        }
        $stmt->close();
    }
}

$counts = [];
$zones_list = [];
$bins_list = [];

if (!$conn->connect_error) {
    $z_res = $conn->query("SELECT zone_id, zone_code, zone_name FROM warehouse_zones ORDER BY zone_code ASC");
    if ($z_res) while ($r = $z_res->fetch_assoc()) $zones_list[] = $r;

    $b_res = $conn->query("SELECT bin_id, bin_code, sku, item_name FROM warehouse_bins ORDER BY bin_code ASC");
    if ($b_res) while ($r = $b_res->fetch_assoc()) $bins_list[] = $r;

    $sql = "SELECT cc.*, wz.zone_code, wz.zone_name, wb.bin_code, wb.sku, wb.item_name
            FROM cycle_counts cc
            JOIN warehouse_zones wz ON cc.zone_id = wz.zone_id
            LEFT JOIN warehouse_bins wb ON cc.bin_id = wb.bin_id
            ORDER BY cc.created_at DESC LIMIT 50";
    $result = $conn->query($sql);
    if ($result) while ($r = $result->fetch_assoc()) $counts[] = $r;
} else {
    $db_error = "Database offline.";
}

function getCycleBadge($status) {
    switch ($status) {
        case 'Completed': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Variance Found': return 'bg-rose-100 text-rose-800 border-rose-200 font-bold';
        case 'In Progress': return 'bg-blue-100 text-blue-800 border-blue-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Cycle Count</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 1rem; width: 100%; max-width: 32rem; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
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
                <span class="font-bold text-slate-800 text-sm">Smart Warehousing System</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-600 text-sm font-medium">Cycle Count</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Cycle Count & Physical Audit</h1>
                        <p class="text-slate-500 text-sm mt-1">Audit physical stock counts against bin records with automated variance detection.</p>
                    </div>
                    <button type="button" onclick="openCountModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">checklist</span> Schedule / Log Audit
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
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Zone & Location</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Item / SKU</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Expected</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Counted</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Variance</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Created</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($counts)): ?>
                                    <tr><td colspan="8" class="px-6 py-10 text-center text-slate-400">No cycle counts recorded.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($counts as $c): 
                                        $v = $c['variance'];
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($c['zone_name'] ?: 'Zone ' . $c['zone_code']); ?></div>
                                                <div class="font-mono text-[10px] text-slate-500"><?php echo htmlspecialchars($c['bin_code'] ?: 'General'); ?></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-slate-800"><?php echo htmlspecialchars($c['item_name'] ?: 'Zone General Audit'); ?></div>
                                                <div class="text-[10px] font-mono text-slate-400"><?php echo htmlspecialchars($c['sku'] ?: '—'); ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono text-slate-600"><?php echo $c['expected_quantity']; ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900">
                                                <?php echo $c['counted_quantity'] !== null ? $c['counted_quantity'] : '<span class="text-slate-400 font-normal">Pending</span>'; ?>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-bold <?php echo ($v !== null && $v != 0) ? 'text-rose-600' : 'text-slate-400'; ?>">
                                                <?php echo ($v !== null) ? ($v > 0 ? "+$v" : "$v") : '—'; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border <?php echo getCycleBadge($c['count_status']); ?>">
                                                    <?php echo htmlspecialchars($c['count_status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y', strtotime($c['created_at'])); ?></td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <?php if ($c['counted_quantity'] === null || $c['count_status'] === 'Scheduled'): ?>
                                                    <button type="button" onclick='openResolveModal(<?php echo json_encode($c); ?>)' class="px-2 py-1 bg-primary text-white rounded text-[10px] font-bold">
                                                        Input Count
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 font-semibold">Audited</span>
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

    <!-- Modals -->
    <div id="count-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Record Cycle Count</h3>
                <button type="button" onclick="closeCountModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_count"/>
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
                        <label>Bin (Optional)</label>
                        <select name="bin_id">
                            <option value="">None / General Zone</option>
                            <?php foreach ($bins_list as $b): ?>
                                <option value="<?php echo $b['bin_id']; ?>"><?php echo htmlspecialchars($b['bin_code'] . ' (' . ($b['item_name'] ?: 'Empty') . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Expected Quantity</label>
                        <input type="number" name="expected_quantity" min="0" value="0" required/>
                    </div>
                    <div class="form-field">
                        <label>Physical Counted</label>
                        <input type="number" name="counted_quantity" min="0" placeholder="Optional for scheduling"/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Audit Notes</label>
                    <textarea name="notes" rows="2" placeholder="Observation notes..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCountModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save Audit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="resolve-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Input Physical Count</h3>
                <button type="button" onclick="closeResolveModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="resolve_count"/>
                <input type="hidden" name="count_id" id="resolve-count-id"/>
                <div class="bg-slate-50 p-3 rounded-lg text-xs mb-3">
                    <p><strong>Expected Quantity:</strong> <span id="resolve-expected-qty"></span></p>
                </div>
                <div class="form-field">
                    <label>Actual Counted Quantity</label>
                    <input type="number" name="counted_quantity" min="0" required/>
                </div>
                <div class="form-field">
                    <label>Discrepancy Notes</label>
                    <textarea name="notes" rows="2" placeholder="Reason for variance..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeResolveModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Submit Count</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCountModal() { document.getElementById('count-modal').style.display = 'flex'; }
        function closeCountModal() { document.getElementById('count-modal').style.display = 'none'; }
        function openResolveModal(count) {
            document.getElementById('resolve-count-id').value = count.count_id;
            document.getElementById('resolve-expected-qty').textContent = count.expected_quantity;
            document.getElementById('resolve-modal').style.display = 'flex';
        }
        function closeResolveModal() { document.getElementById('resolve-modal').style.display = 'none'; }
    </script>
</body>
</html>