<?php
// sidebar.php - Synchronized Complete Navigation Component
$current_page = basename($_SERVER['PHP_SELF']);

$nav_groups = [
    [
        "name" => "Smart Warehousing (SWS)",
        "icon" => "warehouse",
        "href" => "warehouse.php",
        "children" => [
            ["name" => "Warehouse Overview", "icon" => "warehouse", "href" => "warehouse.php"],
            ["name" => "Zone Map", "icon" => "grid_view", "href" => "zone_map.php"],
            ["name" => "Bin Location Lookup", "icon" => "search", "href" => "bin_lookup.php"],
            ["name" => "Task Queues", "icon" => "assignment", "href" => "task_queues.php"],
            ["name" => "Cycle Count & Audit", "icon" => "checklist", "href" => "cycle_count.php"],
            ["name" => "Tech Assets & Hardware", "icon" => "devices", "href" => "tech_assets.php"],
            ["name" => "Equipment Matching", "icon" => "assignment_ind", "href" => "asset_assignments.php"],
        ]
    ],
    [
        "name" => "Inventory Management (IMS)",
        "icon" => "inventory_2",
        "href" => "item_master.php",
        "children" => [
            ["name" => "Item Master Directory", "icon" => "inventory_2", "href" => "item_master.php"],
            ["name" => "Stock Level Tracker", "icon" => "bar_chart", "href" => "stock_levels.php"],
            ["name" => "Stock Requisitions", "icon" => "swap_horiz", "href" => "stock_requisitions.php"],
            ["name" => "Utilization Overview", "icon" => "pie_chart", "href" => "utilization_overview.php"],
            ["name" => "Stock Adjustments", "icon" => "tune", "href" => "adjustments.php"],
            ["name" => "Asset Disposition", "icon" => "delete_sweep", "href" => "asset_disposition.php"],
        ]
    ],
    [
        "name" => "Procurement & Sourcing (PSM)",
        "icon" => "shopping_bag",
        "href" => "requisitions.php",
        "children" => [
            ["name" => "Material Requisitions", "icon" => "description", "href" => "requisitions.php"],
            ["name" => "RFQ Management", "icon" => "request_quote", "href" => "rfqs.php"],
            ["name" => "RFP Management", "icon" => "contract", "href" => "rfp_management.php"],
            ["name" => "Supplier Sourcing", "icon" => "handshake", "href" => "sourcing.php"],
            ["name" => "Document Templates", "icon" => "file_copy", "href" => "procurement_templates.php"],
            ["name" => "Spend Analysis", "icon" => "payments", "href" => "spend.php"],
        ]
    ],
    [
        "name" => "Supplier / Vendor (SVM)",
        "icon" => "handshake",
        "href" => "suppliers.php",
        "children" => [
            ["name" => "Vendor Directory", "icon" => "contacts", "href" => "suppliers.php"],
            ["name" => "Performance Scorecard", "icon" => "star", "href" => "supplier_performance.php"],
            ["name" => "Contracts & Compliance", "icon" => "verified", "href" => "supplier_contracts.php"],
            ["name" => "Supplier Documents", "icon" => "folder", "href" => "supplier_documents.php"],
            ["name" => "Supplier Transactions", "icon" => "receipt", "href" => "supplier_transactions.php"],
            ["name" => "Supplier Reports", "icon" => "insights", "href" => "supplier_reports.php"],
        ]
    ],
    [
        "name" => "Purchase Order (POM)",
        "icon" => "receipt_long",
        "href" => "orders.php",
        "children" => [
            ["name" => "Active Purchase Orders", "icon" => "receipt_long", "href" => "orders.php"],
            ["name" => "PO Approvals", "icon" => "task_alt", "href" => "po_approvals.php"],
            ["name" => "Goods Receipt", "icon" => "inventory", "href" => "goods_receipt.php"],
            ["name" => "Order History", "icon" => "history", "href" => "order_history.php"],
            ["name" => "PO QR Scanner", "icon" => "qr_code_scanner", "href" => "po_scanner.php"],
        ]
    ],
    [
        "name" => "Logistics & Records (DTRS)",
        "icon" => "local_shipping",
        "href" => "dtrs.php",
        "children" => [
            ["name" => "Shipment Manifests", "icon" => "local_shipping", "href" => "dtrs.php"],
            ["name" => "Proof of Delivery (POD)", "icon" => "assignment_turned_in", "href" => "pod.php"],
            ["name" => "Document Repository", "icon" => "folder_open", "href" => "document_repository.php"],
            ["name" => "Track Documents", "icon" => "markunread_mailbox", "href" => "document_tracking.php"],
            ["name" => "Carrier / 3PL Directory", "icon" => "commute", "href" => "carriers.php"],
            ["name" => "Customs & Compliance", "icon" => "gavel", "href" => "customs_records.php"],
        ]
    ],
    [
        "name" => "Administration & Security",
        "icon" => "admin_panel_settings",
        "href" => "activity_log.php",
        "children" => [
            ["name" => "User Management", "icon" => "manage_accounts", "href" => "user_management.php"],
            ["name" => "Login History", "icon" => "shield", "href" => "login_history.php"],
            ["name" => "Activity Audit Trail", "icon" => "history", "href" => "activity_log.php"],
        ]
    ]
];
?>

