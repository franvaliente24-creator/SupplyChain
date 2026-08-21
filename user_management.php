<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Only administrators can access user management
if ($_SESSION['role'] !== 'Administrator') {
    header("Location: dashboard.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "User Account Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Administrator';

$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'Staff';
        $password = $_POST['password'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        $validRoles = ['Administrator', 'Supply Chain Manager', 'Staff'];

        // Validation
        if (!$username || !$email || !$password) {
            $db_error = "Username, email, and password are required.";
        } elseif (strlen($username) < 2 || strlen($username) > 50) {
            $db_error = "Username must be between 2 and 50 characters.";
        } elseif (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $db_error = "Username may only contain letters, numbers, dots, underscores, and hyphens.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $db_error = "Please enter a valid email address.";
        } elseif (strlen($password) < 8) {
            $db_error = "Password must be at least 8 characters long.";
        } elseif (!in_array($role, $validRoles, true)) {
            $db_error = "Invalid role selected.";
        } else {
            // Check for existing username or email
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $exists = $stmt->get_result();
            if ($exists && $exists->num_rows > 0) {
                $db_error = "An account with that username or email already exists.";
                $stmt->close();
            } else {
                $stmt->close();
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $ins = $conn->prepare('INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?)');
                $ins->bind_param('ssssi', $username, $email, $passwordHash, $role, $is_active);

                if ($ins->execute()) {
                    $flash = "User account created successfully.";
                    // Log the action
                    $log_msg = "Created new user account: $username ($role)";
                    $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'New', 'status-pill-accent')");
                } else {
                    $db_error = "Failed to create user: " . $ins->error;
                }
                $ins->close();
            }
        }
    }

    if ($action === 'edit_user') {
        $user_id = (int)$_POST['user_id'];
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'Staff';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        $validRoles = ['Administrator', 'Supply Chain Manager', 'Staff'];

        // Validation
        if (!$username || !$email) {
            $db_error = "Username and email are required.";
        } elseif (strlen($username) < 2 || strlen($username) > 50) {
            $db_error = "Username must be between 2 and 50 characters.";
        } elseif (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            $db_error = "Username may only contain letters, numbers, dots, underscores, and hyphens.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $db_error = "Please enter a valid email address.";
        } elseif (!in_array($role, $validRoles, true)) {
            $db_error = "Invalid role selected.";
        } elseif (!empty($password) && strlen($password) < 8) {
            $db_error = "Password must be at least 8 characters long.";
        } else {
            // Check for existing username or email (excluding current user)
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ? LIMIT 1');
            $stmt->bind_param('ssi', $username, $email, $user_id);
            $stmt->execute();
            $exists = $stmt->get_result();
            if ($exists && $exists->num_rows > 0) {
                $db_error = "An account with that username or email already exists.";
                $stmt->close();
            } else {
                $stmt->close();

                if (!empty($password)) {
                    // Update with new password
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                    $upd = $conn->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, role = ?, is_active = ? WHERE user_id = ?');
                    $upd->bind_param('ssssii', $username, $email, $passwordHash, $role, $is_active, $user_id);
                } else {
                    // Update without password change
                    $upd = $conn->prepare('UPDATE users SET username = ?, email = ?, role = ?, is_active = ? WHERE user_id = ?');
                    $upd->bind_param('ssssi', $username, $email, $role, $is_active, $user_id);
                }

                if ($upd->execute()) {
                    $flash = "User account updated successfully.";
                    $log_msg = "Updated user account: $username ($role)";
                    $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'Updated', 'status-pill-success')");
                } else {
                    $db_error = "Failed to update user: " . $upd->error;
                }
                $upd->close();
            }
        }
    }

    if ($action === 'deactivate_user') {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent deactivating yourself
        if ($user_id === (int)$_SESSION['user_id']) {
            $db_error = "You cannot deactivate your own account.";
        } else {
            $stmt = $conn->prepare('UPDATE users SET is_active = 0 WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            
            if ($stmt->execute()) {
                $flash = "User account deactivated successfully.";
                $log_msg = "Deactivated user account ID: $user_id";
                $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'Deactivated', 'status-pill-warning')");
            } else {
                $db_error = "Failed to deactivate user: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'activate_user') {
        $user_id = (int)$_POST['user_id'];
        $stmt = $conn->prepare('UPDATE users SET is_active = 1 WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $flash = "User account activated successfully.";
            $log_msg = "Activated user account ID: $user_id";
            $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'Activated', 'status-pill-success')");
        } else {
            $db_error = "Failed to activate user: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'delete_user') {
        $user_id = (int)$_POST['user_id'];
        
        // Prevent deleting yourself
        if ($user_id === (int)$_SESSION['user_id']) {
            $db_error = "You cannot delete your own account.";
        } else {
            $conn->begin_transaction();
            try {
                // Delete from remember_tokens
                $del1 = $conn->prepare('DELETE FROM remember_tokens WHERE user_id = ?');
                $del1->bind_param('i', $user_id);
                $del1->execute();
                $del1->close();

                // Delete from password_resets
                $del2 = $conn->prepare('DELETE FROM password_resets WHERE user_id = ?');
                $del2->bind_param('i', $user_id);
                $del2->execute();
                $del2->close();

                // Delete the user
                $del3 = $conn->prepare('DELETE FROM users WHERE user_id = ?');
                $del3->bind_param('i', $user_id);
                $del3->execute();
                $del3->close();

                $conn->commit();
                $flash = "User account deleted permanently.";
                $log_msg = "Deleted user account ID: $user_id";
                $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'Deleted', 'status-pill-critical')");
            } catch (Exception $e) {
                $conn->rollback();
                $db_error = "Failed to delete user: " . $e->getMessage();
            }
        }
    }
}

