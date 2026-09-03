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

    if ($action === 'create_adjustment') {
        $item_id = (int)$_POST['item_id'];
        $adj_type = $_POST['adjustment_type'];
        $qty_change = (int)$_POST['quantity_change'];
        $reason = trim($_POST['reason'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($item_id <= 0 || $qty_change == 0 || $reason === '') {
            $db_error = "Item, a non-zero Quantity Change, and a Mandatory Reason are required.";
        } else {
            $conn->begin_transaction();
            try {
                // Get previous stock
                $s_stmt = $conn->query("SELECT quantity_on_hand FROM stock_inventory WHERE item_id = $item_id FOR UPDATE");
                $curr = $s_stmt->fetch_assoc();
                $prev_qty = $curr ? (int)$curr['quantity_on_hand'] : 0;
                
                $diff = ($adj_type === 'Deduction' || $adj_type === 'Damage/Loss') ? -abs($qty_change) : abs($qty_change);
                $new_qty = max(0, $prev_qty + $diff);

                // 1. Update stock
                $upd = $conn->prepare("UPDATE stock_inventory SET quantity_on_hand = ? WHERE item_id = ?");
                $upd->bind_param("ii", $new_qty, $item_id);
                $upd->execute();
                $upd->close();

                // 2. Insert audit log
                $ins = $conn->prepare("INSERT INTO stock_adjustments (item_id, adjustment_type, quantity_change, previous_qty, new_qty, reason, notes, adjusted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->bind_param("isiiisss", $item_id, $adj_type, $diff, $prev_qty, $new_qty, $reason, $notes, $admin_user);
                $ins->execute();
                $ins->close();

                $conn->commit();
                $flash = "Stock adjustment logged. New on-hand balance: $new_qty.";
            } catch (Exception $e) {
                $conn->rollback();
                $db_error = "Adjustment error: " . $e->getMessage();
            }
        }
    }
}

$adjustments = [];
$items_list = [];

if (!$conn->connect_error) {
    $i_res = $conn->query("SELECT im.item_id, im.sku, im.item_name, COALESCE(si.quantity_on_hand, 0) as on_hand 
                          FROM item_master im 
                          LEFT JOIN stock_inventory si ON im.item_id = si.item_id 
                          WHERE im.status != 'Discontinued' ORDER BY im.item_name ASC");
    if ($i_res) while ($r = $i_res->fetch_assoc()) $items_list[] = $r;

    $sql = "SELECT sa.*, im.sku, im.item_name, im.unit 
            FROM stock_adjustments sa
            JOIN item_master im ON sa.item_id = im.item_id
            ORDER BY sa.created_at DESC LIMIT 50";
    $res = $conn->query($sql);
    if ($res) while ($r = $res->fetch_assoc()) $adjustments[] = $r;
} else {
    $db_error = "Database offline.";
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Adjustments</title>
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
                <span class="text-slate-600 text-sm font-medium">Adjustments</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Stock Adjustments & Write-Downs</h1>
                        <p class="text-slate-500 text-sm mt-1">Audit trail for manual quantity reconciliations, damaged goods, and inventory discrepancies.</p>
                    </div>
                    <button type="button" onclick="openAdjModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">tune</span> Record Adjustment
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
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Adjustment Type</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Variance</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Prev &rarr; New</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Reason</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Auditor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($adjustments)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No stock adjustments logged.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($adjustments as $adj): 
                                        $diff = (int)$adj['quantity_change'];
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?php echo date('M j, Y g:i A', strtotime($adj['created_at'])); ?></td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($adj['item_name']); ?></div>
                                                <div class="text-[10px] font-mono text-indigo-600"><?php echo htmlspecialchars($adj['sku']); ?></div>
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-slate-700"><?php echo htmlspecialchars($adj['adjustment_type']); ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-bold <?php echo $diff < 0 ? 'text-rose-600' : 'text-emerald-600'; ?>">
                                                <?php echo $diff > 0 ? "+$diff" : "$diff"; ?>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono text-slate-500">
                                                <?php echo $adj['previous_qty']; ?> &rarr; <span class="font-bold text-slate-900"><?php echo $adj['new_qty']; ?></span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate"><?php echo htmlspecialchars($adj['reason']); ?></td>
                                            <td class="px-4 py-3 text-slate-500"><?php echo htmlspecialchars($adj['adjusted_by']); ?></td>
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
    <div id="adj-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Record Stock Adjustment</h3>
                <button type="button" onclick="closeAdjModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_adjustment"/>
                <div class="form-field">
                    <label>Select Item</label>
                    <select name="item_id" required>
                        <?php foreach ($items_list as $it): ?>
                            <option value="<?php echo $it['item_id']; ?>"><?php echo htmlspecialchars($it['sku'] . ' — ' . $it['item_name'] . ' (Bal: ' . $it['on_hand'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Adjustment Type</label>
                        <select name="adjustment_type" required>
                            <option value="Addition">Stock Addition (+)</option>
                            <option value="Deduction">Manual Deduction (-)</option>
                            <option value="Correction">Physical Count Correction</option>
                            <option value="Damage/Loss">Damage / Spoilage / Loss</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Quantity Count</label>
                        <input type="number" name="quantity_change" min="1" value="1" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Mandatory Reason</label>
                    <input type="text" name="reason" placeholder="e.g. Audit discrepancy, water damage in bay" required/>
                </div>
                <div class="form-field">
                    <label>Notes (Optional)</label>
                    <textarea name="notes" rows="2" placeholder="Additional audit details..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAdjModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Post Adjustment</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openAdjModal() { document.getElementById('adj-modal').style.display = 'flex'; }
        function closeAdjModal() { document.getElementById('adj-modal').style.display = 'none'; }
    </script>
</body>
</html>