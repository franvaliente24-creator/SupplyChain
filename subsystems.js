/**
 * subsystems.js - Centralized Module & Navigation Registry
 */

const subsystemsData = {
  'supply-chain': {
    id: 'supply-chain',
    title: 'Supply Chain Management System',
    category: 'Logistics & Inventory Operations',
    description: 'Centralized control system for warehousing, inventory lifecycle, sourcing, purchasing, and cargo tracking.',
    stats: [
      { label: 'Active Shipments', value: '38', icon: 'local_shipping', tone: 'positive', trend: [20, 24, 28, 35, 38] },
      { label: 'Warehouse Bins', value: '142', icon: 'shelves', tone: 'neutral', trend: [130, 135, 140, 142] },
      { label: 'Pending Receipts', value: '12', icon: 'receipt_long', tone: 'caution', trend: [18, 15, 14, 12] },
      { label: 'Inventory Audits', value: '98.4%', icon: 'checklist', tone: 'positive', trend: [95, 96, 97, 98.4] }
    ],
    quickActions: [
      'Approve purchase orders',
      'Review supplier ratings',
      'Update inventory counts',
      'Track outstanding shipments'
    ],
    criticalAlerts: [
      {
        severity: 'medium',
        type: 'low-stock',
        title: 'Low Stock Discrepancies',
        message: 'Variance flagged in recent cycle counts.',
        items: ['BIN-A1-04 (Safety Helmets)', 'BIN-B2-08 (Laptops)']
      }
    ],
    modules: [
      // 1. Smart Warehousing System (SWS)
      {
        id: 'smart-warehousing-system',
        name: 'Smart Warehousing System (SWS)',
        icon: 'warehouse',
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
        icon: 'inventory_2',
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
        icon: 'shopping_bag',
        subnav: [
          { id: 'requisitions', label: 'Requisitions', icon: 'description', href: 'requisitions.php' },
          { id: 'rfqs', label: 'RFQs', icon: 'request_quote', href: 'rfqs.php' },
          { id: 'sourcing', label: 'Sourcing', icon: 'handshake', href: 'sourcing.php' },
          { id: 'spend', label: 'Spend Analysis', icon: 'payments', href: 'spend.php' }
        ]
      },

      // 4. Supplier / Vendor Management (SVM)
      {
        id: 'supplier-vendor-management',
        name: 'Supplier / Vendor Management',
        icon: 'handshake',
        subnav: [
          { id: 'vendor-directory', label: 'Vendor Directory', icon: 'contacts', href: 'suppliers.php' },
          { id: 'performance-scorecard', label: 'Performance Scorecard', icon: 'star', href: 'supplier_performance.php' },
          { id: 'contracts-compliance', label: 'Contracts & Compliance', icon: 'verified', href: 'supplier_contracts.php' },
          { id: 'supplier-documents', label: 'Supplier Documents', icon: 'folder', href: 'supplier_documents.php' },
          { id: 'supplier-transactions', label: 'Supplier Transactions', icon: 'receipt', href: 'supplier_transactions.php' },
          { id: 'supplier-reports', label: 'Supplier Reports', icon: 'insights', href: 'supplier_reports.php' }
        ]
      },

      // 5. Purchase Order Management (POM)
      {
        id: 'purchase-order-management',
        name: 'Purchase Order Management',
        icon: 'receipt_long',
        subnav: [
          { id: 'active-pos', label: 'Active Purchase Orders', icon: 'receipt_long', href: 'orders.php' },
          { id: 'po-approvals', label: 'PO Approvals', icon: 'task_alt', href: 'po_approvals.php' },
          { id: 'goods-receipt', label: 'Goods Receipt', icon: 'inventory', href: 'goods_receipt.php' },
          { id: 'order-history', label: 'Order History', icon: 'history', href: 'order_history.php' },
          { id: 'po-scanner', label: 'PO QR Scanner', icon: 'qr_code_scanner', href: 'po_scanner.php' }
        ]
      },

      // 6. Document Tracking & Logistics Records System (DTRS)
      {
        id: 'document-tracking-logistics',
        name: 'Document Tracking & Logistics (DTRS)',
        icon: 'local_shipping',
        subnav: [
          { id: 'shipping-manifests', label: 'Shipment Manifests & Tracking', icon: 'local_shipping', href: 'dtrs.php' },
          { id: 'delivery-confirmation', label: 'Delivery Confirmation (POD)', icon: 'assignment_turned_in', href: 'pod.php' },
          { id: 'document-repository', label: 'Document Repository', icon: 'folder_open', href: 'document_repository.php' },
          { id: 'track-documents', label: 'Track Documents', icon: 'markunread_mailbox', href: 'document_tracking.php' },
          { id: 'carrier-directory', label: 'Carrier / 3PL Directory', icon: 'commute', href: 'carriers.php' },
          { id: 'customs-records', label: 'Customs & Compliance Records', icon: 'gavel', href: 'customs_records.php' }
        ]
      }
    ],
    analytics: {
      overviewTitle: 'Dispatch & Receipt Volume',
      overviewMetric: '1,420 Units',
      overviewSubtitle: 'Weekly inventory flow and receipt status',
      overviewTrend: 'This Month',
      overviewData: [
        { label: 'Mon', value: 40 },
        { label: 'Tue', value: 65 },
        { label: 'Wed', value: 50 },
        { label: 'Thu', value: 85 },
        { label: 'Fri', value: 92 },
        { label: 'Sat', value: 30 }
      ],
      overviewHighlights: [
        { label: 'Fulfillment Rate', value: '98.2%' },
        { label: 'Avg Process Time', value: '1.4 Days' }
      ],
      breakdownTitle: 'Storage Occupancy',
      breakdownTotal: '142 Bins',
      breakdownSegments: [
        { label: 'Occupied', value: '65%', color: '#4f46e5' },
        { label: 'Available', value: '25%', color: '#10b981' },
        { label: 'Reserved / Hold', value: '10%', color: '#f59e0b' }
      ]
    },
    activity: [
      { label: 'Manifest MNF-2026-001 Dispatched', status: 'In Transit', time: '10m ago', category: 'Shipments' },
      { label: 'Goods Receipt recorded for PO-8921', status: 'Completed', time: '1h ago', category: 'Inventory' },
      { label: 'Stock Adjustment: Water Damage write-off', status: 'Updated', time: '3h ago', category: 'Inventory' },
      { label: 'Metro Manila Express added to Directory', status: 'Ready', time: '1d ago', category: 'Vendors' }
    ]
  }
};

function getSubsystemFromUrl() {
  const params = new URLSearchParams(window.location.search);
  return params.get('subsystem') || 'supply-chain';
}

function getModuleFromUrl() {
  const params = new URLSearchParams(window.location.search);
  return params.get('module') || null;
}

function getSubsystemById(id) {
  return subsystemsData[id] || subsystemsData['supply-chain'];
}

function normalizeModule(mod) {
  return {
    id: mod.id || '',
    name: mod.name || '',
    icon: mod.icon || 'apps',
    subnav: mod.subnav || []
  };
}

function getDashboardHref(subsystemId) {
  return `dashboard.html?subsystem=${encodeURIComponent(subsystemId || 'supply-chain')}`;
}

function getModuleHref(subsystemId, moduleId) {
  return `module.html?subsystem=${encodeURIComponent(subsystemId || 'supply-chain')}&module=${encodeURIComponent(moduleId)}`;
}