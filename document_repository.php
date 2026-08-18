<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db_connection.php';

$section_title = "Document Tracking & Logistics Records System (DTRS)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

$doc_types = ['Bill of Lading', 'Packing Slip', 'Customs Form', 'Delivery Receipt', 'Invoice', 'Other'];

// shipment_documents may not exist yet if schema_dtrs_fix.sql hasn't been run
// against this database. Check first so the page degrades to a stub instead
// of fataling on a missing table.
$documentsTableExists = false;
if (!$conn->connect_error) {
    $tblCheck = $conn->query("SHOW TABLES LIKE 'shipment_documents'");
    if ($tblCheck && $tblCheck->num_rows === 1) {
        $documentsTableExists = true;
    }
    if ($tblCheck) $tblCheck->free();
}

if ($documentsTableExists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload') {
        $manifest_id = (int)$_POST['manifest_id'];
        $doc_type = $_POST['doc_type'] ?? 'Other';
        $file_name = trim($_POST['file_name'] ?? '');
        $file_url = trim($_POST['file_url'] ?? '');
        $uploaded_by = $admin_user;

        if ($manifest_id <= 0 || $file_name === '' || $file_url === '') {
            $db_error = "Manifest, file name, and file location are all required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO shipment_documents (manifest_id, doc_type, file_name, file_url, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $manifest_id, $doc_type, $file_name, $file_url, $uploaded_by);

            if ($stmt->execute()) {
                $flash = "Document \"$file_name\" filed under manifest #$manifest_id.";
                $log_msg = "Filed $doc_type ($file_name) for Manifest #$manifest_id";
                $safe_log = $conn->real_escape_string($log_msg);
                $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$safe_log', 'Filed', 'status-pill-info')");
            } else {
                $db_error = "Failed to file document: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'delete') {
        $doc_id = (int)$_POST['doc_id'];
        $stmt = $conn->prepare("DELETE FROM shipment_documents WHERE doc_id = ?");
        $stmt->bind_param("i", $doc_id);
        if ($stmt->execute()) {
            $flash = "Document removed from repository.";
        } else {
            $db_error = "Failed to remove document: " . $stmt->error;
        }
        $stmt->close();
    }
}

$documents = [];
$manifests_list = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$conn->connect_error && $documentsTableExists) {
    $sql = "SELECT d.*, m.manifest_number, o.order_number
            FROM shipment_documents d
            LEFT JOIN logistics_manifests m ON d.manifest_id = m.manifest_id
            LEFT JOIN orders o ON m.order_id = o.order_id";
    if ($search !== '') {
        $sql .= " WHERE d.file_name LIKE ? OR d.doc_type LIKE ? OR m.manifest_number LIKE ?";
    }
    $sql .= " ORDER BY d.uploaded_at DESC";

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
            $documents[] = $row;
        }
    }

    $m_res = $conn->query("SELECT manifest_id, manifest_number FROM logistics_manifests ORDER BY created_at DESC");
    if ($m_res) {
        while ($row = $m_res->fetch_assoc()) {
            $manifests_list[] = $row;
        }
    }
} else {
    $db_error = "Database offline.";
}

