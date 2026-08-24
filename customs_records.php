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

$doc_types = ['Import Permit', 'Customs Declaration', 'Certificate of Origin', 'Bureau of Customs Hold', 'Quarantine Clearance', 'Other'];

// customs_records may not exist yet if schema_dtrs_fix.sql hasn't been run
// against this database. Check first so the page degrades to a stub instead
// of fataling on a missing table.
$customsTableExists = false;
if (!$conn->connect_error) {
    $tblCheck = $conn->query("SHOW TABLES LIKE 'customs_records'");
    if ($tblCheck && $tblCheck->num_rows === 1) {
        $customsTableExists = true;
    }
    if ($tblCheck) $tblCheck->free();
}

if ($customsTableExists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $manifest_id = (int)$_POST['manifest_id'];
        $document_type = $_POST['document_type'] ?? 'Other';
        $reference_number = $_POST['reference_number'] !== '' ? trim($_POST['reference_number']) : null;
        $hold_reason = $_POST['hold_reason'] !== '' ? trim($_POST['hold_reason']) : null;
        $notes = $_POST['notes'] !== '' ? trim($_POST['notes']) : null;

        if ($manifest_id <= 0) {
            $db_error = "A linked manifest is required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO customs_records (manifest_id, document_type, reference_number, hold_reason, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $manifest_id, $document_type, $reference_number, $hold_reason, $notes);
            if ($stmt->execute()) {
                $flash = "Customs record logged for manifest #$manifest_id.";
                $safe_log = $conn->real_escape_string("Customs record ($document_type) logged for Manifest #$manifest_id");
                $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$safe_log', 'Logged', 'status-pill-info')");
            } else {
                $db_error = "Failed to log customs record: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'clear') {
        $record_id = (int)$_POST['record_id'];
        $stmt = $conn->prepare("UPDATE customs_records SET cleared_at = NOW() WHERE record_id = ?");
        $stmt->bind_param("i", $record_id);
        if ($stmt->execute()) {
            $flash = "Customs record marked cleared.";
        } else {
            $db_error = "Failed to update customs record: " . $stmt->error;
        }
        $stmt->close();
    }
}

$records = [];
$manifests_list = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$conn->connect_error && $customsTableExists) {
    $sql = "SELECT cr.*, m.manifest_number, m.delivery_status, o.order_number
            FROM customs_records cr
            LEFT JOIN logistics_manifests m ON cr.manifest_id = m.manifest_id
            LEFT JOIN orders o ON m.order_id = o.order_id";
    if ($search !== '') {
        $sql .= " WHERE cr.document_type LIKE ? OR cr.reference_number LIKE ? OR m.manifest_number LIKE ?";
    }
    $sql .= " ORDER BY cr.created_at DESC";

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
            $records[] = $row;
        }
    }

    $m_res = $conn->query("SELECT manifest_id, manifest_number, delivery_status FROM logistics_manifests ORDER BY created_at DESC");
    if ($m_res) {
        while ($row = $m_res->fetch_assoc()) {
            $manifests_list[] = $row;
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
    <title><?php echo $section_title; ?> — Customs &amp; Compliance</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        .status-badge-cleared { background: #dcfce7; color: #166534; }
        .status-badge-hold    { background: #fee2e2; color: #991b1b; }

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
                <span class="font-bold text-slate-800 text-sm">ISMERS DTRS Cluster</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Customs &amp; Compliance Records</h1>
                        <p class="text-slate-500 text-sm mt-1">Clearance documents, reference numbers, and hold reasons for shipments flagged as delayed.</p>
                    </div>
                    <?php if ($customsTableExists): ?>
                        <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">gavel</span> Log Customs Record
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <?php if (!$customsTableExists): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-10 text-center">
                        <span class="material-symbols-outlined text-slate-300 text-4xl">gavel</span>
                        <h2 class="text-sm font-bold text-slate-900 mt-3">Not wired up yet</h2>
                        <p class="text-slate-500 text-xs mt-1 max-w-md mx-auto">
                            The <code>customs_records</code> table hasn't been created in this database yet.
                            Run <code>schema_dtrs_fix.sql</code> to enable this page — once that's applied,
                            this view switches to the live version automatically, no code changes needed.
                        </p>
                    </div>
                <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <form method="get" class="relative w-full sm:max-w-sm">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search document type, reference, or manifest..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Manifest</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Document Type</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Reference No.</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Hold Reason</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Cleared</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($records)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No customs records logged yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($records as $r): $isCleared = !empty($r['cleared_at']); ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-mono font-bold text-slate-900"><?php echo htmlspecialchars($r['manifest_number'] ?? '—'); ?></td>
                                            <td class="px-6 py-4 text-slate-700"><?php echo htmlspecialchars($r['document_type']); ?></td>
                                            <td class="px-6 py-4 font-mono text-slate-600"><?php echo htmlspecialchars($r['reference_number'] ?: '—'); ?></td>
                                            <td class="px-6 py-4 text-slate-500 max-w-[200px] truncate"><?php echo htmlspecialchars($r['hold_reason'] ?: '—'); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo $isCleared ? date('M j, Y g:i A', strtotime($r['cleared_at'])) : '—'; ?></td>
                                            <td class="px-6 py-4">
                                                <span class="status-badge <?php echo $isCleared ? 'status-badge-cleared' : 'status-badge-hold'; ?>" style="padding: 2px 8px; font-size: 0.65rem; border-radius: 999px;">
                                                    <?php echo $isCleared ? 'Cleared' : 'On Hold'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <?php if (!$isCleared): ?>
                                                    <form method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="clear"/>
                                                        <input type="hidden" name="record_id" value="<?php echo (int)$r['record_id']; ?>"/>
                                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-[11px] font-bold inline-flex items-center gap-1">
                                                            <span class="material-symbols-outlined text-[14px]">verified</span> Mark Cleared
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 font-semibold">Resolved</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ($customsTableExists): ?>
    <div id="customs-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Log Customs Record</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="add"/>

                <div class="form-field">
                    <label>Linked Manifest</label>
                    <select name="manifest_id" required>
                        <option value="">Select manifest...</option>
                        <?php foreach ($manifests_list as $m): ?>
                            <option value="<?php echo $m['manifest_id']; ?>"><?php echo htmlspecialchars($m['manifest_number']); ?> <?php echo $m['delivery_status'] === 'Delayed' ? '(Delayed)' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Document Type</label>
                    <select name="document_type" required>
                        <?php foreach ($doc_types as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Reference Number</label>
                    <input type="text" name="reference_number" placeholder="BOC-2026-00123"/>
                </div>

                <div class="form-field">
                    <label>Hold Reason (if applicable)</label>
                    <input type="text" name="hold_reason" placeholder="Awaiting import permit verification"/>
                </div>

                <div class="form-field">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" placeholder="Additional context..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Log Record</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openAddModal() {
            document.getElementById('customs-modal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('customs-modal').style.display = 'none';
        }
    </script>
</body>
</html>