<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

// QR Code Generator using Google Charts API (free, no dependencies)
function generateQRCode($data, $size = 150) {
    $googleChartAPI = "https://chart.googleapis.com/chart";
    $params = [
        'chs' => "{$size}x{$size}",
        'cht' => 'qr',
        'chl' => $data,
        'choe' => 'UTF-8'
    ];
    return $googleChartAPI . '?' . http_build_query($params);
}

// Generate QR code for tech asset
if (isset($_GET['asset_id']) && !empty($_GET['asset_id'])) {
    $asset_id = (int)$_GET['asset_id'];
    
    // Check if tech_assets table exists
    $table_exists = false;
    $check_table = $conn->query("SHOW TABLES LIKE 'tech_assets'");
    if ($check_table && $check_table->num_rows > 0) {
        $table_exists = true;
    }
    $check_table->free();
    
    if ($table_exists) {
        $stmt = $conn->prepare("SELECT asset_id, asset_name, qr_code FROM tech_assets WHERE asset_id = ?");
        $stmt->bind_param("i", $asset_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $asset = $result->fetch_assoc();
            $qr_data = $asset['qr_code'] ?: "ASSET-{$asset_id}";
            $qr_url = generateQRCode($qr_data, 200);
            
            // Update QR code if it doesn't exist
            if (empty($asset['qr_code'])) {
                $new_qr = "ASSET-" . strtoupper(uniqid());
                $update_stmt = $conn->prepare("UPDATE tech_assets SET qr_code = ? WHERE asset_id = ?");
                $update_stmt->bind_param("si", $new_qr, $asset_id);
                $update_stmt->execute();
                $update_stmt->close();
                $qr_data = $new_qr;
                $qr_url = generateQRCode($qr_data, 200);
            }
            
            $stmt->close();
            
            // Return QR code as image
            header('Content-Type: image/png');
            $qr_image = file_get_contents($qr_url);
            echo $qr_image;
            exit;
        }
        $stmt->close();
    }
}

// Generate QR code for inventory item
if (isset($_GET['item_id']) && !empty($_GET['item_id'])) {
    $item_id = (int)$_GET['item_id'];
    
    $stmt = $conn->prepare("SELECT item_id, sku, item_name FROM inventory_items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $qr_data = "INV-{$item['sku']}";
        $qr_url = generateQRCode($qr_data, 200);
        
        $stmt->close();
        
        header('Content-Type: image/png');
        $qr_image = file_get_contents($qr_url);
        echo $qr_image;
        exit;
    }
    $stmt->close();
}

// Generate QR code for purchase order
if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    
    $stmt = $conn->prepare("SELECT order_id, order_number FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $qr_data = "PO-{$order['order_number']}";
        $qr_url = generateQRCode($qr_data, 200);
        
        // Check if po_qr_codes table exists and record the QR code
        $qr_table_check = $conn->query("SHOW TABLES LIKE 'po_qr_codes'");
        if ($qr_table_check && $qr_table_check->num_rows > 0) {
            // Check if QR code already exists for this order
            $check_stmt = $conn->prepare("SELECT qr_id FROM po_qr_codes WHERE order_id = ?");
            $check_stmt->bind_param("i", $order_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();
            
            if (!$existing) {
                // Insert new QR code record
                $insert_stmt = $conn->prepare("INSERT INTO po_qr_codes (order_id, qr_code) VALUES (?, ?)");
                $insert_stmt->bind_param("is", $order_id, $qr_data);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
        }
        $qr_table_check->free();
        
        $stmt->close();
        
        header('Content-Type: image/png');
        $qr_image = file_get_contents($qr_url);
        echo $qr_image;
        exit;
    }
    $stmt->close();
}

// If no valid parameter, show error
http_response_code(400);
echo "Invalid QR code request";
$conn->close();
exit;
?>