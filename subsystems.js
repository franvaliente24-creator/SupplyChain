/**
 * ==========================================================================
 * Subsystems Registry & Module Definitions (subsystems.js)
 * Full ISMERS Enterprise Subsystem Matrix (All 10 Core Transactions)
 * ==========================================================================
 */

const SUBSYSTEMS = [
    {
        id: 'client-management',
        title: 'Client Management',
        category: 'Core Transaction 1',
        description: 'Client acquisition, recruitment, deployment, and assignment visibility for your business pipeline.',
        stats: [
            { label: 'Pipeline Value', value: '$4.8M', icon: 'insights', tone: 'positive' },
            { label: 'Open Requests', value: '87', icon: 'task_alt', tone: 'positive' },
            { label: 'Active Clients', value: '1,240', icon: 'group', tone: 'neutral' },
            { label: 'Fill Rate', value: '92%', icon: 'trending_up', tone: 'positive' }
        ],
        quickActions: [
            'Create new client profile',
            'Review open job orders',
            'Sync assignment roster',
            'Export recruitment report'
        ],
        modules: [
            'Client Management Subsystem',
            'Applicant Registration and Profiling System',
            'Recruitment and Selection Subsystem',
            'Job Order Management Subsystem',
            'Deployment and Assignment Subsystem'
        ],
        activity: [
            { label: 'New client intake completed', time: '2 hours ago', status: 'Success' },
            { label: 'Assignment roster synced', time: '5 hours ago', status: 'Pending' },
            { label: 'Recruitment pipeline updated', time: 'Yesterday', status: 'Updated' }
        ]
    },
    {
        id: 'hris',
        title: 'HRIS',
        category: 'Core Transaction 2',
        description: 'Employee information, attendance, leave, payroll, and performance management in one hub.',
        stats: [
            { label: 'Employees Managed', value: '5,320', icon: 'people', tone: 'neutral' },
            { label: 'Open Leave Requests', value: '38', icon: 'event_note', tone: 'caution' },
            { label: 'Payroll Runs', value: '8', icon: 'attach_money', tone: 'positive' },
            { label: 'Avg Review Score', value: '4.5/5', icon: 'star', tone: 'positive' }
        ],
        quickActions: [
            'Approve pending leaves',
            'Run payroll batch',
            'Review performance scores',
            'Update employee profiles'
        ],
        modules: [
            'Employee Information Management System (HRIS)',
            'Timekeeping and Attendance System',
            'Leave and Absence Management System',
            'Payroll and Compensation System',
            'Performance Management Subsystem'
        ],
        activity: [
            { label: 'Leave approvals pending', time: '1 hour ago', status: 'Pending' },
            { label: 'Payroll batch prepared', time: 'Today', status: 'Ready' },
            { label: 'Performance review scheduled', time: 'Yesterday', status: 'Scheduled' }
        ]
    },
    {
        id: 'employee-development',
        title: 'Employee Development',
        category: 'Core Transaction 3',
        description: 'Training, contracts, compliance, benefits, and separation workflows for workforce readiness.',
        stats: [
            { label: 'Training Plans', value: '24', icon: 'school', tone: 'neutral' },
            { label: 'Compliance Tasks', value: '12', icon: 'gavel', tone: 'caution' },
            { label: 'Benefits Claims', value: '46', icon: 'favorite', tone: 'positive' },
            { label: 'Exit Clearances', value: '7', icon: 'verified_user', tone: 'neutral' }
        ],
        quickActions: [
            'Review training enrollments',
            'Approve compliance documents',
            'Process benefits claims',
            'Initiate separation workflows'
        ],
        modules: [
            'Training and Development Subsystem',
            'Document and Contract Management System',
            'Government Contribution & Compliance Subsystem',
            'Benefits and Loans Management System',
            'Separation and Exit Clearance Subsystem'
        ],
        activity: [
            { label: 'New training module launched', time: '3 hours ago', status: 'Published' },
            { label: 'Compliance audit checklist completed', time: 'Today', status: 'Completed' },
            { label: 'Benefits claim processed', time: 'Yesterday', status: 'Approved' }
        ]
    },
    {
        id: 'governance-safety',
        title: 'Governance & Safety',
        category: 'Core Transaction 4',
        description: 'Compliance, safety, administration, analytics, and asset issuance tracker for enterprise oversight.',
        stats: [
            { label: 'Safety Incidents', value: '3', icon: 'security', tone: 'positive' },
            { label: 'Compliance Alerts', value: '16', icon: 'report', tone: 'caution' },
            { label: 'Assets Issued', value: '184', icon: 'inventory', tone: 'neutral' },
            { label: 'Admin Requests', value: '28', icon: 'admin_panel_settings', tone: 'neutral' }
        ],
        quickActions: [
            'Review compliance reports',
            'Authorize safety protocols',
            'Issue new assets',
            'Schedule audit review'
        ],
        modules: [
            'Health, Safety, and Welfare Subsystem',
            'Legal and Compliance Subsystem',
            'System Administration and Security Subsystem',
            'Reports, Analytics, and Dashboards System',
            'Asset and Equipment Issuance Tracker'
        ],
        activity: [
            { label: 'Safety report filed', time: '4 hours ago', status: 'New' },
            { label: 'Compliance review meeting set', time: 'Today', status: 'Scheduled' },
            { label: 'Asset issuance processed', time: 'Yesterday', status: 'Completed' }
        ]
    },
    {
        id: 'financial-management',
        title: 'Financial Management',
        category: 'Core Transaction 5',
        description: 'General ledger, payables, receivables, cash control, budgeting, and financial analytics.',
        stats: [
            { label: 'Total Transactions', value: '12,450', icon: 'receipt_long', tone: 'positive' },
            { label: 'Total Budget', value: '$2,450,000', icon: 'savings', tone: 'positive' },
            { label: 'Accounts Receivable', value: '$245,300', icon: 'payments', tone: 'positive' },
            { label: 'Accounts Payable', value: '$115,200', icon: 'account_balance', tone: 'caution' }
        ],
        quickActions: [
            'New Transaction',
            'Upload Invoice',
            'Generate Report',
            'Budget Planning',
            'Tax Filing'
        ],
        analytics: {
            overviewTitle: 'Cash Flow Overview',
            overviewMetric: '$245,300',
            overviewSubtitle: 'Cash inflows, outflows, and liquidity across the month.',
            overviewTrend: 'This Month',
            overviewData: [
                { label: 'May 1', value: 35 },
                { label: 'May 7', value: 45 },
                { label: 'May 14', value: 65 },
                { label: 'May 21', value: 75 },
                { label: 'May 28', value: 90 }
            ],
            breakdownTitle: 'Expense Breakdown',
            breakdownTotal: 'Total $245,300',
            breakdownSegments: [
                { label: 'Operations', value: '40%', color: '#4f46e5' },
                { label: 'Payroll', value: '25%', color: '#34d399' },
                { label: 'Procurement', value: '15%', color: '#facc15' },
                { label: 'Debt', value: '10%', color: '#fb7185' },
                { label: 'Others', value: '10%', color: '#a78bfa' }
            ]
        },
        modules: [
            'General Ledger',
            'Accounts Payable (AP)',
            'Accounts Receivable (AR)',
            'Disbursement Management',
            'Collection Management',
            'Budget Management',
            'Cash Management',
            'Financial Reporting & Analytics',
            'Tax Management'
        ],
        activity: [
            { label: 'Quarterly report generated', time: '1 hour ago', status: 'Ready' },
            { label: 'Invoice approvals pending', time: 'Today', status: 'Pending' },
            { label: 'Cash forecast updated', time: 'Yesterday', status: 'Updated' }
        ]
    },
    {
        id: 'supply-chain',
        title: 'Supply Chain',
        category: 'Supply Chain & Inventory',
        description: 'Warehouse, procurement, inventory, vendor, order, and logistics tracking in a single supply chain system.',
        stats: [
            { label: 'Stock Accuracy', value: '98.2%', icon: 'inventory_2', tone: 'positive', trend: [95, 96, 97, 97, 98, 98.2] },
            { label: 'Open POs', value: '72', icon: 'shopping_cart', tone: 'neutral', trend: [65, 68, 70, 71, 73, 72] },
            { label: 'Delivery On-Time', value: '91%', icon: 'local_shipping', tone: 'positive', trend: [88, 89, 90, 90, 91, 91] },
            { label: 'Vendor Score', value: '4.6/5', icon: 'thumb_up', tone: 'positive', trend: [4.2, 4.3, 4.4, 4.5, 4.5, 4.6] }
        ],
        criticalAlerts: [
            { type: 'low-stock', title: 'Low Stock Alert', message: '12 items below safety stock threshold', severity: 'high', items: ['SYS-1042: Barcode Scanner Unit (8 pcs)', 'SYS-1077: Packing Tape Roll (0 box)'] },
            { type: 'delayed-shipment', title: 'Delayed Shipments', message: '2 shipments at risk of delay', severity: 'medium', items: ['MNF-2026-021: Batangas Hub Logistics', 'MNF-2026-022: Metro Manila Supply Co.'] }
        ],
        quickActions: [
            'Approve purchase orders',
            'Review supplier ratings',
            'Update inventory counts',
            'Track outstanding shipments'
        ],
        modules: [
            {
                id: 'sws',
                name: 'Smart Warehousing System (SWS)',
                subnav: [
                    { id: 'tech-assets', label: 'Tech Assets', icon: 'inventory', href: 'tech_assets.php' },
                    { id: 'asset-assignments', label: 'Equipment Matching', icon: 'person_add', href: 'asset_assignments.php' }
                ]
            },
            {
                id: 'ims',
                name: 'Inventory Management System (IMS)',
                subnav: [
                    { id: 'inventory-items', label: 'Inventory Items', icon: 'inventory_2', href: 'inventory.php' },
                    { id: 'inventory-dashboard', label: 'Inventory Analytics', icon: 'insights', href: 'inventory_dashboard.php' },
                    { id: 'qr-scanner', label: 'QR Scanner', icon: 'qr_code_scanner', href: 'qr_scanner.php' },
                    { id: 'stock-requisitions', label: 'Stock Requisitions', icon: 'assignment', href: 'stock_requisitions.php' }
                ]
            },
            {
                id: 'psm',
                name: 'Procurement & Sourcing Management (PSM)',
                subnav: [
                    { id: 'rfp-management', label: 'RFP Management', icon: 'request_quote', href: 'rfp_management.php' },
                    { id: 'procurement-templates', label: 'Procurement Templates', icon: 'folder_special', href: 'procurement_templates.php' }
                ]
            },
            {
                id: 'svm',
                name: 'Supplier / Vendor Management (SVM)',
                subnav: [
                    { id: 'suppliers-list', label: 'Supplier Directory', icon: 'handshake', href: 'suppliers.php' },
                    { id: 'supplier-performance', label: 'Performance & Ratings', icon: 'star', href: 'supplier_performance.php' },
                    { id: 'supplier-contracts', label: 'Contracts & SLA', icon: 'description', href: 'supplier_contracts.php' },
                    { id: 'supplier-docs', label: 'Supplier Documents', icon: 'folder', href: 'supplier_documents.php' },
                    { id: 'supplier-reports', label: 'Supplier Reports', icon: 'bar_chart', href: 'supplier_reports.php' }
                ]
            },
            {
                id: 'pom',
                name: 'Purchase Order Management (POM)',
                subnav: [
                    { id: 'po-list', label: 'Purchase Orders', icon: 'receipt_long', href: 'orders.php' },
                    { id: 'po-approvals', label: 'PO Approvals', icon: 'task_alt', href: 'po_approvals.php' },
                    { id: 'goods-receipt', label: 'Goods Receipt', icon: 'inventory', href: 'goods_receipt.php' },
                    { id: 'po-scanner', label: 'PO QR Scanner', icon: 'qr_code_scanner', href: 'po_scanner.php' }
                ]
            },
            {
                id: 'dtrs',
                name: 'Document Tracking & Logistics Records System (DTRS)',
                subnav: [
                    { id: 'manifests', label: 'Shipping Manifests', icon: 'local_shipping', href: 'dtrs.php' },
                    { id: 'pod', label: 'Delivery Confirmation (POD)', icon: 'assignment_turned_in', href: 'pod.php' },
                    { id: 'doc-repo', label: 'Document Repository', icon: 'folder_open', href: 'document_repository.php' },
                    { id: 'doc-tracking', label: 'Track Documents', icon: 'track_changes', href: 'document_tracking.php' },
                    { id: 'carriers', label: 'Carrier Directory', icon: 'directions_bus', href: 'carriers.php' },
                    { id: 'customs', label: 'Customs & Compliance', icon: 'gavel', href: 'customs_records.php' }
                ]
            }
        ],
        activity: [
            { label: 'Purchase Order PO-2026-089 approved by Procurement', time: '10 mins ago', status: 'Approved', category: 'Shipments' },
            { label: 'Stock count verified in Zone A', time: 'Today', status: 'Verified', category: 'Inventory' },
            { label: 'Vendor performance reviewed for Batangas Hub', time: 'Yesterday', status: 'Updated', category: 'Vendors' },
            { label: 'Customs record logged for Manifest #MNF-2026-021', time: '2 hours ago', status: 'Pending', category: 'Shipments' },
            { label: 'New inventory item added: Steel Pallet Rack', time: 'Today', status: 'Completed', category: 'Inventory' },
            { label: 'New carrier registered: Metro Manila Express', time: 'Yesterday', status: 'New', category: 'Vendors' }
        ]
    },
    {
        id: 'fleet-management',
        title: 'Fleet Management',
        category: 'Fleet & Transportation',
        description: 'Vehicle, driver, route, fuel, and dispatch management for optimized transportation operations.',
        stats: [
            { label: 'Vehicles Active', value: '64', icon: 'directions_car', tone: 'neutral' },
            { label: 'Routes Scheduled', value: '92', icon: 'map', tone: 'positive' },
            { label: 'Fuel Efficiency', value: '18.4 km/l', icon: 'local_gas_station', tone: 'neutral' },
            { label: 'On-time Deliveries', value: '97%', icon: 'schedule', tone: 'positive' }
        ],
        quickActions: [
            'Dispatch new route',
            'Assign driver shifts',
            'Log fuel consumption',
            'Review vehicle service history'
        ],
        modules: [
            'Fleet & Vehicle Management (FVM)',
            'Vehicle Reservation & Dispatch System (VRDS)',
            'Driver and Trip Performance Monitoring',
            'Fuel Management System',
            'Transport Cost Analysis & Optimization (TCAO)',
            'Route Planning & Optimization',
            'Mobile Fleet Command App'
        ],
        activity: [
            { label: 'Route plan finalized', time: '2 hours ago', status: 'Scheduled' },
            { label: 'Fuel order approved', time: 'Today', status: 'Approved' },
            { label: 'Maintenance check complete', time: 'Yesterday', status: 'Completed' }
        ]
    },
    {
        id: 'facilities',
        title: 'Facilities Management',
        category: 'Facilities & Administrative Management',
        description: 'Facility reservations, visitor management, document archiving, legal, and contract control for property operations.',
        stats: [
            { label: 'Facilities Booked', value: '18', icon: 'meeting_room', tone: 'neutral' },
            { label: 'Visitor Logs', value: '1,134', icon: 'badge', tone: 'neutral' },
            { label: 'Open Cases', value: '9', icon: 'folder_open', tone: 'caution' },
            { label: 'Contract Renewals', value: '6', icon: 'description', tone: 'positive' }
        ],
        quickActions: [
            'Approve room reservations',
            'Verify visitor access',
            'Archive documents',
            'Review contract expirations'
        ],
        modules: [
            'Facilities Reservation System',
            'Visitor Management System',
            'Document Management (Archiving System)',
            'Records Retention & Compliance',
            'Legal Management System',
            'Contract Management'
        ],
        activity: [
            { label: 'Visitor access granted', time: '1 hour ago', status: 'Confirmed' },
            { label: 'Contract review queued', time: 'Today', status: 'Queued' },
            { label: 'Archives synced', time: 'Yesterday', status: 'Completed' }
        ]
    },
    {
        id: 'business-intelligence',
        title: 'Business Intelligence',
        category: 'Business Intelligence & Analytics',
        description: 'Dashboards, KPIs, predictive analytics, reporting, and data integration for decision support.',
        stats: [
            { label: 'Dashboards Live', value: '14', icon: 'dashboard', tone: 'positive' },
            { label: 'KPIs Tracking', value: '62', icon: 'insights', tone: 'positive' },
            { label: 'New Reports', value: '18', icon: 'description', tone: 'neutral' },
            { label: 'Data Sources', value: '9', icon: 'storage', tone: 'neutral' }
        ],
        quickActions: [
            'Create new KPI report',
            'Refresh analytics feed',
            'Export dashboard snapshot',
            'Connect a new data source'
        ],
        modules: [
            'Dashboard & Data Visualization System',
            'KPI Monitoring & Performance Tracking System',
            'Predictive Analytics System',
            'Custom Report Generation System',
            'Data Aggregation & Integration System',
            'Exportable Reports & Decision Support System'
        ],
        activity: [
            { label: 'Analytics feed refreshed', time: '2 hours ago', status: 'Completed' },
            { label: 'New KPI dashboard published', time: 'Today', status: 'Published' },
            { label: 'Data connector validated', time: 'Yesterday', status: 'Validated' }
        ]
    },
    {
        id: 'crm-sales',
        title: 'CRM & Sales',
        category: 'Customer Relationship Management (CRM)',
        description: 'Client, lead, opportunities, communication, and pipeline management to grow customer relationships.',
        stats: [
            { label: 'Active Leads', value: '1,830', icon: 'leaderboard', tone: 'positive' },
            { label: 'Conversion Rate', value: '16.8%', icon: 'trending_up', tone: 'positive' },
            { label: 'Open Deals', value: '42', icon: 'handshake', tone: 'neutral' },
            { label: 'Follow-ups Due', value: '97', icon: 'notifications', tone: 'caution' }
        ],
        quickActions: [
            'Create new opportunity',
            'Update lead status',
            'Schedule follow-up call',
            'Send outreach campaign'
        ],
        modules: [
            'Lead and Client Tracking System',
            'Communication History Management',
            'Client Satisfaction and Survey System',
            'Follow-up Reminder System',
            'Opportunity Pipeline Visualization'
        ],
        activity: [
            { label: 'Lead outreach launched', time: '1 hour ago', status: 'In progress' },
            { label: 'Opportunity pipeline adjusted', time: 'Today', status: 'Updated' },
            { label: 'Client survey results ready', time: 'Yesterday', status: 'Ready' }
        ]
    }
];

