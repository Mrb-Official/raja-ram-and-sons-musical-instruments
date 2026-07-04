<?php
// thermal_receipt.php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    die("Access Denied!");
}

if (!isset($_GET['sale_id'])) {
    die("Invalid Bill ID!");
}

$sale_id = $_GET['sale_id'];

try {
    // તમારા ઓરિજિનલ ટેબલમાંથી ડેટા લાવો
    $stmt = $pdo->prepare("SELECT * FROM offline_sales WHERE offline_sales_id = :id");
    $stmt->execute([':id' => $sale_id]);
    $sale = $stmt->fetch();

    if (!$sale) {
        die("Bill not found!");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>POS Receipt - #<?php echo $sale['offline_sales_id']; ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap');
        
        body {
            font-family: 'Courier Prime', monospace;
            background-color: #f0f0f0;
            color: #000;
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        .receipt-container {
            width: 300px;
            background-color: #fff;
            margin: 0 auto;
            padding: 15px;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px; }
        .border-bottom { border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 3px 0; }
        
        @media print {
            body { background-color: #fff; padding: 0; }
            .receipt-container { box-shadow: none; width: 100%; max-width: 300px; margin: 0; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" style="padding: 10px 20px; font-weight: bold; cursor: pointer;">🖨️ Print Receipt</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>

    <div class="receipt-container">
        <div class="text-center border-bottom">
            <h2 style="margin: 0 0 5px 0; font-size: 18px;">RajaRam & Sons</h2>
            <p style="margin: 0 0 2px 0;">The Grand Music Mall</p>
            <p style="margin: 0 0 2px 0;">Uplipaad Road, Bhuj</p>
            <p style="margin: 0 0 5px 0;">Ph: +91 98255 80615</p>
        </div>

        <div style="margin: 10px 0;">
            <p style="margin: 2px 0;">Date: <?php echo date('d-m-Y H:i'); ?></p>
            <p style="margin: 2px 0;">Bill No: #POS-<?php echo str_pad($sale['offline_sales_id'], 4, '0', STR_PAD_LEFT); ?></p>
            <p style="margin: 2px 0;">Customer: <?php echo htmlspecialchars($sale['buyer_name']); ?></p>
            <?php if(!empty($sale['mobile_number'])): ?>
                <p style="margin: 2px 0;">Phone: <?php echo htmlspecialchars($sale['mobile_number']); ?></p>
            <?php endif; ?>
        </div>

        <div class="border-top border-bottom">
            <table>
                <thead>
                    <tr class="font-bold border-bottom">
                        <th style="text-align: left;">Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left; padding-right: 5px;">
                            <?php echo htmlspecialchars($sale['product_name']); ?><br>
                            <small>@ ₹<?php echo number_format($sale['price'], 2); ?></small>
                        </td>
                        <td class="text-center align-top"><?php echo $sale['quantity']; ?></td>
                        <td class="text-right align-top">₹<?php echo number_format($sale['sum_total'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-bottom" style="padding-top: 5px;">
            <table>
                <tr class="font-bold" style="font-size: 14px;">
                    <td style="text-align: left;">NET TOTAL:</td>
                    <td class="text-right">₹<?php echo number_format($sale['sum_total'], 2); ?></td>
                </tr>
            </table>
        </div>

        <div class="text-center" style="margin-top: 15px;">
            <p style="margin: 0 0 2px 0; font-weight: bold;">Thank You for visiting!</p>
            <p style="margin: 0; font-size: 10px;">Goods once sold will not be returned.</p>
            <p style="margin: 5px 0 0 0;">*** Have a Musical Day ***</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>

</body>
</html>