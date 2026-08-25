<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Document Tracking & Logistics Records System (DTRS)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// U-001: Document Archive toggle. Detect the column at runtime so this page
// keeps working even before schema_dtrs_archive.sql has been run — the
// archive UI just stays hidden until then.
$hasArchiveColumn = false;
if (!$conn->connect_error) {
    $colCheck = $conn->query("SHOW COLUMNS FROM logistics_manifests LIKE 'is_archived'");
    if ($colCheck && $colCheck->num_rows === 1) {
        $hasArchiveColumn = true;
    }
    if ($colCheck) $colCheck->free();
}

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'archive' || $action === 'unarchive') {
        if (!$hasArchiveColumn) {
            $db_error = "Archiving isn't enabled yet — run schema_dtrs_archive.sql to add the is_archived column.";
        } else {
            $mnf_id = (int)$_POST['manifest_id'];
            $archivedValue = $action === 'archive' ? 1 : 0;
            $stmt = $conn->prepare("UPDATE logistics_manifests SET is_archived = ? WHERE manifest_id = ?");
            $stmt->bind_param("ii", $archivedValue, $mnf_id);
            if ($stmt->execute()) {
                $flash = $action === 'archive' ? "Manifest #$mnf_id archived." : "Manifest #$mnf_id restored from archive.";
            } else {
                $db_error = "Failed to update archive status: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO logistics_manifests (manifest_number, order_id, carrier_name, tracking_number, dispatch_date, estimated_delivery, delivery_status, document_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $order_id = $_POST['order_id'] !== '' ? (int)$_POST['order_id'] : null;
        $dispatch = $_POST['dispatch_date'] !== '' ? $_POST['dispatch_date'] : null;
        $delivery = $_POST['estimated_delivery'] !== '' ? $_POST['estimated_delivery'] : null;
        $doc_url = $_POST['document_url'] !== '' ? $_POST['document_url'] : 'manifest_copy.pdf';

        $stmt->bind_param("sissssss", 
            $_POST['manifest_number'], 
            $order_id, 
            $_POST['carrier_name'], 
            $_POST['tracking_number'], 
            $dispatch, 
            $delivery, 
            $_POST['delivery_status'],
            $doc_url
        );

        if ($stmt->execute()) {
            $flash = "Dispatch manifest logged & tracking sequence initialized.";
            $log_msg = "Logged Dispatch Manifest: " . $_POST['manifest_number'];
            $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'In Transit', 'status-pill-info')");
        } else {
            $db_error = "Failed to log shipment: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'update_status') {
        $mnf_id = (int)$_POST['manifest_id'];
        $status = $_POST['delivery_status'];

        $stmt = $conn->prepare("UPDATE logistics_manifests SET delivery_status = ? WHERE manifest_id = ?");
        $stmt->bind_param("si", $status, $mnf_id);
        
        if ($stmt->execute()) {
            $flash = "Logistics shipping status updated to $status.";
        } else {
            $db_error = "Failed to transition status: " . $stmt->error;
        }
        $stmt->close();
    }
}

$manifests = [];
$orders_list = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$showArchived = $hasArchiveColumn && isset($_GET['show_archived']) && $_GET['show_archived'] === '1';

if (!$conn->connect_error) {
    $where = [];
    $params = [];
    $types = '';

    if ($search !== '') {
        $where[] = "(m.manifest_number LIKE ? OR m.tracking_number LIKE ? OR m.carrier_name LIKE ?)";
        $like = "%$search%";
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types .= 'sss';
    }
    if ($hasArchiveColumn && !$showArchived) {
        $where[] = "m.is_archived = 0";
    }

    $sql = "SELECT m.*, o.order_number FROM logistics_manifests m LEFT JOIN orders o ON m.order_id = o.order_id";
    if ($where) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY m.created_at DESC";

    if ($params) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $manifests[] = $row;
        }
    }

    $order_res = $conn->query("SELECT order_id, order_number FROM orders ORDER BY order_date DESC");
    if ($order_res) {
        while ($row = $order_res->fetch_assoc()) {
            $orders_list[] = $row;
        }
    }
} else {
    $db_error = "Database offline.";
}

