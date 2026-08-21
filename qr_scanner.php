<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

require_once 'db_connection.php';

$section_title = "QR Code Scanner - Inventory";
$admin_user = $_SESSION['username'] ?? 'Admin User';
$user_role = $_SESSION['role'] ?? 'Supply Chain Manager';

$db_error = null;
$flash = null;
$scanned_item = null;

// Handle QR code scanning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_data'])) {
    $qr_data = trim($_POST['qr_data']);
    
    // Try to find item by SKU (format: INV-SKU)
    if (preg_match('/^INV-(.+)$/', $qr_data, $matches)) {
        $sku = $matches[1];
        $stmt = $conn->prepare("SELECT i.*, s.supplier_name FROM inventory_items i LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id WHERE i.sku = ? LIMIT 1");
        $stmt->bind_param("s", $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $scanned_item = $result->fetch_assoc();
            $flash = "Product found: " . $scanned_item['item_name'];
            
            // Log the scan
            $log_msg = "Scanned inventory item: " . $scanned_item['item_name'] . " (SKU: " . $scanned_item['sku'] . ")";
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'QR Scan', '$log_msg')");
        } else {
            $db_error = "No product found with SKU: $sku";
        }
        $stmt->close();
    } else {
        // Try direct SKU match
        $stmt = $conn->prepare("SELECT i.*, s.supplier_name FROM inventory_items i LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id WHERE i.sku = ? LIMIT 1");
        $stmt->bind_param("s", $qr_data);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $scanned_item = $result->fetch_assoc();
            $flash = "Product found: " . $scanned_item['item_name'];
            
            $log_msg = "Scanned inventory item: " . $scanned_item['item_name'] . " (SKU: " . $scanned_item['sku'] . ")";
            $conn->query("INSERT INTO activity_log (user_id, username, action, details) VALUES (" . $_SESSION['user_id'] . ", '" . $_SESSION['username'] . "', 'QR Scan', '$log_msg')");
        } else {
            $db_error = "No product found with QR code: $qr_data";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo $section_title; ?> — Console</title>
    <link href="app.css" rel="stylesheet"/>
    <script src="app.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        .scanner-container {
            background: #000;
            border-radius: 1rem;
            overflow: hidden;
            position: relative;
        }
        #reader {
            width: 100%;
            height: 300px;
        }
        .result-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-background text-on-background font-body h-screen flex flex-row overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <header class="bg-white shadow-sm border-b border-slate-200 flex justify-between items-center h-16 px-6 w-full z-30 shrink-0">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-800 text-sm">Inventory Management System</span>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto bg-slate-50 p-6 md:p-8">
            <div class="max-w-4xl mx-auto space-y-8">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">QR Code Scanner</h1>
                        <p class="text-slate-500 text-sm mt-1">Scan inventory QR codes to view product profiles and stock information.</p>
                    </div>
                </div>

                <?php if ($flash): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 rounded-lg">✅ <?php echo htmlspecialchars($flash); ?></div>
                <?php endif; ?>
                <?php if ($db_error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">⚠️ <?php echo htmlspecialchars($db_error); ?></div>
                <?php endif; ?>

                <!-- Scanner Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-sm font-bold text-slate-900">Scan QR Code</h2>
                    </div>
                    <div class="p-6">
                        <div class="scanner-container mb-4">
                            <div id="reader"></div>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="startScanner()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold">Start Camera</button>
                            <button type="button" onclick="stopScanner()" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold text-slate-600">Stop Camera</button>
                        </div>
                        
                        <!-- Manual Entry Fallback -->
                        <div class="mt-6 pt-6 border-t border-slate-200">
                            <form method="post" class="flex gap-3">
                                <input type="text" name="qr_data" placeholder="Or enter QR code/SKU manually..." class="flex-1 px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"/>
                                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold">Look Up</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Scanned Item Result -->
                <?php if ($scanned_item): ?>
                    <div class="result-card p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900"><?php echo htmlspecialchars($scanned_item['item_name']); ?></h3>
                                <p class="text-sm text-slate-500">SKU: <?php echo htmlspecialchars($scanned_item['sku']); ?></p>
                            </div>
                            <a href="qr_generator.php?item_id=<?php echo (int)$scanned_item['item_id']; ?>" target="_blank" class="text-sm text-blue-600 hover:underline">View QR Code</a>
                        </div>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-slate-50 p-3 rounded-lg">
                                <p class="text-xs text-slate-500 uppercase tracking-wider">Category</p>
                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($scanned_item['category'] ?: 'N/A'); ?></p>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg">
                                <p class="text-xs text-slate-500 uppercase tracking-wider">Quantity</p>
                                <p class="font-semibold text-slate-900"><?php echo (int)$scanned_item['quantity']; ?> <?php echo htmlspecialchars($scanned_item['unit']); ?></p>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg">
                                <p class="text-xs text-slate-500 uppercase tracking-wider">Unit Price</p>
                                <p class="font-semibold text-slate-900">₱<?php echo number_format((float)$scanned_item['unit_price'], 2); ?></p>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg">
                                <p class="text-xs text-slate-500 uppercase tracking-wider">Location</p>
                                <p class="font-semibold text-slate-900"><?php echo htmlspecialchars($scanned_item['warehouse_zone'] ?: 'Unshelved'); ?></p>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-slate-500 uppercase tracking-wider">Supplier</p>
                                    <p class="font-medium text-slate-900"><?php echo htmlspecialchars($scanned_item['supplier_name'] ?: 'No supplier assigned'); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 uppercase tracking-wider">Status</p>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold border 
                                        <?php echo $scanned_item['status'] === 'Active' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 
                                            ($scanned_item['status'] === 'Low Stock' ? 'bg-amber-100 text-amber-800 border-amber-200' : 
                                            ($scanned_item['status'] === 'Out of Stock' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-slate-100 text-slate-800 border-slate-200')); ?>">
                                        <?php echo htmlspecialchars($scanned_item['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex gap-2">
                            <a href="inventory.php" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold">View in Inventory</a>
                            <a href="inventory.php?q=<?php echo urlencode($scanned_item['sku']); ?>" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold text-slate-600">Edit Item</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        let html5QrcodeScanner = null;

        function startScanner() {
            if (html5QrcodeScanner) {
                return; // Already running
            }

            html5QrcodeScanner = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrcodeScanner.start({ facingMode: "environment" }, config, 
                function(decodedText, decodedResult) {
                    // Scan successful
                    stopScanner();
                    
                    // Submit the scanned data
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'qr_data';
                    input.value = decodedText;
                    
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                },
                function(errorMessage) {
                    // Scan error - ignore, it's normal during scanning
                }
            ).catch(function(err) {
                console.error("Error starting scanner", err);
                alert("Unable to access camera. Please ensure camera permissions are granted.");
            });
        }

        function stopScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(function() {
                    html5QrcodeScanner.clear();
                    html5QrcodeScanner = null;
                }).catch(function(err) {
                    console.error("Error stopping scanner", err);
                });
            }
        }

        // Clean up scanner when page is unloaded
        window.addEventListener('beforeunload', function() {
            stopScanner();
        });
    </script>
</body>
</html>