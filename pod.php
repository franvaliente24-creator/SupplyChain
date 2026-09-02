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

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm') {
        $manifest_id = (int)$_POST['manifest_id'];
        $received_by = trim($_POST['received_by'] ?? '');
        $signature_url = $_POST['signature_url'] !== '' ? trim($_POST['signature_url']) : null;
        $photo_url = $_POST['photo_url'] !== '' ? trim($_POST['photo_url']) : null;
        $confirmed_at = $_POST['confirmed_at'] !== '' ? $_POST['confirmed_at'] : date('Y-m-d H:i:s');
        $notes = $_POST['discrepancy_notes'] !== '' ? trim($_POST['discrepancy_notes']) : null;

        if ($received_by === '') {
            $db_error = "Receiver name is required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO delivery_confirmations (manifest_id, received_by, signature_url, photo_url, confirmed_at, discrepancy_notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $manifest_id, $received_by, $signature_url, $photo_url, $confirmed_at, $notes);

            if ($stmt->execute()) {
                $dateOnly = date('Y-m-d', strtotime($confirmed_at));
                $upd = $conn->prepare("UPDATE logistics_manifests SET actual_delivery_date = ? WHERE manifest_id = ?");
                $upd->bind_param("si", $dateOnly, $manifest_id);
                $upd->execute();
                $upd->close();

                $flash = "Proof of delivery recorded for manifest #$manifest_id.";
                $log_msg = "Delivery confirmed for Manifest #$manifest_id, received by " . $received_by;
                $safe_log = $conn->real_escape_string($log_msg);
                $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$safe_log', 'Verified', 'status-pill-success')");
            } else {
                $db_error = "Failed to record delivery confirmation: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$rows = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$conn->connect_error) {
    $sql = "SELECT m.manifest_id, m.manifest_number, m.dispatch_date, m.estimated_delivery, m.actual_delivery_date,
               m.order_number,
               dc.confirmation_id, dc.received_by, dc.confirmed_at, dc.discrepancy_notes
        FROM logistics_manifests m
        LEFT JOIN delivery_confirmations dc ON dc.manifest_id = m.manifest_id
        WHERE m.delivery_status = 'Delivered'";
    if ($search !== '') {
        $sql .= " AND (m.manifest_number LIKE ? OR o.order_number LIKE ? OR dc.received_by LIKE ?)";
    }
    $sql .= " ORDER BY m.dispatch_date DESC";

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
            $rows[] = $row;
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
    <title><?php echo $section_title; ?> — Delivery Confirmation</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <style>
        .status-badge-active   { background: #dcfce7; color: #166534; }
        .status-badge-pending  { background: #fef3c7; color: #92400e; }

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
        <?php include 'header.php'; ?>
<main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Delivery Confirmation (POD)</h1>
                    <p class="text-slate-500 text-sm mt-1">Proof-of-delivery for manifests marked delivered — receiver, signature/photo reference, and discrepancy notes.</p>
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
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search manifest, PO, or receiver..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Manifest</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Purchase Link</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Actual Delivery</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Received By</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Discrepancies</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No delivered manifests found yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): $isConfirmed = !empty($r['confirmation_id']); ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-mono font-bold text-slate-900"><?php echo htmlspecialchars($r['manifest_number']); ?></td>
                                            <td class="px-6 py-4 font-semibold text-primary"><?php echo htmlspecialchars($r['order_number'] ?: 'Direct Fleet'); ?></td>
                                            <td class="px-6 py-4 text-slate-500"><?php echo $r['actual_delivery_date'] ? date('M j, Y', strtotime($r['actual_delivery_date'])) : '—'; ?></td>
                                            <td class="px-6 py-4 text-slate-700"><?php echo $isConfirmed ? htmlspecialchars($r['received_by']) : '—'; ?></td>
                                            <td class="px-6 py-4 text-slate-500 max-w-[200px] truncate"><?php echo $isConfirmed && $r['discrepancy_notes'] ? htmlspecialchars($r['discrepancy_notes']) : ($isConfirmed ? 'None noted' : '—'); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="status-badge <?php echo $isConfirmed ? 'status-badge-active' : 'status-badge-pending'; ?>" style="padding: 2px 8px; font-size: 0.65rem; border-radius: 999px;">
                                                    <?php echo $isConfirmed ? 'Confirmed' : 'Pending POD'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <?php if (!$isConfirmed): ?>
                                                    <button type="button" onclick="openConfirmModal(<?php echo (int)$r['manifest_id']; ?>, '<?php echo htmlspecialchars($r['manifest_number'], ENT_QUOTES); ?>')" class="px-3 py-1.5 bg-primary text-white rounded-lg text-[11px] font-bold inline-flex items-center gap-1">
                                                        <span class="material-symbols-outlined text-[14px]">assignment_turned_in</span> Confirm
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 font-semibold">Recorded</span>
                                                <?php endif; ?>
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

    <div id="confirm-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Record Proof of Delivery — <span id="modal-manifest-label"></span></h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="confirm"/>
                <input type="hidden" name="manifest_id" id="modal-manifest-id"/>

                <div class="form-field">
                    <label>Received By</label>
                    <input type="text" name="received_by" placeholder="Full name of receiver" required/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Signature Reference (URL/path)</label>
                        <input type="text" name="signature_url" placeholder="signatures/mnf-001.png"/>
                    </div>
                    <div class="form-field">
                        <label>Photo Reference (URL/path)</label>
                        <input type="text" name="photo_url" placeholder="delivery_photos/mnf-001.jpg"/>
                    </div>
                </div>

                <div class="form-field">
                    <label>Confirmed At</label>
                    <input type="datetime-local" name="confirmed_at"/>
                </div>

                <div class="form-field">
                    <label>Discrepancy Notes (optional)</label>
                    <textarea name="discrepancy_notes" rows="3" placeholder="e.g. 2 units short, box damaged on arrival..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save Confirmation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openConfirmModal(manifestId, manifestLabel) {
            document.getElementById('modal-manifest-id').value = manifestId;
            document.getElementById('modal-manifest-label').textContent = manifestLabel;
            document.getElementById('confirm-modal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('confirm-modal').style.display = 'none';
        }
    </script>
</body>
</html>