function getLogisticsBadge($status) {
    switch ($status) {
        case 'Delivered': return 'status-badge-active';
        case 'Dispatched': return 'status-badge-archived';
        case 'In Transit': return 'status-badge-transit';
        case 'Out for Delivery': return 'status-badge-maintenance';
        case 'Delayed': return 'status-badge-critical';
        default: return 'status-badge-archived';
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
        .status-badge-active      { background: #dcfce7; color: #166534; }
        .status-badge-maintenance { background: #fef3c7; color: #92400e; }
        .status-badge-critical    { background: #fee2e2; color: #991b1b; }
        .status-badge-archived    { background: #e2e8f0; color: #475569; }
        .status-badge-transit     { background: #dbeafe; color: #1e40af; }
        
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
                        <h1 class="text-2xl font-bold text-slate-900">Shipping Manifests & Tracking</h1>
                        <p class="text-slate-500 text-sm mt-1">Audit carrier metrics, trace active bill of lading numbers, and manage dispatch manifests.</p>
                    </div>
                    <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add_road</span> Register Manifest
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
                        <form method="get" class="w-full sm:max-w-lg flex items-center gap-3">
                            <div class="relative flex-1">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search manifest code, tracking ID, or carrier..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                            </div>
                            <?php if ($hasArchiveColumn): ?>
                                <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 whitespace-nowrap cursor-pointer select-none">
                                    <input type="checkbox" name="show_archived" value="1" <?php echo $showArchived ? 'checked' : ''; ?> onchange="this.form.submit()"/>
                                    Show Archived
                                </label>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Manifest Number</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Purchase Link</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Carrier Service</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Tracking Reference</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Dispatch Date</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">ETA</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Shipping Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($manifests)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-slate-400">No dispatch records found in shipping queue.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($manifests as $m): 
                                        $badge = getLogisticsBadge($m['delivery_status']);
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-mono font-bold text-slate-900">
                                                <?php echo htmlspecialchars($m['manifest_number']); ?>
                                                <?php if ($hasArchiveColumn && !empty($m['is_archived'])): ?>
                                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">Archived</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 font-semibold text-primary"><?php echo htmlspecialchars($m['order_number'] ?: 'Direct Fleet'); ?></td>
                                            <td class="px-6 py-4 font-medium text-slate-900"><?php echo htmlspecialchars($m['carrier_name']); ?></td>
                                            <td class="px-6 py-4 font-mono text-slate-600 font-medium"><?php echo htmlspecialchars($m['tracking_number']); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo $m['dispatch_date'] ? date('M j, Y', strtotime($m['dispatch_date'])) : '—'; ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo $m['estimated_delivery'] ? date('M j, Y', strtotime($m['estimated_delivery'])) : '—'; ?></td>
                                            <td class="px-6 py-4">
                                                <span class="status-badge <?php echo $badge; ?>" style="padding: 2px 8px; font-size: 0.65rem; border-radius: 999px;">
                                                    <?php echo htmlspecialchars($m['delivery_status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <div class="inline-flex gap-1 items-center justify-end">
                                                    <?php if ($m['delivery_status'] !== 'Delivered'): ?>
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="action" value="update_status"/>
                                                            <input type="hidden" name="manifest_id" value="<?php echo (int)$m['manifest_id']; ?>"/>
                                                            <input type="hidden" name="delivery_status" value="Delivered"/>
                                                            <button type="submit" class="p-1 rounded hover:bg-emerald-50 text-emerald-600" title="Confirm Delivery">
                                                                <span class="material-symbols-outlined text-sm">local_shipping</span>
                                                            </button>
                                                        </form>
                                                        <form method="post" style="display:inline;">
                                                            <input type="hidden" name="action" value="update_status"/>
                                                            <input type="hidden" name="manifest_id" value="<?php echo (int)$m['manifest_id']; ?>"/>
                                                            <input type="hidden" name="delivery_status" value="Delayed"/>
                                                            <button type="submit" class="p-1 rounded hover:bg-red-50 text-red-600" title="Mark Delayed">
                                                                <span class="material-symbols-outlined text-sm">warning</span>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-[10px] text-slate-400 font-semibold mr-1">Completed</span>
                                                    <?php endif; ?>

                                                    <?php if ($hasArchiveColumn): ?>
                                                        <?php if (!empty($m['is_archived'])): ?>
                                                            <form method="post" style="display:inline;">
                                                                <input type="hidden" name="action" value="unarchive"/>
                                                                <input type="hidden" name="manifest_id" value="<?php echo (int)$m['manifest_id']; ?>"/>
                                                                <button type="submit" class="p-1 rounded hover:bg-blue-50 text-blue-600" title="Restore from Archive">
                                                                    <span class="material-symbols-outlined text-sm">unarchive</span>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="post" style="display:inline;">
                                                                <input type="hidden" name="action" value="archive"/>
                                                                <input type="hidden" name="manifest_id" value="<?php echo (int)$m['manifest_id']; ?>"/>
                                                                <button type="submit" class="p-1 rounded hover:bg-slate-100 text-slate-500" title="Archive Manifest">
                                                                    <span class="material-symbols-outlined text-sm">archive</span>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
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

    <div id="manifest-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Log Logistics Shipping Manifest</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="add"/>

                <div class="form-field">
                    <label>Manifest Number</label>
                    <input type="text" name="manifest_number" placeholder="MNF-2026-001" required/>
                </div>

                <div class="form-field">
                    <label>Reference Purchase Order Link</label>
                    <select name="order_id" required>
                        <option value="">Select PO tracking origin...</option>
                        <?php foreach ($orders_list as $o): ?>
                            <option value="<?php echo $o['order_id']; ?>"><?php echo htmlspecialchars($o['order_number']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Carrier / Third-Party Logistics Partner</label>
                        <input type="text" name="carrier_name" placeholder="Metro Manila Express" required/>
                    </div>
                    <div class="form-field">
                        <label>Waybill / Tracking Number</label>
                        <input type="text" name="tracking_number" placeholder="TRK-9900-2026" required/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Dispatch Date</label>
                        <input type="date" name="dispatch_date" required/>
                    </div>
                    <div class="form-field">
                        <label>Estimated Arrival (ETA)</label>
                        <input type="date" name="estimated_delivery"/>
                    </div>
                </div>

                <div class="form-field">
                    <label>Logistics Stage</label>
                    <select name="delivery_status">
                        <option value="Dispatched">Dispatched</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Out for Delivery">Out for Delivery</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Delayed">Delayed (Customs/Ferry Hold)</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Dispatch Cargo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('manifest-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('manifest-modal').style.display = 'none';
        }
    </script>
</body>
</html>