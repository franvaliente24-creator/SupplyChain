<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "Inventory Management System";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO inventory_items (sku, item_name, category, supplier_id, quantity, unit, reorder_level, unit_price, status, warehouse_zone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $supplier_id = $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
        $qty = (int)$_POST['quantity'];
        $reorder = (int)$_POST['reorder_level'];
        $price = (float)$_POST['unit_price'];
        
        $stmt->bind_param("sssiisidss", 
            $_POST['sku'], 
            $_POST['item_name'], 
            $_POST['category'], 
            $supplier_id, 
            $qty, 
            $_POST['unit'], 
            $reorder, 
            $price, 
            $_POST['status'], 
            $_POST['warehouse_zone']
        );
        
        if ($stmt->execute()) {
            $flash = "New item added successfully.";
            $log_msg = "Added new inventory item: " . $_POST['item_name'];
            $conn->query("INSERT INTO activity_log (label, status, status_class) VALUES ('$log_msg', 'New', 'status-pill-accent')");
        } else {
            $db_error = "Failed to add item: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'edit') {
        $item_id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("UPDATE inventory_items SET sku = ?, item_name = ?, category = ?, supplier_id = ?, quantity = ?, unit = ?, reorder_level = ?, unit_price = ?, status = ?, warehouse_zone = ? WHERE item_id = ?");
        $supplier_id = $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
        $qty = (int)$_POST['quantity'];
        $reorder = (int)$_POST['reorder_level'];
        $price = (float)$_POST['unit_price'];

        $stmt->bind_param("sssiisidssi", 
            $_POST['sku'], 
            $_POST['item_name'], 
            $_POST['category'], 
            $supplier_id, 
            $qty, 
            $_POST['unit'], 
            $reorder, 
            $price, 
            $_POST['status'], 
            $_POST['warehouse_zone'],
            $item_id
        );

        if ($stmt->execute()) {
            $flash = "Item parameters updated successfully.";
        } else {
            $db_error = "Failed to update item: " . $stmt->error;
        }
        $stmt->close();
    }

    if ($action === 'delete') {
        $item_id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("DELETE FROM inventory_items WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) {
            $flash = "Item deleted successfully.";
        } else {
            $db_error = "Failed to delete item: " . $stmt->error;
        }
        $stmt->close();
    }
}

