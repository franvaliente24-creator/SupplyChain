<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Supplier Document Management";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Check if supplier_documents table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'supplier_documents'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}
$check_table->free();

if ($table_exists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_document') {
        // Handle file upload
        $file_path = '';
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/supplier_documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['document_file']['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $file_path)) {
                $file_path = $file_name; // Store just the filename
            } else {
                $db_error = "Failed to upload file.";
            }
        }
        
        if (empty($db_error)) {
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
            
            $stmt = $conn->prepare("INSERT INTO supplier_documents (supplier_id, document_type, document_name, file_path, upload_date, expiry_date, status, uploaded_by) VALUES (?, ?, ?, ?, CURDATE(), ?, 'Active', ?)");
            
            $supplier_id = (int)$_POST['supplier_id'];
            $uploaded_by = (int)$_SESSION['user_id'];

            $stmt->bind_param("issssi", 
                $supplier_id,
                $_POST['document_type'],
                $_POST['document_name'],
                $file_path,
                $expiry_date,
                $uploaded_by
            );

            if ($stmt->execute()) {
                $flash = "Document uploaded successfully.";
                $log_msg = "Uploaded document for supplier ID: " . $_POST['supplier_id'];
                $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'Document Upload', '$log_msg')");
            } else {
                $db_error = "Failed to save document record: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if ($action === 'delete_document') {
        $doc_id = (int)$_POST['document_id'];
        
        // Get file path before deletion
        $file_stmt = $conn->prepare("SELECT file_path FROM supplier_documents WHERE document_id = ?");
        $file_stmt->bind_param("i", $doc_id);
        $file_stmt->execute();
        $file_result = $file_stmt->get_result()->fetch_assoc();
        $file_stmt->close();
        
        // Delete from database
        $stmt = $conn->prepare("DELETE FROM supplier_documents WHERE document_id = ?");
        $stmt->bind_param("i", $doc_id);
        
        if ($stmt->execute()) {
            // Delete physical file
            if ($file_result && !empty($file_result['file_path'])) {
                $full_path = 'uploads/supplier_documents/' . $file_result['file_path'];
                if (file_exists($full_path)) {
                    unlink($full_path);
                }
            }
            $flash = "Document deleted successfully.";
        } else {
            $db_error = "Failed to delete document: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'update_status') {
        $doc_id = (int)$_POST['document_id'];
        $new_status = $_POST['new_status'];
        
        $stmt = $conn->prepare("UPDATE supplier_documents SET status = ? WHERE document_id = ?");
        $stmt->bind_param("si", $new_status, $doc_id);
        
        if ($stmt->execute()) {
            $flash = "Document status updated to $new_status.";
        } else {
            $db_error = "Failed to update status: " . $stmt->error;
        }
        $stmt->close();
    }
}

$documents = [];
$suppliers = [];

