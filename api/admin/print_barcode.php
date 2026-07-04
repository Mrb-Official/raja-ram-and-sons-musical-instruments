<?php
// print_barcode.php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    die("Access Denied.");
}

if (!isset($_GET['id'])) {
    die("Product ID is missing.");
}
$pid = $_GET['id'];

// પ્રોડક્ટની ડિટેલ લાવો
$stmt = $pdo->prepare("SELECT * FROM products WHERE pid = :pid");
$stmt->execute([':pid' => $pid]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

// સ્ટોક અને બારકોડ વેલ્યુ (જેમ કે RR0001)
$stock = $product['stock_quantity'];
if ($stock <= 0) {
    die("Stock is 0. No barcodes to print.");
}

$barcode_value = "RR" . str_pad($product['pid'], 4, '0', STR_PAD_LEFT);

// દુકાનનો મોબાઈલ નંબર
$shop_mobile = "98255 80615"; 

// પ્રિન્ટ કર્યાનો સમય (ભારતીય સમય મુજબ)
date_default_timezone_set('Asia/Kolkata');
$print_time = date('d/m/Y h:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Barcodes - <?php echo htmlspecialchars($product['product_name']); ?></title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #e2e8f0; margin: 0; padding: 20px; }
        .no-print { margin-bottom: 20px; text-align: right; }
        .btn { background: #0A192F; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; margin-left: 10px;}
        .btn-close { background: #64748b; }
        
        /* સ્ટીકરનું લેઆઉટ */
        .container { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; }
        .label { 
            background: white; 
            width: 50mm; /* સ્ટાન્ડર્ડ બારકોડ સ્ટીકરની સાઈઝ */
            padding: 8px; 
            text-align: center; 
            border: 1px solid #cbd5e1; 
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .store-name { font-size: 11px; font-weight: 900; color: #B7915F; text-transform: uppercase; margin-bottom: 3px; letter-spacing: 0.5px;}
        .product-name { font-size: 12px; font-weight: bold; color: #0A192F; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .price { font-size: 15px; font-weight: 900; color: #000; margin-bottom: 3px; }
        
        /* બારકોડને સેન્ટરમાં રાખવા માટેનો નવો ક્લાસ */
        .barcode-container { text-align: center; width: 100%; display: flex; justify-content: center; align-items: center; }
        
        /* ફૂટર (નંબર અને ટાઈમ) માટેની સ્ટાઈલ */
        .footer-info { display: flex; justify-content: space-between; align-items: center; margin-top: 3px; border-top: 1px dashed #e2e8f0; padding-top: 3px; }
        .contact-info { font-size: 9px; font-weight: bold; color: #475569; }
        .timestamp { font-size: 7px; color: #94a3b8; font-weight: 600; }

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none; }
            .container { gap: 5px; justify-content: flex-start; }
            .label { border: 1px dashed #94a3b8; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn">🖨️ Print <?php echo $stock; ?> Labels</button>
        <button onclick="window.close()" class="btn btn-close">Close</button>
    </div>

    <div class="container">
        <?php for($i = 1; $i <= $stock; $i++): ?>
            <div class="label">
                <div>
                    <div class="store-name">RajaRam & Sons</div>
                    <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                    <div class="price">₹<?php echo number_format($product['price'], 2); ?></div>
                </div>
                
                <div class="barcode-container">
                    <svg class="barcode" 
                         jsbarcode-value="<?php echo $barcode_value; ?>" 
                         jsbarcode-height="35" 
                         jsbarcode-width="1.5" 
                         jsbarcode-fontsize="11"
                         jsbarcode-margin="0">
                    </svg>
                </div>
                
                <div class="footer-info">
                    <span class="contact-info">Ph: <?php echo $shop_mobile; ?></span>
                    <span class="timestamp"><?php echo $print_time; ?></span>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <script>
        JsBarcode(".barcode").init();
    </script>
</body>
</html>