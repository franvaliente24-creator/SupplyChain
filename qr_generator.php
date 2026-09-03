<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

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

// ---------------------------------------------------------------
// Tech asset QR (Smart Warehousing System) — connects to db_sws
// ---------------------------------------------------------------
if (isset($_GET['asset_id']) && !empty($_GET['asset_id'])) {
    require_once 'sws_connection.php';
    $asset_id = (int)$_GET['asset_id'];

    $stmt = $conn->prepare("SELECT asset_id, asset_name, qr_code FROM tech_assets WHERE asset_id = ?");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $asset = $result->fetch_assoc();
        $qr_data = $asset['qr_code'] ?: "ASSET-{$asset_id}";
        $qr_url = generateQRCode($qr_data, 200);

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
        $conn->close();

        header('Content-Type: image/png');
        echo file_get_contents($qr_url);
        exit;
    }
    $stmt->close();
    $conn->close();
}

// ---------------------------------------------------------------
// Inventory item QR (Inventory Management System) — connects to
// db_ims. Uses item_master, not the old inventory_items table.
// ---------------------------------------------------------------
if (isset($_GET['item_id']) && !empty($_GET['item_id'])) {
    require_once 'ims_connection.php';
    $item_id = (int)$_GET['item_id'];

    $stmt = $conn->prepare("SELECT item_id, sku, item_name FROM item_master WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $qr_data = "INV-{$item['sku']}";
        $qr_url = generateQRCode($qr_data, 200);

        $stmt->close();
        $conn->close();

        header('Content-Type: image/png');
        echo file_get_contents($qr_url);
        exit;
    }
    $stmt->close();
    $conn->close();
}

// ---------------------------------------------------------------
// Purchase order QR (Purchase Order Management) — connects to
// db_pom. po_qr_codes is a same-database table (see
// db_pom_add_po_qr_codes.sql), so this insert is local, not a
// cross-service call.
// ---------------------------------------------------------------
if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    require_once 'pom_connection.php';
    $order_id = (int)$_GET['order_id'];

    $stmt = $conn->prepare("SELECT order_id, order_number FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        $qr_data = "PO-{$order['order_number']}";
        $qr_url = generateQRCode($qr_data, 200);

        $check_stmt = $conn->prepare("SELECT qr_id FROM po_qr_codes WHERE order_id = ?");
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if (!$existing) {
            $insert_stmt = $conn->prepare("INSERT INTO po_qr_codes (order_id, qr_code) VALUES (?, ?)");
            $insert_stmt->bind_param("is", $order_id, $qr_data);
            $insert_stmt->execute();
            $insert_stmt->close();
        }

        $stmt->close();
        $conn->close();

        header('Content-Type: image/png');
        echo file_get_contents($qr_url);
        exit;
    }
    $stmt->close();
    $conn->close();
}

// If no valid parameter, or the lookup found nothing, show an error
http_response_code(400);
echo "Invalid QR code request";
exit;
?>