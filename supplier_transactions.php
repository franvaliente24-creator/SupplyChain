<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Supplier Transaction History";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

// Check if supplier_transactions table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'supplier_transactions'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}
$check_table->free();

if ($table_exists && !$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_transaction') {
        $stmt = $conn->prepare("INSERT INTO supplier_transactions (supplier_id, transaction_type, reference_number, amount, transaction_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
        
        $amount = !empty($_POST['amount']) ? (float)$_POST['amount'] : null;
        
        $stmt->bind_param("issdsd", 
            (int)$_POST['supplier_id'],
            $_POST['transaction_type'],
            $_POST['reference_number'],
            $amount,
            $_POST['transaction_date'],
            $_POST['notes']
        );

        if ($stmt->execute()) {
            $flash = "Transaction recorded successfully.";
            $log_msg = "Added supplier transaction: " . $_POST['transaction_type'] . " for supplier ID: " . $_POST['supplier_id'];
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'Supplier Transaction', '$log_msg')");
        } else {
            $db_error = "Failed to record transaction: " . $stmt->error;
        }
        $stmt->close();
    }
}

$transactions = [];
$suppliers = [];

if ($table_exists && !$conn->connect_error) {
    $sql = "SELECT st.*, s.supplier_name 
            FROM supplier_transactions st 
            LEFT JOIN suppliers s ON st.supplier_id = s.supplier_id 
            ORDER BY st.transaction_date DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
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
        $db_error = "Supplier transactions table not found. Please run the schema_updates.sql file to create it.";
    } else {
        $db_error = "Database connection offline.";
    }
}

function getTransactionTypeBadgeClass($type) {
    switch ($type) {
        case 'Quote': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'Purchase Order': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'Delivery': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Payment': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Return': return 'bg-red-100 text-red-800 border-red-200';
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
                        <h1 class="text-2xl font-bold text-slate-900">Supplier Transaction History</h1>
                        <p class="text-slate-500 text-sm mt-1">Complete record of quotations, purchase orders, deliveries, and payments by supplier.</p>
                    </div>
                    <?php if ($table_exists): ?>
                        <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px]">add_circle</span> Add Transaction
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
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Type</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Reference</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">No supplier transactions found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions as $trans): 
                                            $type_badge = getTransactionTypeBadgeClass($trans['transaction_type']);
                                        ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3 text-slate-500"><?php echo date('M j, Y', strtotime($trans['transaction_date'])); ?></td>
                                                <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($trans['supplier_name'] ?: 'Unknown'); ?></td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border <?php echo $type_badge; ?>">
                                                        <?php echo htmlspecialchars($trans['transaction_type']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($trans['reference_number'] ?: '—'); ?></td>
                                                <td class="px-4 py-3 text-slate-600"><?php echo $trans['amount'] ? '₱' . number_format($trans['amount'], 2) : '—'; ?></td>
                                                <td class="px-4 py-3 text-slate-500 max-w-xs truncate"><?php echo htmlspecialchars($trans['notes'] ?: '—'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-amber-50 border border-amber-200 text-amber-700 text-sm px-4 py-3 rounded-lg">
                        <p><strong>Supplier transaction history is not yet enabled.</strong></p>
                        <p class="mt-2">To enable this feature, run the SQL schema updates:</p>
                        <code class="block mt-2 bg-amber-100 p-2 rounded">mysql -u root -p supplychain < schema_updates.sql</code>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Transaction Modal -->
    <div id="add-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Add Supplier Transaction</h3>
                <button type="button" onclick="closeAddModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="add_transaction"/>

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
                    <label>Transaction Type</label>
                    <select name="transaction_type" required>
                        <option value="Quote">Quote</option>
                        <option value="Purchase Order">Purchase Order</option>
                        <option value="Delivery">Delivery</option>
                        <option value="Payment">Payment</option>
                        <option value="Return">Return</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Reference Number</label>
                    <input type="text" name="reference_number" placeholder="PO-2026-001"/>
                </div>

                <div class="form-field">
                    <label>Amount (₱)</label>
                    <input type="number" step="0.01" name="amount" placeholder="0.00"/>
                </div>

                <div class="form-field">
                    <label>Transaction Date</label>
                    <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required/>
                </div>

                <div class="form-field">
                    <label>Notes</label>
                    <textarea name="notes" rows="2" placeholder="Additional details..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Add Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('add-modal').style.display = 'flex';
        }

        function closeAddModal() {
            document.getElementById('add-modal').style.display = 'none';
        }
    </script>
</body>
</html>