$users = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$conn->connect_error) {
    $sql = "SELECT user_id, username, email, role, is_active, created_at, last_login FROM users";
    if ($search !== '') {
        $sql .= " WHERE username LIKE ? OR email LIKE ? OR role LIKE ?";
    }
    $sql .= " ORDER BY created_at DESC";

    if ($search !== '') {
        $stmt = $conn->prepare($sql);
        $like = "%$search%";
        $stmt->bind_param("sss", $like, $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} else {
    $db_error = "Database connection offline.";
}

function getRoleBadgeClass($role) {
    switch ($role) {
        case 'Administrator': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'Supply Chain Manager': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Staff': return 'bg-slate-100 text-slate-800 border-slate-200';
        default: return 'bg-slate-100 text-slate-800 border-slate-200';
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
        .status-badge-active { background: #dcfce7; color: #166534; }
        .status-badge-inactive { background: #fee2e2; color: #991b1b; }
        
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 100; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 1rem; width: 100%; max-width: 32rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-height: 90vh; overflow-y: auto;
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
                <span class="font-bold text-slate-800 text-sm">ISMERS Administration Console</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">User Account Management</h1>
                        <p class="text-slate-500 text-sm mt-1">Create, edit, deactivate, and manage system user accounts and permissions.</p>
                    </div>
                    <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">person_add</span> Create New User
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <form method="get" class="relative w-full sm:max-w-sm">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search username, email, or role..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                        </form>
                        <?php if ($search !== ''): ?>
                            <a href="user_management.php" class="text-xs font-semibold text-slate-500 hover:text-primary">Reset Filter</a>
                        <?php endif; ?>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">User Account</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Last Login</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">No user accounts found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): 
                                        $is_current_user = ($user['user_id'] == $_SESSION['user_id']);
                                        $role_badge = getRoleBadgeClass($user['role']);
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                                <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($user['email']); ?></div>
                                                <?php if ($is_current_user): ?>
                                                    <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-semibold bg-primary/10 text-primary rounded-md">(You)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $role_badge; ?>">
                                                    <?php echo htmlspecialchars($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="status-badge <?php echo $user['is_active'] ? 'status-badge-active' : 'status-badge-inactive'; ?>" style="padding: 2px 8px; font-size: 0.65rem; border-radius: 999px;">
                                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <div class="inline-flex gap-1">
                                                    <button type="button" onclick='openEditModal(<?php echo json_encode($user); ?>)' class="p-1 rounded hover:bg-slate-100 text-slate-600" title="Edit User">
                                                        <span class="material-symbols-outlined text-sm">edit</span>
                                                    </button>
                                                    <?php if ($user['is_active']): ?>
                                                        <?php if (!$is_current_user): ?>
                                                            <form method="post" style="display:inline;">
                                                                <input type="hidden" name="action" value="deactivate_user"/>
                                                                <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>"/>
                                                                <button type="submit" class="p-1 rounded hover:bg-amber-50 text-amber-600" title="Deactivate User">
                                                                    <span class="material-symbols-outlined text-sm">block</span>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="action" value="activate_user"/>
                                                            <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>"/>
                                                            <button type="submit" class="p-1 rounded hover:bg-emerald-50 text-emerald-600" title="Activate User">
                                                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if (!$is_current_user): ?>
                                                        <form method="post" onsubmit="return confirm('Delete this user account permanently? This action cannot be undone.');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_user"/>
                                                            <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>"/>
                                                            <button type="submit" class="p-1 rounded hover:bg-red-50 text-red-600" title="Delete User">
                                                                <span class="material-symbols-outlined text-sm">delete</span>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
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

    <div id="user-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modal-title" class="text-base font-bold text-slate-900">Create New User Account</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" id="form-action" value="add_user"/>
                <input type="hidden" name="user_id" id="form-user-id" value=""/>

                <div class="form-field">
                    <label>Username</label>
                    <input type="text" name="username" id="f-username" placeholder="jdoe" required/>
                </div>

                <div class="form-field">
                    <label>Email Address</label>
                    <input type="email" name="email" id="f-email" placeholder="john.doe@company.com" required/>
                </div>

                <div class="form-field">
                    <label>Role</label>
                    <select name="role" id="f-role" required>
                        <option value="Staff">Staff</option>
                        <option value="Supply Chain Manager">Supply Chain Manager</option>
                        <option value="Administrator">Administrator</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Password <?php echo $_POST['action'] === 'edit_user' ? '(leave blank to keep current)' : ''; ?></label>
                    <input type="password" name="password" id="f-password" placeholder="Min. 8 characters" <?php echo $_POST['action'] === 'add_user' ? 'required' : ''; ?>/>
                </div>

                <div class="form-field">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="f-is-active" value="1" checked/>
                        <span class="text-sm">Active Account</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Create New User Account';
            document.getElementById('form-action').value = 'add_user';
            document.getElementById('form-user-id').value = '';
            document.getElementById('f-username').value = '';
            document.getElementById('f-email').value = '';
            document.getElementById('f-role').value = 'Staff';
            document.getElementById('f-password').value = '';
            document.getElementById('f-password').required = true;
            document.getElementById('f-is-active').checked = true;
            document.getElementById('user-modal').style.display = 'flex';
        }

        function openEditModal(user) {
            document.getElementById('modal-title').textContent = 'Edit User Account';
            document.getElementById('form-action').value = 'edit_user';
            document.getElementById('form-user-id').value = user.user_id;
            document.getElementById('f-username').value = user.username;
            document.getElementById('f-email').value = user.email;
            document.getElementById('f-role').value = user.role;
            document.getElementById('f-password').value = '';
            document.getElementById('f-password').required = false;
            document.getElementById('f-is-active').checked = user.is_active == 1;
            document.getElementById('user-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('user-modal').style.display = 'none';
        }
    </script>
</body>
</html>