<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'dtrs_connection.php';

$section_title = "Document Tracking & Logistics Records System (DTRS)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

$service_types = ['Freight', 'Courier', 'Inter-island Ferry', 'Air Cargo', 'Trucking', 'Other'];

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $carrier_name = trim($_POST['carrier_name'] ?? '');
        $contact_person = $_POST['contact_person'] !== '' ? trim($_POST['contact_person']) : null;
        $phone = $_POST['phone'] !== '' ? trim($_POST['phone']) : null;
        $email = $_POST['email'] !== '' ? trim($_POST['email']) : null;
        $service_type = $_POST['service_type'] ?? 'Other';

        if ($carrier_name === '') {
            $db_error = "Carrier name is required.";
        } elseif ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO carriers (carrier_name, contact_person, phone, email, service_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $carrier_name, $contact_person, $phone, $email, $service_type);
            if ($stmt->execute()) {
                $flash = "Carrier \"$carrier_name\" added to directory.";
                $safe_log = $conn->real_escape_string("Added carrier: $carrier_name");
                $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$safe_log', 'Added', 'status-pill-info')");
            } else {
                $db_error = "Failed to add carrier: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $carrier_id = (int)$_POST['carrier_id'];
            $stmt = $conn->prepare("UPDATE carriers SET carrier_name = ?, contact_person = ?, phone = ?, email = ?, service_type = ? WHERE carrier_id = ?");
            $stmt->bind_param("sssssi", $carrier_name, $contact_person, $phone, $email, $service_type, $carrier_id);
            if ($stmt->execute()) {
                $flash = "Carrier \"$carrier_name\" updated.";
            } else {
                $db_error = "Failed to update carrier: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'toggle_active') {
        $carrier_id = (int)$_POST['carrier_id'];
        $stmt = $conn->prepare("UPDATE carriers SET is_active = NOT is_active WHERE carrier_id = ?");
        $stmt->bind_param("i", $carrier_id);
        if ($stmt->execute()) {
            $flash = "Carrier status updated.";
        } else {
            $db_error = "Failed to update carrier status: " . $stmt->error;
        }
        $stmt->close();
    }
}

$carriers = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$conn->connect_error) {
    $sql = "SELECT c.*,
                   COUNT(m.manifest_id) AS shipment_count
            FROM carriers c
            LEFT JOIN logistics_manifests m ON m.carrier_id = c.carrier_id";
    if ($search !== '') {
        $sql .= " WHERE c.carrier_name LIKE ? OR c.contact_person LIKE ? OR c.service_type LIKE ?";
    }
    $sql .= " GROUP BY c.carrier_id ORDER BY c.carrier_name ASC";

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
            $carriers[] = $row;
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
    <title><?php echo $section_title; ?> — Carrier Directory</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        .status-badge-active   { background: #dcfce7; color: #166534; }
        .status-badge-inactive { background: #f1f5f9; color: #64748b; }

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
                        <h1 class="text-2xl font-bold text-slate-900">Carrier / 3PL Directory</h1>
                        <p class="text-slate-500 text-sm mt-1">Logistics partners and couriers used to fulfill shipments — separate from the supplier/vendor directory.</p>
                    </div>
                    <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add</span> Add Carrier
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
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search carrier, contact, or service type..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Carrier</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Service Type</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Shipments</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($carriers)): ?>
                                    <tr><td colspan="6" class="px-6 py-10 text-center text-slate-400">No carriers in the directory yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($carriers as $c): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-semibold text-slate-900"><?php echo htmlspecialchars($c['carrier_name']); ?></td>
                                            <td class="px-6 py-4 text-slate-600">
                                                <?php echo htmlspecialchars($c['contact_person'] ?: '—'); ?>
                                                <?php if ($c['phone'] || $c['email']): ?>
                                                    <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($c['phone'] ?: $c['email']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($c['service_type'] ?: '—'); ?></td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo (int)$c['shipment_count']; ?></td>
                                            <td class="px-6 py-4">
                                                <span class="status-badge <?php echo $c['is_active'] ? 'status-badge-active' : 'status-badge-inactive'; ?>" style="padding: 2px 8px; font-size: 0.65rem; border-radius: 999px;">
                                                    <?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <div class="inline-flex gap-1">
                                                    <button type="button" onclick='openEditModal(<?php echo json_encode($c); ?>)' class="p-1 rounded hover:bg-slate-100 text-slate-600" title="Edit Carrier">
                                                        <span class="material-symbols-outlined text-sm">edit</span>
                                                    </button>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="toggle_active"/>
                                                        <input type="hidden" name="carrier_id" value="<?php echo (int)$c['carrier_id']; ?>"/>
                                                        <button type="submit" class="p-1 rounded hover:bg-amber-50 text-amber-600" title="<?php echo $c['is_active'] ? 'Deactivate' : 'Reactivate'; ?>">
                                                            <span class="material-symbols-outlined text-sm"><?php echo $c['is_active'] ? 'toggle_on' : 'toggle_off'; ?></span>
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

    <div id="carrier-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900" id="modal-title">Add Carrier</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" id="modal-action" value="add"/>
                <input type="hidden" name="carrier_id" id="modal-carrier-id"/>

                <div class="form-field">
                    <label>Carrier / 3PL Name</label>
                    <input type="text" name="carrier_name" id="modal-carrier-name" placeholder="Metro Manila Express" required/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" id="modal-contact-person" placeholder="Juan Dela Cruz"/>
                    </div>
                    <div class="form-field">
                        <label>Service Type</label>
                        <select name="service_type" id="modal-service-type">
                            <?php foreach ($service_types as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Phone</label>
                        <input type="text" name="phone" id="modal-phone" placeholder="+63 900 000 0000"/>
                    </div>
                    <div class="form-field">
                        <label>Email</label>
                        <input type="email" name="email" id="modal-email" placeholder="dispatch@carrier.com"/>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold" id="modal-submit">Add Carrier</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add Carrier';
            document.getElementById('modal-action').value = 'add';
            document.getElementById('modal-carrier-id').value = '';
            document.getElementById('modal-carrier-name').value = '';
            document.getElementById('modal-contact-person').value = '';
            document.getElementById('modal-service-type').value = 'Freight';
            document.getElementById('modal-phone').value = '';
            document.getElementById('modal-email').value = '';
            document.getElementById('modal-submit').textContent = 'Add Carrier';
            document.getElementById('carrier-modal').style.display = 'flex';
        }
        function openEditModal(carrier) {
            document.getElementById('modal-title').textContent = 'Edit Carrier';
            document.getElementById('modal-action').value = 'edit';
            document.getElementById('modal-carrier-id').value = carrier.carrier_id;
            document.getElementById('modal-carrier-name').value = carrier.carrier_name || '';
            document.getElementById('modal-contact-person').value = carrier.contact_person || '';
            document.getElementById('modal-service-type').value = carrier.service_type || 'Other';
            document.getElementById('modal-phone').value = carrier.phone || '';
            document.getElementById('modal-email').value = carrier.email || '';
            document.getElementById('modal-submit').textContent = 'Save Changes';
            document.getElementById('carrier-modal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('carrier-modal').style.display = 'none';
        }
    </script>
</body>
</html>