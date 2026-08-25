<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Supplier / Vendor Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, vendor_type, contact_person, email, phone, address, rating, status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $rating = (float)$_POST['rating'];
        $status = $_POST['status'] ?? 'Active';
        $is_active = ($status === 'Active') ? 1 : 0;

        $stmt->bind_param("ssssssdsi", 
            $_POST['supplier_name'], 
            $_POST['vendor_type'],
            $_POST['contact_person'], 
            $_POST['email'], 
            $_POST['phone'], 
            $_POST['address'], 
            $rating, 
            $status,
            $is_active
        );

        if ($stmt->execute()) {
            $flash = "Supplier profile registered successfully.";
            $log_msg = "Registered vendor profile: " . $_POST['supplier_name'];
            $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'New', 'status-pill-accent')");
        } else {
            $db_error = "Failed to register supplier: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'edit') {
        $supplier_id = (int)$_POST['supplier_id'];
        $stmt = $conn->prepare("UPDATE suppliers SET supplier_name = ?, vendor_type = ?, contact_person = ?, email = ?, phone = ?, address = ?, rating = ?, status = ?, is_active = ? WHERE supplier_id = ?");
        $rating = (float)$_POST['rating'];
        $status = $_POST['status'] ?? 'Active';
        $is_active = ($status === 'Active') ? 1 : 0;

        $stmt->bind_param("ssssssdsii", 
            $_POST['supplier_name'], 
            $_POST['vendor_type'],
            $_POST['contact_person'], 
            $_POST['email'], 
            $_POST['phone'], 
            $_POST['address'], 
            $rating, 
            $status,
            $is_active,
            $supplier_id
        );

        if ($stmt->execute()) {
            $flash = "Supplier profile modifications updated.";
        } else {
            $db_error = "Failed to update supplier: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'toggle') {
        // Quick action from the table row: Active <-> Suspended only.
        // Pending Approval and Blacklisted are set deliberately via the edit form, not this shortcut.
        $supplier_id = (int)$_POST['supplier_id'];
        $current_status = $_POST['current_status'] ?? 'Active';
        $new_status = ($current_status === 'Active') ? 'Suspended' : 'Active';
        $new_active = ($new_status === 'Active') ? 1 : 0;

        $stmt = $conn->prepare("UPDATE suppliers SET status = ?, is_active = ? WHERE supplier_id = ?");
        $stmt->bind_param("sii", $new_status, $new_active, $supplier_id);
        if ($stmt->execute()) {
            $flash = "Supplier operational visibility updated.";
        } else {
            $db_error = "Failed to modify activation state: " . $stmt->error;
        }
        $stmt->close();
    }
}

