<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'ims_connection.php';
$section_title = "Inventory Management System (IMS)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';
$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_disposition') {
        $item_id = (int)$_POST['item_id'];
        $disp_type = $_POST['disposition_type'];
        $qty = (int)$_POST['quantity'];
        $salvage = (float)$_POST['salvage_value'];
        $reason = trim($_POST['reason'] ?? '');
        $disp_date = !empty($_POST['disposition_date']) ? $_POST['disposition_date'] : date('Y-m-d');
        $approved_by = $admin_user;

        if ($item_id <= 0 || $qty <= 0 || $reason === '') {
            $db_error = "Item, Quantity, and Reason are required.";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO asset_dispositions (item_id, disposition_type, quantity, salvage_value, reason, disposed_by, approved_by, status, disposition_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'Completed', ?)");
                $stmt->bind_param("isidssss", $item_id, $disp_type, $qty, $salvage, $reason, $admin_user, $approved_by, $disp_date);
                $stmt->execute();
                $stmt->close();

                // Deduct disposed units from stock inventory
                $upd = $conn->prepare("UPDATE stock_inventory SET quantity_on_hand = GREATEST(0, quantity_on_hand - ?) WHERE item_id = ?");
                $upd->bind_param("ii", $qty, $item_id);
                $upd->execute();
                $upd->close();

                $conn->commit();
                $flash = "Asset disposition recorded and inventory written off.";
            } catch (Exception $e) {
                $conn->rollback();
                $db_error = "Disposition error: " . $e->getMessage();
            }
        }
    }
}

$dispositions = [];
$items_list = [];
$total_salvage = 0;

if (!$conn->connect_error) {
    $i_res = $conn->query("SELECT item_id, sku, item_name FROM item_master ORDER BY item_name ASC");
    if ($i_res) while ($r = $i_res->fetch_assoc()) $items_list[] = $r;

    $sql = "SELECT ad.*, im.sku, im.item_name, im.unit 
            FROM asset_dispositions ad
            JOIN item_master im ON ad.item_id = im.item_id
            ORDER BY ad.disposition_date DESC LIMIT 50";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $total_salvage += (float)$r['salvage_value'];
            $dispositions[] = $r;
        }
    }
} else {
    $db_error = "Database offline.";
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Asset Disposition</title>
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
                <span class="font-bold text-slate-800 text-sm">Inventory Management System</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-600 text-sm font-medium">Asset Disposition</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Asset Disposition & Scrapping</h1>
                        <p class="text-slate-500 text-sm mt-1">Manage decommissioned stock, scrapping write-offs, and salvage recovery value.</p>
                    </div>
                    <button type="button" onclick="openDispModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">delete_sweep</span> Initiate Disposition
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
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Item / SKU</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Method</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Qty</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Salvage Value</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Reason</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($dispositions)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No asset disposition records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($dispositions as $d): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y', strtotime($d['disposition_date'])); ?></td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($d['item_name']); ?></div>
                                                <div class="text-[10px] font-mono text-indigo-600"><?php echo htmlspecialchars($d['sku']); ?></div>
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-slate-700"><?php echo htmlspecialchars($d['disposition_type']); ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900"><?php echo $d['quantity']; ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-semibold text-emerald-700">&#8369;<?php echo number_format($d['salvage_value'], 2); ?></td>
                                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate"><?php echo htmlspecialchars($d['reason']); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-800 border border-slate-200">
                                                    <?php echo htmlspecialchars($d['status']); ?>
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

    <!-- Modal -->
    <div id="disp-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Initiate Asset Disposition</h3>
                <button type="button" onclick="closeDispModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_disposition"/>
                <div class="form-field">
                    <label>Item</label>
                    <select name="item_id" required>
                        <?php foreach ($items_list as $it): ?>
                            <option value="<?php echo $it['item_id']; ?>"><?php echo htmlspecialchars($it['sku'] . ' — ' . $it['item_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Disposition Method</label>
                        <select name="disposition_type" required>
                            <option value="Scrapped">Scrapped</option>
                            <option value="Sold/Liquidated">Sold / Liquidated</option>
                            <option value="Donated">Donated</option>
                            <option value="Recycled">Recycled</option>
                            <option value="Written Off">Written Off</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Quantity Disposed</label>
                        <input type="number" name="quantity" min="1" value="1" required/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Salvage Value Recovered (&#8369;)</label>
                        <input type="number" step="0.01" name="salvage_value" min="0" value="0.00"/>
                    </div>
                    <div class="form-field">
                        <label>Disposition Date</label>
                        <input type="date" name="disposition_date" value="<?php echo date('Y-m-d'); ?>" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Reason for Disposition</label>
                    <textarea name="reason" rows="2" placeholder="e.g. End of life, beyond economical repair..." required></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeDispModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Confirm Disposition</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openDispModal() { document.getElementById('disp-modal').style.display = 'flex'; }
        function closeDispModal() { document.getElementById('disp-modal').style.display = 'none'; }
    </script>
</body>
</html>