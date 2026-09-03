<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Only administrators can access activity logs
if ($_SESSION['role'] !== 'Administrator') {
    header("Location: dashboard.html");
    exit();
}

require_once 'core_connection.php';

$section_title = "System Activity Audit Trail";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Administrator';

$db_error = null;
$flash = null;

// Enhanced logging function that can be called from other pages
function logActivity($conn, $user_id, $username, $action, $details = '', $ip_address = '', $user_agent = '') {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, username, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $username, $action, $details, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Log the current page view
if (!$conn->connect_error) {
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    logActivity($conn, $_SESSION['user_id'], $_SESSION['username'], 'Viewed Activity Log', 'Audit trail access', $current_ip, $current_ua);
}

// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!$conn->connect_error) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Timestamp', 'User', 'Action', 'Details', 'IP Address', 'User Agent']);
        
        $sql = "SELECT * FROM activity_log ORDER BY created_at DESC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['log_id'],
                    $row['created_at'],
                    $row['username'],
                    $row['action'],
                    $row['details'],
                    $row['ip_address'],
                    $row['user_agent']
                ]);
            }
        }
        fclose($output);
        $conn->close();
        exit;
    }
}

$logs = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

if (!$conn->connect_error) {
    $sql = "SELECT * FROM activity_log WHERE 1=1";
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $sql .= " AND (action LIKE ? OR details LIKE ? OR username LIKE ?)";
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    
    if ($filter_action !== '') {
        $sql .= " AND action = ?";
        $params[] = $filter_action;
        $types .= 's';
    }
    
    if ($filter_user !== '') {
        $sql .= " AND username = ?";
        $params[] = $filter_user;
        $types .= 's';
    }
    
    if ($date_from !== '') {
        $sql .= " AND created_at >= ?";
        $params[] = $date_from . ' 00:00:00';
        $types .= 's';
    }
    
    if ($date_to !== '') {
        $sql .= " AND created_at <= ?";
        $params[] = $date_to . ' 23:59:59';
        $types .= 's';
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 500";
    
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
            $logs[] = $row;
        }
    }
    
    // Get unique actions for filter dropdown
    $actions_result = $conn->query("SELECT DISTINCT action FROM activity_log ORDER BY action ASC");
    $available_actions = [];
    if ($actions_result) {
        while ($row = $actions_result->fetch_assoc()) {
            $available_actions[] = $row['action'];
        }
    }
    
    // Get unique users for filter dropdown
    $users_result = $conn->query("SELECT DISTINCT username FROM activity_log ORDER BY username ASC");
    $available_users = [];
    if ($users_result) {
        while ($row = $users_result->fetch_assoc()) {
            $available_users[] = $row['username'];
        }
    }
} else {
    $db_error = "Database connection offline.";
}

function getActionBadgeClass($action) {
    // Determine badge color based on action type
    $danger_actions = ['Deleted', 'Failed', 'Error', 'Rejected', 'Deactivated'];
    $warning_actions = ['Updated', 'Modified', 'Changed', 'Pending'];
    $success_actions = ['Created', 'Added', 'Approved', 'Activated', 'Completed', 'Success'];
    $info_actions = ['Viewed', 'Accessed', 'Logged in', 'Exported'];
    
    foreach ($danger_actions as $da) {
        if (stripos($action, $da) !== false) return 'bg-red-100 text-red-800 border-red-200';
    }
    foreach ($warning_actions as $wa) {
        if (stripos($action, $wa) !== false) return 'bg-amber-100 text-amber-800 border-amber-200';
    }
    foreach ($success_actions as $sa) {
        if (stripos($action, $sa) !== false) return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    }
    foreach ($info_actions as $ia) {
        if (stripos($action, $ia) !== false) return 'bg-blue-100 text-blue-800 border-blue-200';
    }
    
    return 'bg-slate-100 text-slate-800 border-slate-200';
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
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">ISMERS Administration Console</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">System Activity Audit Trail</h1>
                        <p class="text-slate-500 text-sm mt-1">Comprehensive log of all system actions for compliance and security monitoring.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="?export=csv" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-emerald-700 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">file_download</span> Export CSV
                        </a>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200">
                        <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                            </div>
                            <select name="action" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none">
                                <option value="">All Actions</option>
                                <?php foreach ($available_actions as $act): ?>
                                    <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $filter_action === $act ? 'selected' : ''; ?>><?php echo htmlspecialchars($act); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="user" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none">
                                <option value="">All Users</option>
                                <?php foreach ($available_users as $usr): ?>
                                    <option value="<?php echo htmlspecialchars($usr); ?>" <?php echo $filter_user === $usr ? 'selected' : ''; ?>><?php echo htmlspecialchars($usr); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="flex gap-2">
                                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                            </div>
                        </form>
                        <div class="flex gap-2 mt-3">
                            <button type="submit" form="filter-form" class="px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-semibold">Apply Filters</button>
                            <a href="activity_log.php" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Timestamp</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">User</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Action</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Details</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">IP Address</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-slate-400">No activity logs found matching your criteria.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): 
                                        $action_badge = getActionBadgeClass($log['action']);
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?php echo date('M j, Y g:i:s A', strtotime($log['created_at'])); ?></td>
                                            <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($log['username']); ?></td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $action_badge; ?>">
                                                    <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate"><?php echo htmlspecialchars($log['details'] ?: '—'); ?></td>
                                            <td class="px-4 py-3 font-mono text-slate-400 text-[10px]"><?php echo htmlspecialchars($log['ip_address'] ?: '—'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="px-4 py-3 border-t border-slate-200 bg-slate-50 text-xs text-slate-500">
                        Showing last 500 records. Use filters to narrow down results or export for full history.
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>