$items = [];
$suppliers = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (!$conn->connect_error) {
    $sql = "SELECT i.*, s.supplier_name FROM inventory_items i LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id";
    if ($search !== '') {
        $sql .= " WHERE i.item_name LIKE ? OR i.sku LIKE ? OR i.category LIKE ?";
    }
    $sql .= " ORDER BY i.updated_at DESC";

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
            $items[] = $row;
        }
    }

    $sup_res = $conn->query("SELECT supplier_id, supplier_name FROM suppliers WHERE is_active = 1 ORDER BY supplier_name");
    if ($sup_res) {
        while ($row = $sup_res->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
} else {
    $db_error = "Database connection offline.";
}

function getStatusClass($status) {
    switch ($status) {
        case 'Active': return 'status-badge-active';
        case 'Low Stock': return 'status-badge-maintenance';
        case 'Out of Stock': return 'status-badge-critical';
        case 'Archived': return 'status-badge-archived';
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
        .form-field input, .form-field select {
            width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">ISMERS System Ecosystem</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Inventory Items Workspace</h1>
                        <p class="text-slate-500 text-sm mt-1">Add raw stock profiles, assign preferred vendors, and coordinate warehouse locations.</p>
                    </div>
                    <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-primary text-on-primary rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add_circle</span> Add Stock Profile
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
                            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Filter by keyword, SKU, or type..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                        </form>
                        <?php if ($search !== ''): ?>
                            <a href="inventory.php" class="text-xs font-semibold text-slate-500 hover:text-primary">Reset Filter</a>
                        <?php endif; ?>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">SKU Code</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Product Identifier</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Class / Category</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Unit Cost</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Location</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider">Operational Status</th>
                                    <th class="px-6 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-10 text-center text-slate-400">No inventory products verified in database.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): 
                                        $status_badge = getStatusClass($item['status']);
                                    ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-6 py-4 font-mono font-semibold text-primary"><?php echo htmlspecialchars($item['sku']); ?></td>
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <div class="text-[10px] text-slate-400">Supplier: <?php echo htmlspecialchars($item['supplier_name'] ?: 'N/A'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($item['category'] ?: 'Unassigned'); ?></td>
                                            <td class="px-6 py-4 font-semibold text-slate-900">₱<?php echo number_format((float)$item['unit_price'], 2); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="font-bold"><?php echo (int)$item['quantity']; ?></span> <span class="text-slate-400 text-[10px]"><?php echo htmlspecialchars($item['unit']); ?></span>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-slate-500"><?php echo htmlspecialchars($item['warehouse_zone'] ?: 'Unshelved'); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="status-badge <?php echo $status_badge; ?>" style="padding: 2px 8px; font-size: 0.65rem; border-radius: 999px;">
                                                    <?php echo htmlspecialchars($item['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                                <div class="inline-flex gap-1">
                                                    <button type="button" onclick='openEditModal(<?php echo json_encode($item); ?>)' class="p-1 rounded hover:bg-slate-100 text-slate-600" title="Edit Item">
                                                        <span class="material-symbols-outlined text-sm">edit</span>
                                                    </button>
                                                    <form method="post" onsubmit="return confirm('Archive this physical item profile?');" style="display:inline;">
                                                        <input type="hidden" name="action" value="delete"/>
                                                        <input type="hidden" name="item_id" value="<?php echo (int)$item['item_id']; ?>"/>
                                                        <button type="submit" class="p-1 rounded hover:bg-red-50 text-red-600" title="Delete">
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
            </div>
        </main>
    </div>

    <div id="item-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modal-title" class="text-base font-bold text-slate-900">New Inventory Entry</h3>
                <button type="button" onclick="closeModal()" class="p-1 rounded-full hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" id="form-action" value="add"/>
                <input type="hidden" name="item_id" id="form-item-id" value=""/>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>SKU Code</label>
                        <input type="text" name="sku" id="f-sku" placeholder="SYS-1011" required/>
                    </div>
                    <div class="form-field">
                        <label>Item Name</label>
                        <input type="text" name="item_name" id="f-name" placeholder="Copper Pipe Fittings" required/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Category Group</label>
                        <input type="text" name="category" id="f-category" placeholder="Hardware Supplies"/>
                    </div>
                    <div class="form-field">
                        <label>Supplier Vendor</label>
                        <select name="supplier_id" id="f-supplier">
                            <option value="">No Vendor Assigned</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?php echo $s['supplier_id']; ?>"><?php echo htmlspecialchars($s['supplier_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="form-field">
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="f-qty" min="0" required/>
                    </div>
                    <div class="form-field">
                        <label>Stock Unit</label>
                        <input type="text" name="unit" id="f-unit" placeholder="pcs" required/>
                    </div>
                    <div class="form-field">
                        <label>Reorder Limit</label>
                        <input type="number" name="reorder_level" id="f-reorder" min="1" required/>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="form-field">
                        <label>Unit Price (₱)</label>
                        <input type="number" step="0.01" name="unit_price" id="f-price" required/>
                    </div>
                    <div class="form-field">
                        <label>Zone Location</label>
                        <input type="text" name="warehouse_zone" id="f-zone" placeholder="Zone A"/>
                    </div>
                    <div class="form-field">
                        <label>Status</label>
                        <select name="status" id="f-status">
                            <option value="Active">Active</option>
                            <option value="Low Stock">Low Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Archived">Archived</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modal-title').textContent = 'Add Inventory Stock Profile';
            document.getElementById('form-action').value = 'add';
            document.getElementById('form-item-id').value = '';
            document.getElementById('f-sku').value = '';
            document.getElementById('f-name').value = '';
            document.getElementById('f-category').value = '';
            document.getElementById('f-supplier').value = '';
            document.getElementById('f-qty').value = '0';
            document.getElementById('f-unit').value = 'pcs';
            document.getElementById('f-reorder').value = '10';
            document.getElementById('f-price').value = '0.00';
            document.getElementById('f-zone').value = '';
            document.getElementById('f-status').value = 'Active';
            document.getElementById('item-modal').style.display = 'flex';
        }

        function openEditModal(item) {
            document.getElementById('modal-title').textContent = 'Modify Inventory Item Record';
            document.getElementById('form-action').value = 'edit';
            document.getElementById('form-item-id').value = item.item_id;
            document.getElementById('f-sku').value = item.sku;
            document.getElementById('f-name').value = item.item_name;
            document.getElementById('f-category').value = item.category || '';
            document.getElementById('f-supplier').value = item.supplier_id || '';
            document.getElementById('f-qty').value = item.quantity;
            document.getElementById('f-unit').value = item.unit;
            document.getElementById('f-reorder').value = item.reorder_level;
            document.getElementById('f-price').value = item.unit_price;
            document.getElementById('f-zone').value = item.warehouse_zone || '';
            document.getElementById('f-status').value = item.status;
            document.getElementById('item-modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('item-modal').style.display = 'none';
        }
    </script>
</body>
</html>