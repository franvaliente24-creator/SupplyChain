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
            { type: 'low-stock', title: 'Low Stock Alert', message: '12 items below safety stock threshold', severity: 'high', items: ['SKU-001: Widget A (3 units)', 'SKU-045: Component B (5 units)', 'SKU-089: Part C (2 units)'] },
            { type: 'delayed-shipment', title: 'Delayed Shipments', message: '6 shipments at risk of delay', severity: 'medium', items: ['PO-1234: Supplier Alpha (2 days late)', 'PO-1256: Supplier Beta (1 day late)', 'PO-1289: Supplier Gamma (at risk)'] }
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
                    { id: 'zone-map', label: 'Zone Map', icon: 'grid_view', render: 'renderSWSWorkspace' },
                    { id: 'bin-lookup', label: 'Bin Lookup', icon: 'search', render: 'renderSWSBinLookup' },
                    { id: 'task-queues', label: 'Task Queues', icon: 'assignment', render: 'renderSWSTaskQueues' },
                    { id: 'cycle-count', label: 'Cycle Count', icon: 'fact_check', render: 'renderSWSCycleCount' }
                ],
                functions: [
                    { name: 'generateAssetQR', description: 'Generates a printable QR code for an asset/bin/locker.' },
                    { name: 'scanAssetQR', description: 'Looks up an asset by scanned QR code and returns its current zone/location.' },
                    { name: 'updateAssetZone', description: 'Moves an asset to a new zone category and logs the change.' },
                    { name: 'getWarehouseCapacity', description: 'Returns current utilization per zone for the capacity view.' }
                ]
            },
            {
                id: 'ims',
                name: 'Inventory Management System (IMS)',
                subnav: [
                    { id: 'item-master', label: 'Item Master', icon: 'inventory', render: 'renderIMSWorkspace' },
                    { id: 'stock-levels', label: 'Stock Levels', icon: 'bar_chart', render: 'renderIMSStockLevels' },
                    { id: 'utilization-overview', label: 'Utilization Overview', icon: 'pie_chart', render: 'renderIMSUtilizationOverview' },
                    { id: 'adjustments', label: 'Adjustments', icon: 'tune', render: 'renderIMSAdjustmentWorkflow' },
                    { id: 'asset-disposition', label: 'Asset Disposition', icon: 'sync_alt', render: 'renderIMSAvailable' }
                ],
                functions: [
                    { name: 'checkReorderAlerts', description: 'Returns items below reorder threshold for restocking.' },
                    { name: 'adjustStockCount', description: 'Updates stock quantity with reason and timestamp.' },
                    { name: 'trackLicenseRenewals', description: 'Tracks software license expiration dates and renewal schedules.' },
                    { name: 'getStockMovementHistory', description: 'Returns historical stock movement log for an SKU.' }
                ]
            },
            {
                id: 'psm',
                name: 'Procurement & Sourcing Management (PSM)',
                subnav: [
                    { id: 'requisitions', label: 'Requisitions', icon: 'description', render: 'renderPSMWorkspace' },
                    { id: 'rfqs', label: 'RFQs', icon: 'request_quote', render: 'renderPSMRFQ' },
                    { id: 'sourcing', label: 'Sourcing', icon: 'handshake', render: 'renderPSMSourcingPipeline' },
                    { id: 'spend', label: 'Spend', icon: 'payments', render: 'renderPSMSpendAnalytics' }
                ],
                functions: [
                    { name: 'checkBudgetAllocation', description: 'Checks if requested amount is within available budget.' },
                    { name: 'compareVendorQuotes', description: 'Compares price and lead time from multiple vendors.' },
                    { name: 'routeApprovalWorkflow', description: 'Routes requisition to appropriate approver based on amount.' },
                    { name: 'createRequisition', description: 'Creates a new requisition with pending status.' }
                ]
            },
            {
                id: 'svm',
                name: 'Supplier / Vendor Management (SVM)',
                subnav: [
                    { id: 'supplier-directory', label: 'Supplier Directory', icon: 'contacts', render: 'renderSVMWorkspace' },
                    { id: 'scorecards', label: 'Scorecards', icon: 'assessment', render: 'renderSVMScorecards' },
                    { id: 'compliance', label: 'Compliance', icon: 'verified_user', render: 'renderSVMCompliance' },
                    { id: 'onboarding', label: 'Onboarding', icon: 'person_add', render: 'renderSVMOnboarding' }
                ],
                functions: [
                    { name: 'calculateVendorScorecard', description: 'Calculates KPIs including lead time, defect rate, and overall score.' },
                    { name: 'getVendorContracts', description: 'Returns contract types and expiry status for a vendor.' },
                    { name: 'rateCourierPerformance', description: 'Rates courier services by on-time delivery and damage rate.' }
                ]
            },
            {
                id: 'pom',
                name: 'Purchase Order Management (POM)',
                subnav: [
                    { id: 'po-pipeline', label: 'PO Pipeline', icon: 'view_list', render: 'renderPOMWorkspace' },
                    { id: 'po-creation', label: 'PO Creation', icon: 'add_circle', render: 'renderPOMCreation' },
                    { id: 'grn', label: 'GRN', icon: 'inventory', render: 'renderPOMGRN' },
                    { id: 'overdue', label: 'Overdue', icon: 'warning', render: 'renderPOMOverdue' }
                ],
                functions: [
                    { name: 'createPurchaseOrder', description: 'Creates a new purchase order with draft status.' },
                    { name: 'getPOStatusPipeline', description: 'Returns POs grouped by status (draft, approved, sent, etc.).' },
                    { name: 'matchThreeWay', description: 'Compares PO, goods receipt, and invoice for payment matching.' },
                    { name: 'flagOverduePOs', description: 'Returns POs past expected delivery date.' }
                ]
            },
            {
                id: 'dtrs',
                name: 'Document Tracking & Logistics Records System (DTRS)',
                subnav: [
                    { id: 'shipment-tracking', label: 'Shipment Tracking', icon: 'local_shipping', render: 'renderDTRSWorkspace' },
                    { id: 'documents', label: 'Documents', icon: 'folder', render: 'renderDTRSDocuments' },
                    { id: 'delivery', label: 'Delivery', icon: 'check_circle', render: 'renderDTRSDelivery' },
                    { id: 'exceptions', label: 'Exceptions', icon: 'error', render: 'renderDTRSExceptions' }
                ],
                functions: [
                    { name: 'generateWaybillQR', description: 'Generates QR-coded waybill for shipment tracking.' },
                    { name: 'logChainOfCustody', description: 'Logs chain of custody events with timestamp and actor.' },
                    { name: 'recordAccountabilitySignature', description: 'Records digital signature for accountability forms.' },
                    { name: 'trackAssetReturn', description: 'Logs asset return shipment and status.' }
                ]
            }
        ],
        activity: [
            { label: 'New shipment logged', time: '3 hours ago', status: 'In transit', category: 'Shipments' },
            { label: 'Stock count completed', time: 'Today', status: 'Verified', category: 'Inventory' },
            { label: 'Vendor performance reviewed', time: 'Yesterday', status: 'Updated', category: 'Vendors' },
            { label: 'PO approval pending', time: '2 hours ago', status: 'Pending', category: 'Shipments' },
            { label: 'Inventory adjustment made', time: 'Today', status: 'Completed', category: 'Inventory' },
            { label: 'New supplier onboarded', time: 'Yesterday', status: 'New', category: 'Vendors' }
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
            ' dispatch new route',
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

function getSubsystemById(id) {
    return SUBSYSTEMS.find(subsytem => subsytem.id === id);
}

function getSubsystemFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('subsystem');
}

function getModuleFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('module');
}

function normalizeModule(module) {
    if (typeof module === 'string') {
        const id = module.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        return { id, name: module, description: '', features: [] };
    }
    // Preserve subnav property if it exists
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
    // All modules now use the shared module.html template
    return `module.html?subsystem=${encodeURIComponent(subsystemId)}&module=${encodeURIComponent(moduleId)}`;
}

function getDashboardHref(subsystemId) {
    return `dashboard.html?subsystem=${encodeURIComponent(subsystemId)}`;
}
const MODULE_PAGE_OVERRIDES = {
    'supply-chain:svm': 'suppliers.php',
    'supply-chain:pom': 'orders.php',
    'supply-chain:dtrs': 'dtrs.php'
};

function getModuleHref(subsystemId, moduleId) {
    const overrideKey = `${subsystemId}:${moduleId}`;
    if (MODULE_PAGE_OVERRIDES[overrideKey]) {
        return MODULE_PAGE_OVERRIDES[overrideKey];
    }
    return `module.html?subsystem=${encodeURIComponent(subsystemId)}&module=${encodeURIComponent(moduleId)}`;
}