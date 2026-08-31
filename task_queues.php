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

    if ($action === 'create_task') {
        $sku = trim($_POST['sku'] ?? '');
        $item_name = trim($_POST['item_name'] ?? '');
        $source_location = trim($_POST['source_location'] ?? 'Receiving Dock');
        $destination_location = trim($_POST['destination_location'] ?? '');
        $quantity = (int)$_POST['quantity'];
        $movement_type = $_POST['movement_type'] ?? 'Transfer';
        $assigned_to = (int)($_SESSION['user_id'] ?? 1);

        if ($item_name === '' || $quantity <= 0) {
            $db_error = "Item Name and a positive Quantity are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO stock_movements (sku, item_name, source_location, destination_location, quantity, movement_type, task_status, assigned_to) VALUES (?, ?, ?, ?, ?, ?, 'Queued', ?)");
            $stmt->bind_param("ssssisi", $sku, $item_name, $source_location, $destination_location, $quantity, $movement_type, $assigned_to);
            if ($stmt->execute()) {
                $flash = "Task queued successfully.";
            } else {
                $db_error = "Failed to queue task: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'update_task_status') {
        $movement_id = (int)$_POST['movement_id'];
        $task_status = $_POST['task_status'];
        $completed_clause = ($task_status === 'Completed') ? ", completed_at = NOW()" : "";

        $stmt = $conn->prepare("UPDATE stock_movements SET task_status = ? $completed_clause WHERE movement_id = ?");
        $stmt->bind_param("si", $task_status, $movement_id);
        if ($stmt->execute()) {
            $flash = "Task marked as '$task_status'.";
        } else {
            $db_error = "Failed to update task: " . $stmt->error;
        }
        $stmt->close();
    }
}

$tasks = [];
if (!$conn->connect_error) {
    $sql = "SELECT * FROM stock_movements ORDER BY FIELD(task_status, 'In Progress', 'Queued', 'Completed', 'Cancelled'), created_at DESC LIMIT 100";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) $tasks[] = $row;
    }
} else {
    $db_error = "Database offline.";
}

function getTaskBadge($status) {
    switch ($status) {
        case 'Completed': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'In Progress': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Queued': return 'bg-amber-100 text-amber-800 border-amber-200';
        default: return 'bg-slate-100 text-slate-800 border-slate-200';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Task Queues</title>
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
                <span class="text-slate-600 text-sm font-medium">Task Queues</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Task Queues & Stock Movement</h1>
                        <p class="text-slate-500 text-sm mt-1">Direct putaway, picking runs, and location relocations to warehouse workers.</p>
                    </div>
                    <button type="button" onclick="openTaskModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add_task</span> Queue Movement
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
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">SKU / Item</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Route</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Created</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($tasks)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No movement tasks currently queued.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $t): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-semibold text-slate-900 flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[16px] text-primary">
                                                    <?php echo $t['movement_type'] === 'Inbound' ? 'login' : ($t['movement_type'] === 'Outbound' ? 'logout' : 'sync_alt'); ?>
                                                </span>
                                                <?php echo htmlspecialchars($t['movement_type']); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($t['item_name']); ?></div>
                                                <div class="text-[10px] font-mono text-slate-400"><?php echo htmlspecialchars($t['sku'] ?: 'N/A'); ?></div>
                                            </td>
                                            <td class="px-4 py-3 font-mono text-slate-600">
                                                <?php echo htmlspecialchars($t['source_location'] ?: 'Dock'); ?> &rarr; <?php echo htmlspecialchars($t['destination_location'] ?: 'Floor'); ?>
                                            </td>
                                            <td class="px-4 py-3 font-bold text-slate-900"><?php echo $t['quantity']; ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border <?php echo getTaskBadge($t['task_status']); ?>">
                                                    <?php echo htmlspecialchars($t['task_status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y g:i A', strtotime($t['created_at'])); ?></td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <?php if ($t['task_status'] !== 'Completed'): ?>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="update_task_status"/>
                                                        <input type="hidden" name="movement_id" value="<?php echo (int)$t['movement_id']; ?>"/>
                                                        <input type="hidden" name="task_status" value="Completed"/>
                                                        <button type="submit" class="px-2.5 py-1 bg-emerald-600 text-white rounded text-[11px] font-bold">Complete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 font-semibold">Done</span>
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

    <div id="task-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Queue Movement Task</h3>
                <button type="button" onclick="closeTaskModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_task"/>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Movement Type</label>
                        <select name="movement_type" required>
                            <option value="Inbound">Inbound</option>
                            <option value="Outbound">Outbound</option>
                            <option value="Transfer" selected>Transfer</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Quantity</label>
                        <input type="number" name="quantity" min="1" value="1" required/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>SKU (Optional)</label>
                        <input type="text" name="sku" placeholder="SYS-1001"/>
                    </div>
                    <div class="form-field">
                        <label>Item Name</label>
                        <input type="text" name="item_name" placeholder="Steel Pallet Rack" required/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Source Location</label>
                        <input type="text" name="source_location" placeholder="Receiving Dock / BIN-A1" required/>
                    </div>
                    <div class="form-field">
                        <label>Destination Location</label>
                        <input type="text" name="destination_location" placeholder="BIN-B2-04" required/>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeTaskModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Queue Movement</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openTaskModal() { document.getElementById('task-modal').style.display = 'flex'; }
        function closeTaskModal() { document.getElementById('task-modal').style.display = 'none'; }
    </script>
</body>
</html>