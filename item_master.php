<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'ims_connection.php';
$section_title = "Inventory Management System (IMS)";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';
$db_error = null;
$flash = null;

if (!$conn->connect_error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_item') {
        $sku = strtoupper(trim($_POST['sku'] ?? ''));
        $item_name = trim($_POST['item_name'] ?? '');
        $category = trim($_POST['category'] ?? 'General');
        $unit = trim($_POST['unit'] ?? 'pcs');
        $unit_cost = (float)$_POST['unit_cost'];
        $reorder_level = (int)$_POST['reorder_level'];
        $safety_stock = (int)$_POST['safety_stock'];
        $initial_qty = (int)$_POST['initial_qty'];
        $location = trim($_POST['location'] ?? 'Main Warehouse');
        $description = trim($_POST['description'] ?? '');

        if ($sku === '' || $item_name === '') {
            $db_error = "SKU and Item Name are required.";
        } else {
            $conn->begin_transaction();
            try {
                $status = ($initial_qty <= 0) ? 'Out of Stock' : (($initial_qty <= $reorder_level) ? 'Low Stock' : 'Active');
                
                $stmt = $conn->prepare("INSERT INTO item_master (sku, item_name, category, unit, unit_cost, reorder_level, safety_stock, status, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssdiiss", $sku, $item_name, $category, $unit, $unit_cost, $reorder_level, $safety_stock, $status, $description);
                $stmt->execute();
                $item_id = $stmt->insert_id;
                $stmt->close();

                $stmt_stock = $conn->prepare("INSERT INTO stock_inventory (item_id, quantity_on_hand, quantity_reserved, warehouse_location, last_restocked_at) VALUES (?, ?, 0, ?, NOW())");
                $stmt_stock->bind_param("iis", $item_id, $initial_qty, $location);
                $stmt_stock->execute();
                $stmt_stock->close();

                $conn->commit();
                $flash = "Item '$item_name' registered in Item Master.";
            } catch (Exception $e) {
                $conn->rollback();
                $db_error = "Failed to register item: " . $e->getMessage();
            }
        }
    }

    if ($action === 'edit_item') {
        $item_id = (int)$_POST['item_id'];
        $item_name = trim($_POST['item_name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $unit = trim($_POST['unit'] ?? 'pcs');
        $unit_cost = (float)$_POST['unit_cost'];
        $reorder_level = (int)$_POST['reorder_level'];
        $safety_stock = (int)$_POST['safety_stock'];
        $status = $_POST['status'] ?? 'Active';
        $description = trim($_POST['description'] ?? '');

        $stmt = $conn->prepare("UPDATE item_master SET item_name = ?, category = ?, unit = ?, unit_cost = ?, reorder_level = ?, safety_stock = ?, status = ?, description = ? WHERE item_id = ?");
        $stmt->bind_param("sssdiissi", $item_name, $category, $unit, $unit_cost, $reorder_level, $safety_stock, $status, $description, $item_id);
        if ($stmt->execute()) {
            $flash = "Item updated successfully.";
        } else {
            $db_error = "Failed to update item: " . $stmt->error;
        }
        $stmt->close();
    }
}

$items = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter_cat = isset($_GET['category']) ? trim($_GET['category']) : '';
$categories = [];

if (!$conn->connect_error) {
    $cat_res = $conn->query("SELECT DISTINCT category FROM item_master WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    if ($cat_res) while ($r = $cat_res->fetch_assoc()) $categories[] = $r['category'];

    $sql = "SELECT im.*, COALESCE(si.quantity_on_hand, 0) AS on_hand, COALESCE(si.warehouse_location, 'Unassigned') AS location
            FROM item_master im
            LEFT JOIN stock_inventory si ON im.item_id = si.item_id
            WHERE 1=1";
    $params = [];
    $types = '';

    if ($search !== '') {
        $sql .= " AND (im.sku LIKE ? OR im.item_name LIKE ? OR im.description LIKE ?)";
        $like = "%$search%";
        $params = [$like, $like, $like];
        $types = 'sss';
    }

    if ($filter_cat !== '') {
        $sql .= " AND im.category = ?";
        $params[] = $filter_cat;
        $types .= 's';
    }

    $sql .= " ORDER BY im.created_at DESC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $conn->query($sql);
    }

    if ($res) while ($row = $res->fetch_assoc()) $items[] = $row;
} else {
    $db_error = "Database offline.";
}

function getItemBadge($status) {
    switch ($status) {
        case 'Active': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'Low Stock': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'Out of Stock': return 'bg-rose-100 text-rose-800 border-rose-200';
        default: return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Item Master</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.55); display: flex; align-items: center; justify-content: center; z-index: 100; padding: 1rem; }
        .modal-box { background: #fff; border-radius: 1rem; width: 100%; max-width: 34rem; padding: 1.5rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .form-field { margin-bottom: 0.85rem; }
        .form-field label { display:block; font-size:0.75rem; font-weight:600; margin-bottom:0.25rem; color:#475569; }
        .form-field input, .form-field select, .form-field textarea { width:100%; padding:0.5rem 0.75rem; border:1px solid #cbd5e1; border-radius:0.5rem; font-size:0.875rem; }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Inventory Management System</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-600 text-sm font-medium">Item Master</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-surface-container-lowest p-6 md:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Item Master Directory</h1>
                        <p class="text-slate-500 text-sm mt-1">Central catalog of SKUs, categories, unit valuations, and reorder levels.</p>
                    </div>
                    <button type="button" onclick="openAddItemModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-xs sm:text-sm font-semibold hover:bg-primary/90 transition shadow-sm inline-flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[18px]">add_box</span> Add Master Item
                    </button>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg"><?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-200">
                        <form method="get" class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search SKU, item name, or description..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none"/>
                            </div>
                            <select name="category" class="px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter_cat === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-semibold">Filter</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">SKU Code</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Item & Category</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Unit Cost</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">On Hand</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Reorder Point</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($items)): ?>
                                    <tr><td colspan="7" class="px-6 py-10 text-center text-slate-400">No items found in Item Master.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 font-mono font-bold text-primary flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-[16px] text-slate-400">qr_code_2</span>
                                                <?php echo htmlspecialchars($item['sku']); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-slate-900"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                                <div class="text-[10px] text-slate-500"><?php echo htmlspecialchars($item['category']); ?> &bull; Unit: <?php echo htmlspecialchars($item['unit']); ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono font-semibold text-slate-800">&#8369;<?php echo number_format($item['unit_cost'], 2); ?></td>
                                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900"><?php echo number_format($item['on_hand']); ?></td>
                                            <td class="px-4 py-3 text-right font-mono text-slate-500"><?php echo $item['reorder_level']; ?></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold border <?php echo getItemBadge($item['status']); ?>">
                                                    <?php echo htmlspecialchars($item['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                                <button type="button" onclick='openEditModal(<?php echo json_encode($item); ?>)' class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-[11px]">
                                                    Edit
                                                </button>
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

    <!-- Add Item Modal -->
    <div id="add-item-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Add Item to Master Catalog</h3>
                <button type="button" onclick="closeAddItemModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="add_item"/>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>SKU Code</label>
                        <input type="text" name="sku" placeholder="SYS-2001" required/>
                    </div>
                    <div class="form-field">
                        <label>Category</label>
                        <input type="text" name="category" placeholder="Electronics / Furniture" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Item Name</label>
                    <input type="text" name="item_name" placeholder="Wireless Barcode Scanner" required/>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="form-field">
                        <label>Unit (pcs, box)</label>
                        <input type="text" name="unit" value="pcs" required/>
                    </div>
                    <div class="form-field">
                        <label>Unit Cost (&#8369;)</label>
                        <input type="number" step="0.01" name="unit_cost" min="0" value="0.00" required/>
                    </div>
                    <div class="form-field">
                        <label>Initial Stock</label>
                        <input type="number" name="initial_qty" min="0" value="0" required/>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Reorder Point</label>
                        <input type="number" name="reorder_level" min="1" value="10" required/>
                    </div>
                    <div class="form-field">
                        <label>Safety Stock</label>
                        <input type="number" name="safety_stock" min="0" value="5" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Location Storage Bay</label>
                    <input type="text" name="location" placeholder="Zone A - Shelf 01"/>
                </div>
                <div class="form-field">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="Specifications..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddItemModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Register Item</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="edit-item-modal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Edit Master Item: <span id="edit-item-sku"></span></h3>
                <button type="button" onclick="closeEditModal()" class="p-1 rounded-full hover:bg-slate-100"><span class="material-symbols-outlined text-lg">close</span></button>
            </div>
            <form method="post" class="space-y-3">
                <input type="hidden" name="action" value="edit_item"/>
                <input type="hidden" name="item_id" id="edit-item-id"/>
                <div class="grid grid-cols-2 gap-3">
                    <div class="form-field">
                        <label>Category</label>
                        <input type="text" name="category" id="edit-item-cat" required/>
                    </div>
                    <div class="form-field">
                        <label>Status</label>
                        <select name="status" id="edit-item-status">
                            <option value="Active">Active</option>
                            <option value="Low Stock">Low Stock</option>
                            <option value="Out of Stock">Out of Stock</option>
                            <option value="Discontinued">Discontinued</option>
                        </select>
                    </div>
                </div>
                <div class="form-field">
                    <label>Item Name</label>
                    <input type="text" name="item_name" id="edit-item-name" required/>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="form-field">
                        <label>Unit</label>
                        <input type="text" name="unit" id="edit-item-unit" required/>
                    </div>
                    <div class="form-field">
                        <label>Unit Cost (&#8369;)</label>
                        <input type="number" step="0.01" name="unit_cost" id="edit-item-cost" required/>
                    </div>
                    <div class="form-field">
                        <label>Reorder Point</label>
                        <input type="number" name="reorder_level" id="edit-item-reorder" required/>
                    </div>
                </div>
                <div class="form-field">
                    <label>Safety Stock</label>
                    <input type="number" name="safety_stock" id="edit-item-safety" required/>
                </div>
                <div class="form-field">
                    <label>Description</label>
                    <textarea name="description" id="edit-item-desc" rows="2"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 border rounded-lg text-xs font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-xs font-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddItemModal() { document.getElementById('add-item-modal').style.display = 'flex'; }
        function closeAddItemModal() { document.getElementById('add-item-modal').style.display = 'none'; }
        function openEditModal(item) {
            document.getElementById('edit-item-id').value = item.item_id;
            document.getElementById('edit-item-sku').textContent = item.sku;
            document.getElementById('edit-item-name').value = item.item_name;
            document.getElementById('edit-item-cat').value = item.category;
            document.getElementById('edit-item-unit').value = item.unit;
            document.getElementById('edit-item-cost').value = item.unit_cost;
            document.getElementById('edit-item-reorder').value = item.reorder_level;
            document.getElementById('edit-item-safety').value = item.safety_stock;
            document.getElementById('edit-item-status').value = item.status;
            document.getElementById('edit-item-desc').value = item.description || '';
            document.getElementById('edit-item-modal').style.display = 'flex';
        }
        function closeEditModal() { document.getElementById('edit-item-modal').style.display = 'none'; }
    </script>
</body>
</html>