function docTypeIcon($type) {
    switch ($type) {
        case 'Bill of Lading': return 'receipt_long';
        case 'Packing Slip': return 'inventory_2';
        case 'Customs Form': return 'gavel';
        case 'Delivery Receipt': return 'assignment_turned_in';
        case 'Invoice': return 'request_quote';
        default: return 'description';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Document Repository</title>
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
        .form-field { margin-bottom: 1rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select {
            width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem;
        }
        .doc-type-chip {
            background:#f1f5f9; color:#475569; border-radius:999px; padding:2px 10px;
            font-size:0.65rem; font-weight:600; display:inline-flex; align-items:center; gap:4px;
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
                        <h1 class="text-2xl font-bold text-slate-900">Document Repository</h1>
                        <p class="text-slate-500 text-sm mt-1">Bills of lading, packing slips, and other shipment paperwork filed per manifest.</p>
                    </div>
                    <?php if ($documentsTableExists): ?>
                        <button type="button" onclick="openUploadModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">upload_file</span> File Document
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <?php if (!$documentsTableExists): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-10 text-center">
                        <span class="material-symbols-outlined text-slate-300 text-4xl">folder_open</span>
                        <h2 class="text-sm font-bold text-slate-900 mt-3">Not wired up yet</h2>
                        <p class="text-slate-500 text-xs mt-1 max-w-md mx-auto">
                            The <code>shipment_documents</code> table hasn't been created in this database yet.
                            Run <code>schema_dtrs_fix.sql</code> to enable this page — once that's applied,
                            this view switches to the live version automatically, no code changes needed.
                        </p>
                    </div>
                <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <form method="get" class="relative w-full sm:max-w-sm">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search file name, type, or manifest..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Document</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Manifest</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Purchase Link</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Uploaded By</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Uploaded</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($documents)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No documents filed yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $d): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-semibold text-slate-900">
                                                <a href="<?php echo htmlspecialchars($d['file_url']); ?>" target="_blank" class="hover:text-primary inline-flex items-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[16px] text-slate-400"><?php echo docTypeIcon($d['doc_type']); ?></span>
                                                    <?php echo htmlspecialchars($d['file_name']); ?>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4"><span class="doc-type-chip"><?php echo htmlspecialchars($d['doc_type']); ?></span></td>
                                            <td class="px-6 py-4 font-mono text-slate-600"><?php echo htmlspecialchars($d['manifest_number'] ?? '—'); ?></td>
                                            <td class="px-6 py-4 font-semibold text-primary"><?php echo htmlspecialchars($d['order_number'] ?? '—'); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo htmlspecialchars($d['uploaded_by'] ?? '—'); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo date('M j, Y g:i A', strtotime($d['uploaded_at'])); ?></td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <div class="inline-flex gap-1">
                                                    <?php if (strtolower(pathinfo($d['file_url'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                                        <button type="button" onclick="openPreviewModal('<?php echo htmlspecialchars($d['file_url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($d['file_name'], ENT_QUOTES); ?>')" class="p-1 rounded hover:bg-blue-50 text-blue-600" title="Preview PDF">
                                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                                        </button>
                                                    <?php endif; ?>
                                                    <form method="post" style="display:inline;" onsubmit="return confirm('Remove this document from the repository?');">
                                                        <input type="hidden" name="action" value="delete"/>
                                                        <input type="hidden" name="doc_id" value="<?php echo (int)$d['doc_id']; ?>"/>
                                                        <button type="submit" class="p-1 rounded hover:bg-red-50 text-red-600" title="Remove Document">
                                                            <span class="material-symbols-outlined text-sm">delete</span>
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
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ($documentsTableExists): ?>
    <div id="upload-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">File Shipment Document</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="upload"/>

                <div class="form-field">
                    <label>Linked Manifest</label>
                    <select name="manifest_id" required>
                        <option value="">Select manifest...</option>
                        <?php foreach ($manifests_list as $m): ?>
                            <option value="<?php echo $m['manifest_id']; ?>"><?php echo htmlspecialchars($m['manifest_number']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Document Type</label>
                    <select name="doc_type" required>
                        <?php foreach ($doc_types as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>File Name</label>
                    <input type="text" name="file_name" placeholder="BOL-MNF-2026-001.pdf" required/>
                </div>

                <div class="form-field">
                    <label>File Location (URL / path)</label>
                    <input type="text" name="file_url" placeholder="/uploads/documents/BOL-MNF-2026-001.pdf" required/>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">File Document</button>
                </div>
            </form>
        </div>
    </div>

    <div id="preview-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box" style="max-width: 48rem;">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-slate-900" id="preview-title">Document Preview</h3>
                <button type="button" onclick="closePreviewModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <iframe id="preview-frame" src="" style="width:100%; height:70vh; border:1px solid #e2e8f0; border-radius:0.5rem;"></iframe>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openUploadModal() {
            document.getElementById('upload-modal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('upload-modal').style.display = 'none';
        }
        function openPreviewModal(url, title) {
            document.getElementById('preview-frame').src = url;
            document.getElementById('preview-title').textContent = title;
            document.getElementById('preview-modal').style.display = 'flex';
        }
        function closePreviewModal() {
            document.getElementById('preview-modal').style.display = 'none';
            document.getElementById('preview-frame').src = '';
        }
    </script>
</body>
</html>