<div id="sidebar-backdrop" class="fixed top-16 bottom-0 left-0 right-0 md:inset-0 bg-gray-900/50 backdrop-blur-md z-40 hidden md:hidden transition-opacity duration-300 opacity-0"></div>

<aside id="app-sidebar" class="sidebar w-72 bg-surface border-r border-slate-200 flex flex-col shrink-0 transition-all duration-300 relative overflow-visible h-screen">
    <div id="sidebar-resize-handle" class="absolute right-0 top-0 bottom-0 w-1 cursor-col-resize hover:bg-primary/20 z-40"></div>
    
    <nav class="flex-1 flex flex-col overflow-y-auto overflow-x-hidden p-3 space-y-1.5">
        <div class="sidebar-brand-section mb-2">
            <div class="sidebar-brand-card flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">inventory_2</span>
                </div>
                <div class="overflow-hidden">
                    <div class="sidebar-brand-title font-bold text-xs text-slate-900 truncate">Supply Chain</div>
                    <div class="sidebar-brand-subtitle text-[11px] text-slate-500 truncate">Management Console</div>
                </div>
            </div>
        </div>

        <a id="sidebar-dashboard-link" class="sidebar-main-link flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 font-medium text-xs transition <?php echo in_array($current_page, ['dashboard.html', 'dashboard.php'], true) ? 'bg-indigo-50 text-indigo-700 font-semibold' : ''; ?>" href="dashboard.html?subsystem=supply-chain">
            <span class="material-symbols-outlined text-indigo-600 text-[18px]">dashboard</span>
            <span>Dashboard</span>
        </a>

        <div class="sidebar-subsystem-modules space-y-1 pt-2">
            <?php foreach ($nav_groups as $group): 
                $child_hrefs = array_column($group['children'], 'href');
                $is_open = in_array($current_page, $child_hrefs, true) || ($current_page === $group['href']);
            ?>
                <div class="sidebar-module-group <?php echo $is_open ? 'open' : ''; ?>">
                    <button type="button" class="sidebar-module-toggle w-full flex items-center justify-between px-3 py-2 rounded-xl border border-transparent text-slate-700 hover:bg-slate-100 font-medium text-xs transition <?php echo $is_open ? 'bg-indigo-50 text-indigo-700 font-semibold' : ''; ?>">
                        <span class="flex items-center gap-2.5 truncate">
                            <span class="material-symbols-outlined text-[18px] text-indigo-600"><?php echo htmlspecialchars($group['icon']); ?></span>
                            <span class="truncate"><?php echo htmlspecialchars($group['name']); ?></span>
                        </span>
                        <span class="material-symbols-outlined sidebar-chevron text-[16px] text-slate-400 transition-transform duration-200 <?php echo $is_open ? 'rotate-180' : ''; ?>">expand_more</span>
                    </button>

                    <div class="sidebar-submenu pl-4 pr-1 py-1 space-y-1" style="<?php echo $is_open ? 'max-height: 500px; display: block;' : 'max-height: 0px; display: none;'; ?>">
                        <?php foreach ($group['children'] as $child): 
                            $is_active = ($current_page === $child['href']);
                        ?>
                            <a href="<?php echo htmlspecialchars($child['href']); ?>" 
                               class="sidebar-submenu-link flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs transition <?php echo $is_active ? 'bg-indigo-600 text-white font-semibold shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                                <span class="material-symbols-outlined sidebar-submenu-icon text-[16px] <?php echo $is_active ? 'text-white' : 'text-slate-400'; ?>"><?php echo htmlspecialchars($child['icon']); ?></span>
                                <span class="truncate"><?php echo htmlspecialchars($child['name']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.sidebar-module-toggle').forEach(btn => {
        btn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            const parentGroup = btn.closest('.sidebar-module-group');
            if (!parentGroup) return;

            const isOpen = parentGroup.classList.toggle('open');
            const chevron = btn.querySelector('.sidebar-chevron');
            const submenu = parentGroup.querySelector('.sidebar-submenu');
            
            if (submenu) {
                submenu.style.display = isOpen ? 'block' : 'none';
                submenu.style.maxHeight = isOpen ? '500px' : '0px';
                if (chevron) {
                    chevron.classList.toggle('rotate-180', isOpen);
                }
            }
        };
    });
});
</script>