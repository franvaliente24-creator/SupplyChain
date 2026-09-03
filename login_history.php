<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Only administrators can access login history
if ($_SESSION['role'] !== 'Administrator') {
    header("Location: dashboard.html");
    exit();
}

require_once 'core_connection.php';

$section_title = "Login History Monitoring";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Administrator';

$db_error = null;
$flash = null;

// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!$conn->connect_error) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="login_history_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Timestamp', 'User', 'IP Address', 'User Agent', 'Status', 'Failure Reason']);
        
        $sql = "SELECT * FROM login_history ORDER BY created_at DESC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['history_id'],
                    $row['created_at'],
                    $row['username'],
                    $row['ip_address'],
                    $row['user_agent'],
                    $row['login_status'],
                    $row['failure_reason']
                ]);
            }
        }
        fclose($output);
        $conn->close();
        exit;
    }
}

$login_history = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$filter_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

// Check if login_history table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'login_history'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}
$check_table->free();

if ($table_exists && !$conn->connect_error) {
    $sql = "SELECT * FROM login_history WHERE 1=1";
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $sql .= " AND (username LIKE ? OR ip_address LIKE ?)";
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
    
    if ($filter_status !== '') {
        $sql .= " AND login_status = ?";
        $params[] = $filter_status;
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
            $login_history[] = $row;
        }
    }
    
    // Get unique users for filter dropdown
    $users_result = $conn->query("SELECT DISTINCT username FROM login_history ORDER BY username ASC");
    $available_users = [];
    if ($users_result) {
        while ($row = $users_result->fetch_assoc()) {
            $available_users[] = $row['username'];
        }
    }
} else {
    if (!$table_exists) {
        $db_error = "Login history table not found. Please run the schema_updates.sql file to create it.";
    } else {
        $db_error = "Database connection offline.";
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
        .status-success { background: #dcfce7; color: #166534; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        
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
                        <h1 class="text-2xl font-bold text-slate-900">Login History Monitoring</h1>
                        <p class="text-slate-500 text-sm mt-1">Track successful and failed login attempts for security monitoring and suspicious activity detection.</p>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($table_exists): ?>
                            <a href="?export=csv" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-emerald-700 transition shadow-sm inline-flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[18px]">file_download</span> Export CSV
                            </a>
                        <?php endif; ?>
                    </div>
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
                            <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search username or IP..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                                </div>
                                <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none">
                                    <option value="">All Status</option>
                                    <option value="success" <?php echo $filter_status === 'success' ? 'selected' : ''; ?>>Success</option>
                                    <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
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
                                <button type="submit" class="px-3 py-1.5 bg-primary text-white rounded-lg text-xs font-semibold">Apply Filters</button>
                                <a href="login_history.php" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Timestamp</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">User</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">IP Address</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Details</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($login_history)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-10 text-center text-slate-400">No login history found matching your criteria.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($login_history as $login): 
                                            $status_class = $login['login_status'] === 'success' ? 'status-success' : 'status-failed';
                                        ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?php echo date('M j, Y g:i:s A', strtotime($login['created_at'])); ?></td>
                                                <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($login['username']); ?></td>
                                                <td class="px-4 py-3 font-mono text-slate-600"><?php echo htmlspecialchars($login['ip_address'] ?: '—'); ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($login['login_status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600 max-w-xs truncate"><?php echo htmlspecialchars($login['failure_reason'] ?: 'Successful login'); ?></td>
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
                <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3 rounded-lg">
                        <p><strong>Login history monitoring is not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>