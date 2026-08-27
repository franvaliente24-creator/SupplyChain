<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$admin_user = $_SESSION['username'] ?? ($admin_user ?? 'Admin User');
$user_role = $_SESSION['role'] ?? ($user_role ?? 'Supply Chain Manager');

$sub_modules = [
    [
        "name" => "Smart Warehousing System (SWS)",
        "icon" => "warehouse",
        "href" => "warehouse.php",
        "children" => [
            ["name" => "Tech Assets", "href" => "tech_assets.php"],
            ["name" => "Equipment Matching", "href" => "asset_assignments.php"],
        ]
    ],
    [
        "name" => "Inventory Management System (IMS)",
        "icon" => "inventory_2",
        "href" => "inventory.php",
        "children" => [
            ["name" => "Inventory Items", "href" => "inventory.php"],
            ["name" => "Inventory Analytics", "href" => "inventory_dashboard.php"],
            ["name" => "QR Scanner", "href" => "qr_scanner.php"],
            ["name" => "Stock Requisitions", "href" => "stock_requisitions.php"],
        ]
    ],
    [
        "name" => "Procurement & Sourcing Management (PSM)",
        "icon" => "shopping_bag",
        "href" => "psm.php",
        "children" => [
            ["name" => "RFP Management", "href" => "rfp_management.php"],
            ["name" => "Procurement Templates", "href" => "procurement_templates.php"],
        ]
    ],
    [
        "name" => "Supplier / Vendor Management",
        "icon" => "handshake",
        "href" => "suppliers.php",
        "children" => [
            ["name" => "Vendor Directory", "href" => "suppliers.php"],
            ["name" => "Performance Scorecard", "href" => "supplier_performance.php"],
            ["name" => "Contracts & Compliance", "href" => "supplier_contracts.php"],
            ["name" => "Supplier Documents", "href" => "supplier_documents.php"],
            ["name" => "Supplier Reports", "href" => "supplier_reports.php"],
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
            ["name" => "PO QR Scanner", "href" => "po_scanner.php"],
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
            ["name" => "Track Documents", "href" => "document_tracking.php"],
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
<div id="sidebar-backdrop" class="fixed top-16 bottom-0 left-0 right-0 md:inset-0 bg-gray-900/50 backdrop-blur-md z-40 hidden md:hidden transition-opacity duration-300 opacity-0"></div>
<aside id="app-sidebar" class="sidebar w-72 bg-surface border-r border-outline-variant/30 flex flex-col shrink-0 transition-all duration-300 relative overflow-visible">
    <div id="sidebar-resize-handle" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-primary/20 z-40"></div>
    <nav class="flex-1 flex flex-col overflow-y-auto overflow-x-hidden">
        <div class="sidebar-brand-section">
            <div class="sidebar-brand-card">
                <div class="sidebar-brand-icon w-10 h-10 rounded-xl flex items-center justify-center shrink-0 overflow-hidden">
                    <img src="img/logo.png" alt="Supply Chain Logo" class="w-full h-full object-cover"/>
                </div>
                <div class="sidebar-brand-title">Supply Chain</div>
                <div class="sidebar-brand-subtitle">Supply Chain &amp; Inventory</div>
            </div>
        </div>
        <a class="sidebar-main-link" href="dashboard.html?subsystem=supply-chain">
            <span class="sidebar-main-link-icon material-symbols-outlined">dashboard</span>
            <span class="font-label font-medium text-sm">Dashboard</span>
        </a>
        <div class="sidebar-subsystem-nav-panel">
            <nav class="sidebar-subsystem-modules">
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
        </div>
    </nav>
</aside>