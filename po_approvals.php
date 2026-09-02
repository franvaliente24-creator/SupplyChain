<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'pom_connection.php';

$section_title = "Purchase Order Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Workflow lives entirely on orders.status — no new columns needed:
// Draft --submit--> Pending --approve--> Approved
//                        \--reject--> Draft
// Any stage --cancel--> Cancelled

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $order_id = (int)($_POST['order_id'] ?? 0);

    $transitions = [
        'submit'  => ['from' => ['Draft'],           'to' => 'Pending',   'log' => 'submitted for approval', 'class' => 'status-pill-info'],
        'approve' => ['from' => ['Pending'],          'to' => 'Approved',  'log' => 'approved',               'class' => 'status-pill-success'],
        'reject'  => ['from' => ['Pending'],          'to' => 'Draft',     'log' => 'sent back to draft',     'class' => 'status-pill-warning'],
        'cancel'  => ['from' => ['Draft', 'Pending'], 'to' => 'Cancelled', 'log' => 'cancelled',              'class' => 'status-pill-critical'],
    ];

    if ($order_id > 0 && isset($transitions[$action])) {
        $t = $transitions[$action];

        $placeholders = implode(',', array_fill(0, count($t['from']), '?'));
        $stmt = $conn->prepare("SELECT order_id, order_number FROM orders WHERE order_id = ? AND status IN ($placeholders)");
        $bindTypes = 'i' . str_repeat('s', count($t['from']));
        $bindParams = array_merge([$order_id], $t['from']);
        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $order = $res->fetch_assoc();
            $stmt->close();

            $upd = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $upd->bind_param("si", $t['to'], $order_id);
            if ($upd->execute()) {
                $flash = "PO " . $order['order_number'] . " " . $t['log'] . ".";
                $msg = "Purchase Order " . $order['order_number'] . " " . $t['log'] . " by " . $admin_user;
                $safe = $conn->real_escape_string($msg);
                $safeClass = $conn->real_escape_string($t['class']);
                $safeStatus = $conn->real_escape_string($t['to']);
                $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$safe', '$safeStatus', '$safeClass')");
            } else {
                $db_error = "Failed to update order: " . $upd->error;
            }
            $upd->close();
        } else {
            $db_error = "That order is no longer in a state that allows this action. It may have already been updated.";
            $stmt->close();
        }
    }
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$orders = [];
if (!$conn->connect_error) {
    $sql = "SELECT o.order_id, o.order_number, o.supplier_id, o.order_date, o.expected_date,
               o.status, o.total_amount, o.supplier_name
        FROM orders o
        WHERE o.status IN ('Draft', 'Pending')";
    if ($search !== '') {
        $sql .= " AND (o.order_number LIKE ? OR s.supplier_name LIKE ?)";
    }
    $sql .= " ORDER BY FIELD(o.status, 'Pending', 'Draft'), o.order_date ASC";

    if ($search !== '') {
        $stmt = $conn->prepare($sql);
        $like = "%$search%";
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    } else {
        $db_error = $db_error ?? ("Query failed: " . $conn->error);
    }
}

function approvalStatusMeta($status) {
    switch ($status) {
        case 'Draft':   return ['label' => 'Draft',   'class' => 'status-badge-archived'];
        case 'Pending': return ['label' => 'Pending',  'class' => 'status-badge-maintenance'];
        default:        return ['label' => $status,    'class' => 'status-badge-archived'];
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — PO Approvals</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        .status-badge { display:inline-block; padding:0.2rem 0.65rem; border-radius:9999px; font-size:0.7rem; font-weight:600; }
        .status-badge-maintenance { background:#fef3c7; color:#92400e; }
        .status-badge-archived    { background:#e2e8f0; color:#475569; }
        .btn-approve { background:#dcfce7; color:#166534; }
        .btn-reject  { background:#fef3c7; color:#92400e; }
        .btn-cancel  { background:#fee2e2; color:#991b1b; }
        .btn-submit  { background:#dbeafe; color:#1e40af; }
        .action-btn { padding:0.35rem 0.75rem; border-radius:0.5rem; font-size:0.7rem; font-weight:700; border:none; cursor:pointer; }
        .action-btn:hover { opacity:0.85; }
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
                        <h1 class="text-2xl font-bold text-slate-900">PO Approvals</h1>
                        <p class="text-slate-500 text-sm mt-1">Draft and pending purchase orders awaiting sign-off before release to suppliers.</p>
                    </div>
                    <form method="get" class="relative w-full sm:max-w-xs">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search PO # or supplier..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                    </form>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900">Approval Queue</h2>
                        <span class="text-xs text-slate-400"><?php echo count($orders); ?> awaiting action</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">PO Number</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Order Date</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($orders)): ?>
                                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">Nothing waiting on approval right now.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): $meta = approvalStatusMeta($order['status']); ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-mono font-semibold text-slate-900"><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($order['supplier_name'] ?: 'Unassigned'); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                            <td class="px-6 py-4 font-semibold text-slate-900">₱<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                            <td class="px-6 py-4"><span class="status-badge <?php echo $meta['class']; ?>"><?php echo htmlspecialchars($meta['label']); ?></span></td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap space-x-1.5">
                                                <?php if ($order['status'] === 'Draft'): ?>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="action" value="submit"/>
                                                        <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>"/>
                                                        <button type="submit" class="action-btn btn-submit">Submit for Approval</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="action" value="approve"/>
                                                        <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>"/>
                                                        <button type="submit" class="action-btn btn-approve">Approve</button>
                                                    </form>
                                                    <form method="post" class="inline">
                                                        <input type="hidden" name="action" value="reject"/>
                                                        <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>"/>
                                                        <button type="submit" class="action-btn btn-reject">Send Back</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="post" class="inline" onsubmit="return confirm('Cancel this purchase order?');">
                                                    <input type="hidden" name="action" value="cancel"/>
                                                    <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>"/>
                                                    <button type="submit" class="action-btn btn-cancel">Cancel</button>
                                                </form>
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
</body>
</html>