if ($table_exists && !$conn->connect_error) {
    $sql = "SELECT sd.*, s.supplier_name, u.username as uploaded_by_name 
            FROM supplier_documents sd 
            LEFT JOIN suppliers s ON sd.supplier_id = s.supplier_id 
            LEFT JOIN users u ON sd.uploaded_by = u.user_id 
            ORDER BY sd.upload_date DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
    }
    
    // Get active suppliers
    $sup_result = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    if ($sup_result) {
        while ($row = $sup_result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
} else {
    if (!$table_exists) {
        $db_error = "Supplier documents table not found. Please run the schema_updates.sql file to create it.";
    } else {
        $db_error = "Database connection offline.";
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Active': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Expired': return 'bg-red-100 text-red-800 border-red-200';
        case 'Pending Renewal': return 'bg-amber-100 text-amber-800 border-amber-200';
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
        <?php include 'header.php'; ?>
<main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Supplier Document Management</h1>
                        <p class="text-slate-500 text-sm mt-1">Upload and manage compliance documents, contracts, and certifications for suppliers.</p>
                    </div>
                    <?php if ($table_exists): ?>
                        <button type="button" onclick="openUploadModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">upload_file</span> Upload Document
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
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Document Name</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Upload Date</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Expiry</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($documents)): ?>
                                        <tr>
                                            <td colspan="7" class="px-6 py-10 text-center text-slate-400">No supplier documents found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($documents as $doc): 
                                            $status_badge = getStatusBadgeClass($doc['status']);
                                        ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3">
                                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($doc['document_name']); ?></div>
                                                    <?php if ($doc['file_path']): ?>
                                                        <a href="uploads/supplier_documents/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-[10px] text-blue-600 hover:underline">View File</a>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($doc['supplier_name'] ?: 'Unknown'); ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y', strtotime($doc['upload_date'])); ?></td>
                                                <td class="px-4 py-3 text-slate-500"><?php echo $doc['expiry_date'] ? date('M j, Y', strtotime($doc['expiry_date'])) : '—'; ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $status_badge; ?>">
                                                        <?php echo htmlspecialchars($doc['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                    <div class="inline-flex gap-1">
                                                        <button type="button" onclick='openStatusModal(<?php echo json_encode($doc); ?>)' class="p-1 rounded hover:bg-slate-100 text-slate-600" title="Update Status">
                                                            <span class="material-symbols-outlined text-sm">edit</span>
                                                        </button>
                                                        <form method="post" onsubmit="return confirm('Delete this document?');" style="display:inline;">
                                                            <input type="hidden" name="action" value="delete_document"/>
                                                            <input type="hidden" name="document_id" value="<?php echo (int)$doc['document_id']; ?>"/>
                                                            <button type="submit" class="p-1 rounded hover:bg-red-50 text-red-600" title="Delete Document">
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
                <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3 rounded-lg">
                        <p><strong>Supplier document management is not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Upload Modal -->
    <div id="upload-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Upload Supplier Document</h3>
                <button type="button" onclick="closeUploadModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="action" value="upload_document"/>

                <div class="form-field">
                    <label>Supplier</label>
                    <select name="supplier_id" required>
                        <option value="">Select supplier...</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?php echo $sup['supplier_id']; ?>"><?php echo htmlspecialchars($sup['supplier_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label>Document Type</label>
                    <select name="document_type" required>
                        <option value="Contract">Service Contract</option>
                        <option value="Insurance">Insurance Certificate</option>
                        <option value="License">Business License</option>
                        <option value="Tax Compliance">Tax Compliance</option>
                        <option value="Quality Certification">Quality Certification</option>
                        <option value="NDA">Non-Disclosure Agreement</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Document Name</label>
                    <input type="text" name="document_name" placeholder="Annual Service Contract 2026" required/>
                </div>

                <div class="form-field">
                    <label>Document File</label>
                    <input type="file" name="document_file" accept=".pdf,.doc,.docx,.xls,.xlsx" required/>
                </div>

                <div class="form-field">
                    <label>Expiry Date (if applicable)</label>
                    <input type="date" name="expiry_date"/>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeUploadModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Upload Document</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="status-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Update Document Status</h3>
                <button type="button" onclick="closeStatusModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="update_status"/>
                <input type="hidden" name="document_id" id="status-doc-id" value=""/>

                <div class="bg-slate-50 p-3 rounded-lg mb-3">
                    <p class="text-sm"><strong>Document:</strong> <span id="status-doc-name"></span></p>
                </div>

                <div class="form-field">
                    <label>New Status</label>
                    <select name="new_status" required>
                        <option value="Active">Active</option>
                        <option value="Expired">Expired</option>
                        <option value="Pending Renewal">Pending Renewal</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUploadModal() {
            document.getElementById('upload-modal').style.display = 'flex';
        }

        function closeUploadModal() {
            document.getElementById('upload-modal').style.display = 'none';
        }

        function openStatusModal(doc) {
            document.getElementById('status-doc-id').value = doc.document_id;
            document.getElementById('status-doc-name').textContent = doc.document_name;
            document.getElementById('status-modal').style.display = 'flex';
        }

        function closeStatusModal() {
            document.getElementById('status-modal').style.display = 'none';
        }
    </script>
</body>
</html>