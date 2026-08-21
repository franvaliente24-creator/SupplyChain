-- Enhanced Activity Log Table
CREATE TABLE IF NOT EXISTS activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50),
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add last_login column to users table if it doesn't exist
-- Note: Run this manually if needed, as MySQL doesn't support IF NOT EXISTS for columns directly
-- ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL;

-- Create login_history table for security monitoring
CREATE TABLE IF NOT EXISTS login_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_status ENUM('success', 'failed') NOT NULL,
    failure_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_login_status (login_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Smart Warehousing System (SWS) Tables
CREATE TABLE IF NOT EXISTS tech_assets (
    asset_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(255) NOT NULL,
    asset_type VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) UNIQUE,
    qr_code VARCHAR(255) UNIQUE,
    brand VARCHAR(100),
    model VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    condition_status ENUM('Brand New', 'Good', 'Fair', 'Defective', 'Repaired') DEFAULT 'Brand New',
    current_status ENUM('In Storage', 'Deployed', 'In Transit', 'Maintenance', 'Retired') DEFAULT 'In Storage',
    assigned_to VARCHAR(255),
    client_name VARCHAR(255),
    zone_location VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX asset_qr_code (qr_code),
    INDEX asset_status (current_status),
    INDEX asset_type (asset_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    candidate_name VARCHAR(255) NOT NULL,
    client_name VARCHAR(255),
    assigned_date DATE NOT NULL,
    expected_return_date DATE,
    actual_return_date DATE,
    return_condition ENUM('Brand New', 'Good', 'Fair', 'Defective', 'Repaired'),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES tech_assets(asset_id) ON DELETE CASCADE,
    INDEX idx_asset_id (asset_id),
    INDEX idx_candidate_name (candidate_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_condition_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    old_condition VARCHAR(50),
    new_condition VARCHAR(50) NOT NULL,
    changed_by INT,
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES tech_assets(asset_id) ON DELETE CASCADE,
    INDEX idx_asset_id (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory Management System (IMS) Tables
CREATE TABLE IF NOT EXISTS stock_requisitions (
    requisition_id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_number VARCHAR(50) UNIQUE NOT NULL,
    requested_by INT NOT NULL,
    department VARCHAR(100),
    item_id INT,
    quantity_requested INT NOT NULL,
    quantity_approved INT,
    request_date DATE NOT NULL,
    approval_status ENUM('Pending', 'Approved', 'Rejected', 'Partially Fulfilled') DEFAULT 'Pending',
    approved_by INT,
    approval_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requested_by) REFERENCES users(user_id),
    FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
    INDEX idx_requisition_number (requisition_number),
    INDEX idx_approval_status (approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type ENUM('Stock In', 'Stock Out', 'Adjustment', 'Transfer', 'Requisition') NOT NULL,
    quantity_change INT NOT NULL,
    previous_quantity INT,
    new_quantity INT,
    reference_number VARCHAR(100),
    performed_by INT,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
    INDEX idx_item_id (item_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Procurement & Sourcing Management (PSM) Tables
CREATE TABLE IF NOT EXISTS rfps (
    rfp_id INT AUTO_INCREMENT PRIMARY KEY,
    rfp_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    budget_limit DECIMAL(15,2),
    deadline DATE,
    created_by INT NOT NULL,
    status ENUM('Draft', 'Published', 'Closed', 'Awarded') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_rfp_number (rfp_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rfp_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    rfp_id INT NOT NULL,
    supplier_id INT NOT NULL,
    quote_amount DECIMAL(15,2),
    proposal_document TEXT,
    submitted_date DATE,
    status ENUM('Submitted', 'Under Review', 'Accepted', 'Rejected') DEFAULT 'Submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rfp_id) REFERENCES rfps(rfp_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    INDEX idx_rfp_id (rfp_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS procurement_templates (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    default_budget DECIMAL(15,2),
    required_approvals JSON,
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_frequency ENUM('Monthly', 'Quarterly', 'Annually'),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Supplier / Vendor Management (SVM) Tables
CREATE TABLE IF NOT EXISTS supplier_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500),
    upload_date DATE NOT NULL,
    expiry_date DATE,
    status ENUM('Active', 'Expired', 'Pending Renewal') DEFAULT 'Active',
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    INDEX idx_supplier_id (supplier_id),
    INDEX idx_document_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS supplier_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    transaction_type ENUM('Quote', 'Purchase Order', 'Delivery', 'Payment', 'Return') NOT NULL,
    reference_number VARCHAR(100),
    amount DECIMAL(15,2),
    transaction_date DATE NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    INDEX idx_supplier_id (supplier_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_date (transaction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchase Order Management (POM) Tables
CREATE TABLE IF NOT EXISTS po_qr_codes (
    qr_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT,
    qr_code VARCHAR(255) UNIQUE NOT NULL,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scanned BOOLEAN DEFAULT FALSE,
    scanned_date TIMESTAMP NULL,
    scanned_by INT,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
    INDEX idx_qr_code (qr_code),
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS goods_receipt_logs (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT,
    expected_quantity INT NOT NULL,
    received_quantity INT NOT NULL,
    discrepancy_quantity INT DEFAULT 0,
    discrepancy_reason TEXT,
    received_by INT NOT NULL,
    received_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    location_suggested VARCHAR(50),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory_items(item_id),
    INDEX idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Document Tracking & Logistics Record System (DTRS) Tables
CREATE TABLE IF NOT EXISTS document_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_number VARCHAR(50) UNIQUE NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    recipient_name VARCHAR(255) NOT NULL,
    recipient_address TEXT,
    current_status ENUM('Created', 'Out for Delivery', 'In Transit', 'Delivered', 'Failed', 'Delayed') DEFAULT 'Created',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    notes TEXT,
    INDEX idx_tracking_number (tracking_number),
    INDEX idx_current_status (current_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS delivery_events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_id INT NOT NULL,
    event_type ENUM('Created', 'Picked Up', 'In Transit', 'Out for Delivery', 'Delivered', 'Failed', 'Delayed') NOT NULL,
    event_location VARCHAR(255),
    event_notes TEXT,
    event_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT,
    FOREIGN KEY (tracking_id) REFERENCES document_tracking(tracking_id) ON DELETE CASCADE,
    INDEX idx_tracking_id (tracking_id),
    INDEX idx_event_timestamp (event_timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS document_archive (
    archive_id INT AUTO_INCREMENT PRIMARY KEY,
    original_tracking_id INT,
    tracking_number VARCHAR(50),
    document_type VARCHAR(100),
    recipient_name VARCHAR(255),
    final_status VARCHAR(50),
    archived_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT,
    retention_until DATE,
    INDEX idx_tracking_number (tracking_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;