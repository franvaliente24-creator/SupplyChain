<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$admin_user = $_SESSION['username'] ?? ($admin_user ?? 'Admin User');
$user_role = $_SESSION['role'] ?? ($user_role ?? 'Supply Chain Manager');

$sub_modules = [
    ["name" => "Dashboard", "icon" => "dashboard", "href" => "dashboard.php"],
    ["name" => "Smart Warehousing System (SWS)", "icon" => "warehouse", "href" => "warehouse.php"],
    ["name" => "Inventory Management System", "icon" => "inventory_2", "href" => "inventory.php"],
    ["name" => "Procurement & Sourcing Management (PSM)", "icon" => "shopping_bag", "href" => "psm.php"],
    [
        "name" => "Supplier / Vendor Management",
        "icon" => "handshake",
        "href" => "suppliers.php",
        "children" => [
            ["name" => "Vendor Directory", "href" => "suppliers.php"],
            ["name" => "Performance Scorecard", "href" => "supplier_performance.php"],
            ["name" => "Contracts & Compliance", "href" => "supplier_contracts.php"],
        ]
    ],
    [
        "name" => "Purchase Order Management",
        "icon" => "receipt_long",
        "href" => "orders.php",
        "children" => [
            ["name" => "Active Purchase Orders", "href" => "orders.php"],
            ["name" => "PO Approvals", "href" => "po_approvals.php"],
            ["name" => "Goods Receipt", "href" => "goods_receipt.php"],
            ["name" => "Order History", "href" => "orders.php"],
        ]
    ],
    [
        "name" => "Document Tracking & Logistics Records System (DTRS)",
        "icon" => "local_shipping",
        "href" => "dtrs.php",
        "children" => [
            ["name" => "Shipment Manifests & Tracking", "href" => "dtrs.php"],
            ["name" => "Delivery Confirmation (POD)", "href" => "pod.php"],
            ["name" => "Document Repository", "href" => "document_repository.php"],
            ["name" => "Carrier / 3PL Directory", "href" => "carriers.php"],
            ["name" => "Customs & Compliance Records", "href" => "customs_records.php"],
        ]
    ]
];

// A module is "open" if the current page is its own href or one of its children's hrefs
function sidebar_module_is_active($mod, $current_page) {
    if ($mod['href'] === $current_page) return true;
    if (!empty($mod['children'])) {
        foreach ($mod['children'] as $child) {
            if ($child['href'] === $current_page) return true;
        }
    }
    return false;
}
?>
<aside id="app-sidebar" class="dashboard-sidebar fixed md:static top-16 bottom-0 md:inset-y-0 left-0 z-50 bg-white flex flex-col h-[calc(100vh-4rem)] md:h-full shrink-0 shadow-xl md:shadow-none transition-all duration-300 ease-in-out overflow-hidden w-72 border-r border-slate-200 translate-x-0">
    <div class="px-5 pt-6 pb-5 border-b border-slate-200">
        <div class="sidebar-brand-card rounded-[2rem] p-6 flex flex-col items-center text-center gap-3" style="background: #f8fbff;">
            <span class="sidebar-brand-icon inline-flex items-center justify-center w-14 h-14 rounded-[1.75rem] bg-slate-100 text-primary text-2xl shadow-sm">
                <span class="material-symbols-outlined">local_shipping</span>
            </span>
            <p class="text-sm font-semibold text-slate-950 truncate w-full"><?php echo htmlspecialchars($section_title ?? 'ISMERS'); ?></p>
            <p class="text-xs text-slate-500 mt-1 truncate">Active Transaction Core</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-5 py-6 space-y-2">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 px-3 mb-2">Supply Chain Sub-Modules</p>
        <?php foreach ($sub_modules as $mod):
            $hasChildren = !empty($mod['children']);
            $isOpen = $hasChildren && sidebar_module_is_active($mod, $current_page);
        ?>
            <div class="sidebar-module-group<?php echo $isOpen ? ' open' : ''; ?>">
                <div class="sidebar-module-row">
                    <a href="<?php echo $mod['href']; ?>" class="sidebar-subsystem-link<?php echo ($mod['href'] === $current_page) ? ' active' : ''; ?>">
                        <span class="material-symbols-outlined sidebar-subsystem-link-icon"><?php echo $mod['icon']; ?></span>
                        <span class="truncate text-xs"><?php echo $mod['name']; ?></span>
                    </a>
                    <?php if ($hasChildren): ?>
                        <button type="button" class="sidebar-submenu-toggle" aria-label="Toggle submodules" aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>">
                            <span class="material-symbols-outlined sidebar-submenu-chevron">expand_more</span>
                        </button>
                    <?php endif; ?>
                </div>
                <?php if ($hasChildren): ?>
                    <div class="sidebar-submenu">
                        <?php foreach ($mod['children'] as $child): ?>
                            <a href="<?php echo $child['href']; ?>" class="sidebar-submenu-link<?php echo ($child['href'] === $current_page) ? ' active' : ''; ?>">
                                <span class="truncate text-xs"><?php echo $child['name']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-profile-footer">
        <div class="sidebar-profile-card">
            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xs">
                <?php echo strtoupper(substr($admin_user, 0, 2)); ?>
            </div>
            <div class="flex flex-col text-left min-w-0 flex-1">
                <span class="font-semibold text-xs text-slate-900 truncate"><?php echo htmlspecialchars($admin_user); ?></span>
                <span class="text-[10px] text-slate-500 truncate"><?php echo htmlspecialchars($user_role); ?></span>
            </div>
            <a href="logout.php" title="Sign Out" class="text-rose-600 hover:bg-rose-50 p-1.5 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-sm">logout</span>
            </a>
        </div>
    </div>
</aside>