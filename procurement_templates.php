<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Procurement Templates";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Check if procurement_templates table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'procurement_templates'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}
$check_table->free();

if ($table_exists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_template') {
        $required_approvals = json_encode([
            'stages' => ['Recruiter Lead', 'IT Director'],
            'amount_threshold' => 50000
        ]);
        
        $stmt = $conn->prepare("INSERT INTO procurement_templates (template_name, description, category, default_budget, required_approvals, is_recurring, recurring_frequency, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $default_budget = !empty($_POST['default_budget']) ? (float)$_POST['default_budget'] : null;
        $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
        $recurring_frequency = $is_recurring ? $_POST['recurring_frequency'] : null;
        
        $stmt->bind_param("sssdisis", 
            $_POST['template_name'],
            $_POST['description'],
            $_POST['category'],
            $default_budget,
            $required_approvals,
            $is_recurring,
            $recurring_frequency,
            (int)$_SESSION['user_id']
        );

        if ($stmt->execute()) {
            $flash = "Procurement template created successfully.";
            $log_msg = "Created procurement template: " . $_POST['template_name'];
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'Template Creation', '$log_msg')");
        } else {
            $db_error = "Failed to create template: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'delete_template') {
        $template_id = (int)$_POST['template_id'];
        $stmt = $conn->prepare("DELETE FROM procurement_templates WHERE template_id = ?");
        $stmt->bind_param("i", $template_id);
        
        if ($stmt->execute()) {
            $flash = "Procurement template deleted successfully.";
        } else {
            $db_error = "Failed to delete template: " . $stmt->error;
        }
        $stmt->close();
    }
}

$templates = [];

if ($table_exists && !$conn->connect_error) {
    $sql = "SELECT pt.*, u.username as created_by_name 
            FROM procurement_templates pt 
            LEFT JOIN users u ON pt.created_by = u.user_id 
            ORDER BY pt.created_at DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $templates[] = $row;
        }
    }
} else {
    if (!$table_exists) {
        $db_error = "Procurement templates table not found. Please run the schema_updates.sql file to create it.";
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
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 100; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 1rem; width: 100%; max-width: 36rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            max-height: 90vh; overflow-y: auto;
        }
        .form-field { margin-bottom: 1rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select, .form-field textarea {
            width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Procurement & Sourcing Management</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Procurement Templates</h1>
                        <p class="text-slate-500 text-sm mt-1">Reusable templates for recurring purchases and standardized requisition workflows.</p>
                    </div>
                    <?php if ($table_exists): ?>
                        <button type="button" onclick="openCreateModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">note_add</span> Create Template
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <?php if ($table_exists): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Template Name</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Category</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Default Budget</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Recurring</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Created By</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($templates)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">No procurement templates found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($templates as $template): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($template['template_name']); ?></div>
                                                    <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($template['description'] ?: 'No description'); ?></div>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($template['category'] ?: '—'); ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo $template['default_budget'] ? '₱' . number_format($template['default_budget'], 2) : '—'; ?></td>
                                                <td class="px-4 py-3">
                                                    <?php if ($template['is_recurring']): ?>
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border bg-blue-100 text-blue-800 border-blue-200">
                                                            <?php echo htmlspecialchars($template['recurring_frequency']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-slate-400">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo htmlspecialchars($template['created_by_name']); ?></td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                    <form method="post" onsubmit="return confirm('Delete this template?');" style="display:inline;">
                                                        <input type="hidden" name="action" value="delete_template"/>
                                                        <input type="hidden" name="template_id" value="<?php echo (int)$template['template_id']; ?>"/>
                                                        <button type="submit" class="p-1 rounded hover:bg-red-50 text-red-600" title="Delete Template">
                                                            <span class="material-symbols-outlined text-sm">delete</span>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3 rounded-lg">
                        <p><strong>Procurement templates are not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Create Template Modal -->
    <div id="create-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Create Procurement Template</h3>
                <button type="button" onclick="closeCreateModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="create_template"/>

                <div class="form-field">
                    <label>Template Name</label>
                    <input type="text" name="template_name" placeholder="Annual Software Licenses" required/>
                </div>

                <div class="form-field">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="Purpose and scope of this template..."></textarea>
                </div>

                <div class="form-field">
                    <label>Category</label>
                    <select name="category">
                        <option value="Software">Software Licenses</option>
                        <option value="Hardware">Hardware Equipment</option>
                        <option value="Office Supplies">Office Supplies</option>
                        <option value="Services">Professional Services</option>
                        <option value="Maintenance">Maintenance Contracts</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Default Budget (₱)</label>
                    <input type="number" step="0.01" name="default_budget" placeholder="100000.00"/>
                </div>

                <div class="form-field">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_recurring" value="1" id="is-recurring"/>
                        <span class="text-sm">Recurring Purchase</span>
                    </label>
                </div>

                <div class="form-field" id="recurring-options" style="display:none;">
                    <label>Frequency</label>
                    <select name="recurring_frequency">
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Annually">Annually</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Create Template</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('is-recurring').addEventListener('change', function() {
            document.getElementById('recurring-options').style.display = this.checked ? 'block' : 'none';
        });

        function openCreateModal() {
            document.getElementById('create-modal').style.display = 'flex';
        }

        function closeCreateModal() {
            document.getElementById('create-modal').style.display = 'none';
        }
    </script>
</body>
</html>