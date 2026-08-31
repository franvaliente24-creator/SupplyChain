<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'svm_connection.php';

$section_title = "Supplier / Vendor Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

$docTypes = [
    'DTI Business Permit', 'BIR Certificate of Registration', 'SEC Registration',
    'Supply Agreement', 'ISO 9001 Accreditation', 'Insurance Certificate', 'Other'
];

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO supplier_documents
            (supplier_id, document_type, document_name, file_path, upload_date, expiry_date, status, uploaded_by)
            VALUES (?, ?, ?, ?, CURDATE(), ?, 'Active', ?)");
        $supplier_id = (int)$_POST['supplier_id'];
        $document_name = $_POST['doc_reference'] !== '' ? $_POST['doc_reference'] : $_POST['doc_type'];
        $file_path = $_POST['file_path'] !== '' ? $_POST['file_path'] : null;
        $expiry_date = $_POST['expires_date'] !== '' ? $_POST['expires_date'] : null;
        $uploaded_by = (int)$_SESSION['user_id'];
        $stmt->bind_param(
            "issssi",
            $supplier_id,
            $_POST['doc_type'],
            $document_name,
            $file_path,
            $expiry_date,
            $uploaded_by
        );
        if ($stmt->execute()) {
            $flash = "Document added successfully.";
        } else {
            $db_error = "Failed to add document: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'delete') {
        $doc_id = (int)$_POST['doc_id'];
        $stmt = $conn->prepare("DELETE FROM supplier_documents WHERE document_id = ?");
        $stmt->bind_param("i", $doc_id);
        if ($stmt->execute()) {
            $flash = "Document removed.";
        } else {
            $db_error = "Failed to remove document: " . $stmt->error;
        }
        $stmt->close();
    }
}

$documents = [];
$suppliers = [];
$expiringSoonCount = 0;
$expiredCount = 0;

if (!$conn->connect_error) {
    $sup_result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    if ($sup_result) {
        while ($row = $sup_result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }

    $sql = "SELECT d.document_id, d.supplier_id, d.document_type, d.document_name, d.file_path,
                   d.upload_date, d.expiry_date, s.supplier_name,
                   DATEDIFF(d.expiry_date, CURDATE()) AS days_to_expiry
            FROM supplier_documents d
            JOIN suppliers s ON d.supplier_id = s.supplier_id
            ORDER BY (d.expiry_date IS NULL), d.expiry_date ASC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['expiry_date'] !== null) {
                if ((int)$row['days_to_expiry'] < 0) {
                    $expiredCount++;
                } elseif ((int)$row['days_to_expiry'] <= 30) {
                    $expiringSoonCount++;
                }
            }
            $documents[] = $row;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Contracts &amp; Compliance</title>
    <link href="app.css?v=<?php echo @filemtime('app.css'); ?>" rel="stylesheet"/>
    <script src="app.js?v=<?php echo @filemtime('app.js'); ?>" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <?php include 'header.php'; ?>
<main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Contracts &amp; Compliance</h1>
                        <p class="text-slate-500 text-sm mt-1">Accreditation, signed contracts, and expiring documents per vendor.</p>
                    </div>
                    <button type="button" onclick="document.getElementById('add-doc-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition-colors">
                        <span class="material-symbols-outlined text-base">note_add</span>
                        Add Document
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl px-4 py-3"><?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium rounded-xl px-4 py-3"><?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <?php if ($expiredCount > 0 || $expiringSoonCount > 0): ?>
                    <div class="bg-amber-50 border border-amber-200 text-amber-800 text-xs font-medium rounded-xl px-4 py-3 flex items-center gap-2">
                        <span class="material-symbols-outlined text-base">warning</span>
                        <span>
                            <?php if ($expiredCount > 0): ?><strong><?php echo $expiredCount; ?></strong> document<?php echo $expiredCount > 1 ? 's' : ''; ?> expired.<?php endif; ?>
                            <?php if ($expiringSoonCount > 0): ?> <strong><?php echo $expiringSoonCount; ?></strong> expiring within 30 days.<?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-sm font-bold text-slate-900">Vendor Documents</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Vendor</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Document Type</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Reference No.</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Issued</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Expires</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Status</th>
                                    <th class="px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($documents)): ?>
                                    <tr><td colspan="7" class="px-6 py-8 text-center text-xs text-slate-400">No documents on file yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($documents as $d):
                                        $days = $d['expiry_date'] !== null ? (int)$d['days_to_expiry'] : null;
                                        if ($days === null) {
                                            $statusLabel = 'No expiry';
                                            $statusClass = 'text-slate-600 bg-slate-100 border-slate-200';
                                        } elseif ($days < 0) {
                                            $statusLabel = 'Expired';
                                            $statusClass = 'text-rose-700 bg-rose-50 border-rose-200';
                                        } elseif ($days <= 30) {
                                            $statusLabel = "Expires in {$days}d";
                                            $statusClass = 'text-amber-700 bg-amber-50 border-amber-200';
                                        } else {
                                            $statusLabel = 'Valid';
                                            $statusClass = 'text-emerald-700 bg-emerald-50 border-emerald-200';
                                        }
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 text-xs font-medium text-slate-900"><?php echo htmlspecialchars($d['supplier_name']); ?></td>
                                            <td class="px-6 py-4 text-xs text-slate-600"><?php echo htmlspecialchars($d['document_type']); ?></td>
                                            <td class="px-6 py-4 text-xs text-slate-500"><?php echo htmlspecialchars($d['document_name'] ?? '—'); ?></td>
                                            <td class="px-6 py-4 text-xs text-slate-500"><?php echo $d['upload_date'] ? date("M j, Y", strtotime($d['upload_date'])) : '—'; ?></td>
                                            <td class="px-6 py-4 text-xs text-slate-500"><?php echo $d['expiry_date'] ? date("M j, Y", strtotime($d['expiry_date'])) : '—'; ?></td>
                                            <td class="px-6 py-4 text-xs">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                            </td>
                                            <td class="px-6 py-4 text-xs">
                                                <form method="POST" onsubmit="return confirm('Remove this document?');" class="inline">
                                                    <input type="hidden" name="action" value="delete"/>
                                                    <input type="hidden" name="doc_id" value="<?php echo (int)$d['document_id']; ?>"/>
                                                    <button type="submit" class="text-rose-500 hover:text-rose-700" title="Delete">
                                                        <span class="material-symbols-outlined text-base">delete</span>
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
            </div>
        </main>
    </div>

    <div id="add-doc-modal" class="hidden fixed inset-0 bg-slate-900/40 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-slate-900">Add Vendor Document</h3>
                <button type="button" onclick="document.getElementById('add-doc-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add"/>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Vendor</label>
                    <select name="supplier_id" required class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2">
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?php echo (int)$s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Document Type</label>
                    <select name="doc_type" required class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2">
                        <?php foreach ($docTypes as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Reference Number</label>
                    <input type="text" name="doc_reference" class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2" placeholder="e.g. DTI-2026-000123"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Expiry Date</label>
                    <input type="date" name="expires_date" class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2"/>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">File Path / Reference</label>
                    <input type="text" name="file_path" class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2" placeholder="documents/filename.pdf"/>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white text-xs font-semibold rounded-lg py-2.5 hover:bg-indigo-700 transition-colors">Save Document</button>
            </form>
        </div>
    </div>
</body>
</html>