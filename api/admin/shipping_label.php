<?php
// shipping_label.php
session_start();
require_once '../includes/db_connect.php';

// ફક્ત એડમિન જ આ લેબલ પ્રિન્ટ કરી શકે
if (!isset($_SESSION['admin_id'])) {
    die("Access Denied! Please login as Admin.");
}

if (!isset($_GET['oid'])) {
    die("Order ID is missing!");
}

$oid = $_GET['oid'];

try {
    // ઓર્ડરની માહિતી લાવો
    $sql = "SELECT o.oid, o.quantity, o.total_price, o.payment_method, 
                   u.u_name, u.mobile_number, 
                   p.product_name, 
                   osa.full_address 
            FROM orders o 
            LEFT JOIN users u ON o.uid = u.u_id 
            LEFT JOIN products p ON o.product_id = p.pid 
            LEFT JOIN order_shipping_addresses osa ON o.oid = osa.order_id 
            WHERE o.oid = :oid";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':oid' => $oid]);
    $order = $stmt->fetch();

    if (!$order) {
        die("Order not found!");
    }

    // બારકોડ માટે ઓર્ડર આઈડી નું ફોર્મેટ (દા.ત. ORD-0001)
    $order_barcode_id = "ORD-" . str_pad($order['oid'], 4, '0', STR_PAD_LEFT);

} catch (PDOException $e) {
    die("Error generating label: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shipping Label - <?php echo $order_barcode_id; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        
        /* પ્રિન્ટ કરતી વખતે ડિઝાઇન સેટ કરવા માટે */
        @media print {
            body { background-color: white; margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .label-box { 
                border: 2px solid black !important; 
                box-shadow: none !important; 
                width: 100% !important; 
                max-width: 400px !important; /* 4x6 ઇંચ સ્ટીકર માટે */
                margin: 0 auto !important;
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="p-8 text-black">

    <div class="max-w-[400px] mx-auto mb-6 flex justify-end gap-3 no-print">
        <button onclick="window.close()" class="px-4 py-2 bg-slate-300 font-bold rounded hover:bg-slate-400">Close</button>
        <button onclick="window.print()" class="px-4 py-2 bg-[#0A192F] text-white font-bold rounded hover:bg-[#B7915F] flex items-center gap-2">
            🖨️ Print Sticker
        </button>
    </div>

    <div class="label-box max-w-[400px] mx-auto bg-white border-2 border-slate-800 p-0 rounded-lg overflow-hidden shadow-xl">
        
        <div class="border-b-2 border-slate-800 p-4 bg-slate-50 flex justify-between items-center">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">Sender:</p>
                <h1 class="text-lg font-extrabold leading-tight">RajaRam & Sons</h1>
                <p class="text-[11px] font-semibold mt-1">Uplipaad Road, Bhuj (Kutch) 370001<br>Phone: +91 98255 80615</p>
            </div>
            <div class="text-right">
                <span class="inline-block border-2 border-slate-800 font-black text-sm px-3 py-1 uppercase rounded-md <?php echo (strtolower($order['payment_method']) == 'cod') ? 'bg-black text-white' : 'bg-white text-black'; ?>">
                    <?php echo htmlspecialchars($order['payment_method']); ?>
                </span>
            </div>
        </div>

        <div class="p-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2 border-b border-slate-200 pb-1">Deliver To:</p>
            <h2 class="text-2xl font-extrabold uppercase mb-2"><?php echo htmlspecialchars($order['u_name']); ?></h2>
            <p class="text-sm font-semibold leading-relaxed mb-3">
                <?php echo htmlspecialchars($order['full_address']); ?>
            </p>
            <p class="text-base font-extrabold border border-slate-300 inline-block px-3 py-1 rounded bg-slate-50">
                📞 +91 <?php echo htmlspecialchars($order['mobile_number']); ?>
            </p>
        </div>

        <div class="border-t-2 border-slate-800 p-4 text-center bg-white">
            <p class="text-xs font-bold text-slate-600 mb-1">Item: <?php echo htmlspecialchars($order['product_name']); ?> (Qty: <?php echo $order['quantity']; ?>)</p>
            <p class="text-base font-black mb-4">Total Amount: ₹<?php echo number_format($order['total_price'], 2); ?></p>
            
            <div class="flex justify-center">
                <svg id="barcode"></svg>
            </div>
        </div>

    </div>

    <script>
        JsBarcode("#barcode", "<?php echo $order_barcode_id; ?>", {
            format: "CODE128",
            lineColor: "#000",
            width: 2.5,
            height: 60,
            displayValue: true,
            fontSize: 16,
            fontOptions: "bold",
            margin: 0
        });
    </script>

</body>
</html>