// Helper Functions
function getSubsystemById(id) {
    const targetId = id || 'supply-chain';
    return SUBSYSTEMS.find(subsystem => subsystem.id === targetId) || SUBSYSTEMS.find(s => s.id === 'supply-chain');
}

function getSubsystemFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('subsystem') || 'supply-chain';
}

function getModuleFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('module');
}

function normalizeModule(module) {
    if (typeof module === 'string') {
        const id = module.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        return { id, name: module, description: '', subnav: [] };
    }
    return module;
}

function getModuleById(subsystemId, moduleId) {
    const subsystem = getSubsystemById(subsystemId);
    if (!subsystem || !subsystem.modules) return null;
    return subsystem.modules
        .map(normalizeModule)
        .find(module => module.id === moduleId) || null;
}

function getModuleHref(subsystemId, moduleId) {
    return `module.html?subsystem=${encodeURIComponent(subsystemId)}&module=${encodeURIComponent(moduleId)}`;
}

function getDashboardHref(subsystemId) {
    return `dashboard.html?subsystem=${encodeURIComponent(subsystemId || 'supply-chain')}`;
}

/**
 * subsystems.js — Central Subsystem & Navigation Definition Registry
 */

const subsystems = [
  {
    id: 'supply-chain',
    title: 'Supply Chain & Inventory Management System',
    category: 'Logistics & Supply Operations',
    description: 'Centralized warehouse, inventory control, procurement, supplier registry, and logistics dispatch.',
    stats: [
      { label: 'Active SKUs', value: '1,420', icon: 'inventory_2', tone: 'positive', trend: [65, 70, 75, 80, 85, 90, 95] },
      { label: 'Pending Requisitions', value: '18', icon: 'assignment', tone: 'caution', trend: [30, 28, 25, 22, 20, 19, 18] },
      { label: 'Storage Occupancy', value: '78%', icon: 'warehouse', tone: 'positive', trend: [60, 64, 68, 70, 72, 75, 78] },
      { label: 'Active Shipments', value: '24', icon: 'local_shipping', tone: 'neutral', trend: [15, 18, 20, 22, 21, 23, 24] }
    ],
    quickActions: [
      'Approve purchase orders',
      'Update inventory counts',
      'Track outstanding shipments',
      'Review supplier ratings'
    ],
    activity: [
      { label: 'Zone A visual audit completed', status: 'Completed', time: '10 mins ago', category: 'Inventory' },
      { label: 'PO-2026-089 approved for dispatch', status: 'Updated', time: '45 mins ago', category: 'Shipments' },
      { label: 'New requisition REQ-2026-015 filed', status: 'Pending', time: '2 hours ago', category: 'Vendors' }
    ],
    analytics: {
      overviewTitle: 'Logistics & Stock Performance',
      overviewMetric: '94.2% In-Stock Rate',
      overviewSubtitle: 'Aggregate operational throughput across all warehouse zones and active dispatch routes.',
      overviewTrend: 'Last 30 Days',
      overviewData: [
        { label: 'Zone A', value: 85 },
        { label: 'Zone B', value: 65 },
        { label: 'Zone C', value: 40 },
        { label: 'Zone D', value: 90 }
      ],
      overviewHighlights: [
        { label: 'Fulfilled Requisitions', value: '96.8%' },
        { label: 'Inventory Turnover', value: '4.2x' }
      ],
      breakdownTitle: 'Warehouse Space Utilization',
      breakdownTotal: '4,000 Units',
      breakdownSegments: [
        { label: 'Zone A (Bulk)', value: '35%', color: '#4f46e5' },
        { label: 'Zone B (Racks)', value: '28%', color: '#06b6d4' },
        { label: 'Zone C (Bins)', value: '22%', color: '#f59e0b' },
        { label: 'Zone D (Staging)', value: '15%', color: '#10b981' }
      ]
    },
    criticalAlerts: [
      {
        severity: 'high',
        type: 'low-stock',
        title: 'Critical Stock Threshold',
        message: 'Items operating below configured safety stock levels.',
        items: ['SYS-1077 (Heavy Duty Packing Tape 50m)', 'SYS-1042 (Wireless Barcode Scanner Unit)']
      }
    ],
    modules: [
      // 1. Smart Warehousing System (SWS)
      {
        id: 'smart-warehousing-system',
        name: 'Smart Warehousing System (SWS)',
        subnav: [
          { id: 'zone-map', label: 'Zone Map', icon: 'grid_view', href: 'zone_map.php' },
          { id: 'bin-lookup', label: 'Bin Lookup', icon: 'search', href: 'bin_lookup.php' },
          { id: 'task-queues', label: 'Task Queues', icon: 'assignment', href: 'task_queues.php' },
          { id: 'cycle-count', label: 'Cycle Count', icon: 'checklist', href: 'cycle_count.php' }
        ]
      },

      // 2. Inventory Management System (IMS)
      {
        id: 'inventory-management-system',
        name: 'Inventory Management System (IMS)',
        subnav: [
          { id: 'item-master', label: 'Item Master', icon: 'inventory_2', href: 'item_master.php' },
          { id: 'stock-levels', label: 'Stock Levels', icon: 'bar_chart', href: 'stock_levels.php' },
          { id: 'utilization-overview', label: 'Utilization Overview', icon: 'pie_chart', href: 'utilization_overview.php' },
          { id: 'adjustments', label: 'Adjustments', icon: 'tune', href: 'adjustments.php' },
          { id: 'asset-disposition', label: 'Asset Disposition', icon: 'swap_horiz', href: 'asset_disposition.php' }
        ]
      },

      // 3. Procurement & Sourcing Management (PSM)
      {
        id: 'procurement-sourcing-management',
        name: 'Procurement & Sourcing Management (PSM)',
        subnav: [
          { id: 'requisitions', label: 'Requisitions', icon: 'description', href: 'requisitions.php' },
          { id: 'rfqs', label: 'RFQs', icon: 'request_quote', href: 'rfqs.php' },
          { id: 'sourcing', label: 'Sourcing', icon: 'handshake', href: 'sourcing.php' },
          { id: 'spend', label: 'Spend', icon: 'payments', href: 'spend.php' }
        ]
      },

      // 4. Supplier / Vendor Management (SVM)
      {
        id: 'supplier-vendor-management',
        name: 'Supplier / Vendor Management (SVM)',
        subnav: [
          { id: 'carriers', label: 'Carrier Directory', icon: 'local_shipping', href: 'carriers.php' }
        ]
      },

      // 5. Purchase Order Management (POM)
      {
        id: 'purchase-order-management',
        name: 'Purchase Order Management (POM)',
        subnav: [
          { id: 'goods-receipt', label: 'Goods Receipt', icon: 'fact_check', href: 'goods_receipt.php' }
        ]
      },

      // 6. Document Tracking & Logistics Records System (DTRS)
      {
        id: 'document-tracking-logistics-records',
        name: 'Document Tracking & Logistics Records System (DTRS)',
        subnav: [
          { id: 'manifests', label: 'Shipping Manifests', icon: 'local_shipping', href: 'dtrs.php' },
          { id: 'documents', label: 'Document Repository', icon: 'folder', href: 'document_repository.php' },
          { id: 'customs', label: 'Customs & Compliance', icon: 'gavel', href: 'customs_records.php' },
          { id: 'tracking', label: 'Document Tracking', icon: 'timeline', href: 'document_tracking.php' }
        ]
      }
    ]
  }
];

// Helper functions for dashboard & sidebar router
function getSubsystemFromUrl() {
  const params = new URLSearchParams(window.location.search);
  return params.get('subsystem') || 'supply-chain';
}

function getModuleFromUrl() {
  const params = new URLSearchParams(window.location.search);
  return params.get('module');
}

function getSubsystemById(id) {
  return subsystems.find(s => s.id === id) || subsystems[0];
}

function normalizeModule(module) {
  if (typeof module === 'string') {
    return { id: module.toLowerCase().replace(/[^a-z0-9]+/g, '-'), name: module, subnav: [] };
  }
  return module;
}

function getDashboardHref(subsystemId) {
  return `dashboard.html?subsystem=${encodeURIComponent(subsystemId)}`;
}

function getModuleHref(subsystemId, moduleId) {
  return `module.html?subsystem=${encodeURIComponent(subsystemId)}&module=${encodeURIComponent(moduleId)}`;
}