$suppliers = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$conn->connect_error) {
    $sql = "SELECT * FROM suppliers";
    if ($search !== '') {
        $sql .= " WHERE supplier_name LIKE ? OR contact_person LIKE ? OR email LIKE ?";
    }
    $sql .= " ORDER BY supplier_name ASC";

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
            $suppliers[] = $row;
        }
    }
} else {
    $db_error = "Database connection offline.";
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
        .status-badge-active      { background: #dcfce7; color: #166534; }
        .status-badge-critical    { background: #fee2e2; color: #991b1b; }
        
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            display: flex; align-items: center; justify-content: center;
            z-index: 100; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 1rem; width: 100%; max-width: 32rem;
            padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
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
        <?php include 'header.php'; ?>
<main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Supplier & Vendor Directory</h1>
                        <p class="text-slate-500 text-sm mt-1">Onboard raw material providers, manage communications, and evaluate contract scorecards.</p>
                    </div>
                    <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">person_add</span> Onboard New Vendor
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
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search vendor name, contact person, or emails..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Vendor Business Entity</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Point of Contact</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Communication Details</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Physical Hub Address</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Performance Scorecard</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Operational Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($suppliers)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-slate-400">No active supplier records matched.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($suppliers as $sup): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-bold text-slate-900">
                                                <?php echo htmlspecialchars($sup['supplier_name']); ?>
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200"><?php echo htmlspecialchars($sup['vendor_type'] ?: 'Uncategorized'); ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-700 font-medium"><?php echo htmlspecialchars($sup['contact_person'] ?: '—'); ?></td>
                                            <td class="px-6 py-4 space-y-0.5">
                                                <div class="text-slate-900 font-medium"><?php echo htmlspecialchars($sup['email'] ?: '—'); ?></div>
                                                <div class="text-slate-400 font-mono text-[10px]"><?php echo htmlspecialchars($sup['phone'] ?: '—'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-500 max-w-xs truncate"><?php echo htmlspecialchars($sup['address'] ?: '—'); ?></td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-1 text-amber-600 font-bold">
                                                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1">star</span>
                                                    <span><?php echo number_format((float)$sup['rating'], 1); ?> / 5.0</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                    $statusPillClass = [
                                                        'Active' => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                                                        'Pending Approval' => 'text-amber-700 bg-amber-50 border-amber-200',
                                                        'Suspended' => 'text-rose-700 bg-rose-50 border-rose-200',
                                                        'Blacklisted' => 'text-slate-700 bg-slate-200 border-slate-300',
                                                    ][$sup['status']] ?? 'text-slate-700 bg-slate-100 border-slate-200';
                                                ?>
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $statusPillClass; ?>"><?php echo htmlspecialchars($sup['status']); ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <div class="inline-flex gap-1">
                                                    <button type="button" onclick='openEditModal(<?php echo json_encode($sup); ?>)' class="p-1 rounded hover:bg-slate-100 text-slate-600" title="Modify Details">
                                                        <span class="material-symbols-outlined text-sm">edit</span>
                                                    </button>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle"/>
                                                        <input type="hidden" name="supplier_id" value="<?php echo (int)$sup['supplier_id']; ?>"/>
                                                        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($sup['status']); ?>"/>
                                                        <button type="submit" class="p-1 rounded hover:bg-slate-100 <?php echo $sup['status'] === 'Active' ? 'text-red-600' : 'text-emerald-600'; ?>" title="<?php echo $sup['status'] === 'Active' ? 'Suspend' : 'Reactivate'; ?>">
                                                            <span class="material-symbols-outlined text-sm"><?php echo $sup['status'] === 'Active' ? 'block' : 'check_circle'; ?></span>
                                                        </button>
                                                    </form>
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

    <div id="supplier-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modal-title" class="text-base font-bold text-slate-900">Register Vendor Profile</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" id="form-action" value="add"/>
                <input type="hidden" name="supplier_id" id="form-sup-id" value=""/>

                <div class="form-field">
                    <label>Corporate Legal Entity Name</label>
                    <input type="text" name="supplier_name" id="f-name" placeholder="Batangas Industrial Hub Co." required/>
                </div>

                <div class="form-field">
                    <label>Primary Contact Manager</label>
                    <input type="text" name="contact_person" id="f-contact" placeholder="Maria Santos" required/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Business Email Address</label>
                        <input type="email" name="email" id="f-email" placeholder="logistics@batangashub.ph" required/>
                    </div>
                    <div class="form-field">
                        <label>Telephone/Mobile Network</label>
                        <input type="text" name="phone" id="f-phone" placeholder="0917-123-4567" required/>
                    </div>
                </div>

                <div class="form-field">
                    <label>Corporate Logistics Office Address</label>
                    <input type="text" name="address" id="f-address" placeholder="Batangas City, Philippines"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Vendor Type</label>
                        <select name="vendor_type" id="f-vendor-type">
                            <option value="Product Supplier">Product Supplier</option>
                            <option value="Logistics Carrier">Logistics Carrier</option>
                            <option value="Service Provider">Service Provider</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Initial Performance Scorecard (0.0 - 5.0)</label>
                        <input type="number" step="0.1" min="0" max="5" name="rating" id="f-rating" placeholder="4.5" required/>
                    </div>
                </div>

                <div class="form-field">
                    <label>Vendor Status</label>
                    <select name="status" id="f-status">
                        <option value="Pending Approval">Pending Approval</option>
                        <option value="Active">Active</option>
                        <option value="Suspended">Suspended</option>
                        <option value="Blacklisted">Blacklisted</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Commit Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Onboard New Vendor Entity';
            document.getElementById('form-action').value = 'add';
            document.getElementById('form-sup-id').value = '';
            document.getElementById('f-name').value = '';
            document.getElementById('f-vendor-type').value = 'Product Supplier';
            document.getElementById('f-contact').value = '';
            document.getElementById('f-email').value = '';
            document.getElementById('f-phone').value = '';
            document.getElementById('f-address').value = '';
            document.getElementById('f-rating').value = '5.0';
            document.getElementById('f-status').value = 'Pending Approval';
            document.getElementById('supplier-modal').style.display = 'flex';
        }

        function openEditModal(sup) {
            document.getElementById('modal-title').textContent = 'Modify Vendor Roster Profile';
            document.getElementById('form-action').value = 'edit';
            document.getElementById('form-sup-id').value = sup.supplier_id;
            document.getElementById('f-name').value = sup.supplier_name;
            document.getElementById('f-vendor-type').value = sup.vendor_type || 'Product Supplier';
            document.getElementById('f-contact').value = sup.contact_person || '';
            document.getElementById('f-email').value = sup.email;
            document.getElementById('f-phone').value = sup.phone;
            document.getElementById('f-address').value = sup.address || '';
            document.getElementById('f-rating').value = sup.rating;
            document.getElementById('f-status').value = sup.status || 'Active';
            document.getElementById('supplier-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('supplier-modal').style.display = 'none';
        }
    </script>
</body>
</html>