<?php
// invoice.php
session_start();
require_once '../includes/db_connect.php';

// જો એડમિન કે યુઝર કોઈ પણ લોગ-ઈન ના હોય તો અટકાવો (સિક્યુરિટી માટે)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    die("Access Denied!");
}

if (!isset($_GET['oid'])) {
    die("Order ID is missing!");
}

$oid = $_GET['oid'];

try {
    // ઓર્ડરની બધી જ ડિટેલ્સ લાવો
    $sql = "SELECT o.oid, o.quantity, o.total_price, o.payment_method, o.status, 
                   u.u_name, u.email_id, u.mobile_number, 
                   p.product_name, p.price as unit_price, 
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

    // --- GST Calculation Logic (18% GST Inclusive) ---
    $grand_total = $order['total_price'];
    $gst_rate = 18; // 18% કુલ GST
    
    // મૂળ કિંમત (ટેક્સ વગરની)
    $taxable_value = $grand_total / (1 + ($gst_rate / 100));
    
    // કુલ ટેક્સ
    $total_gst_amount = $grand_total - $taxable_value;
    
    // CGST (9%) અને SGST (9%) ગુજરાત માટે
    $cgst = $total_gst_amount / 2;
    $sgst = $total_gst_amount / 2;

} catch (PDOException $e) {
    die("Error generating invoice: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice #ORD-<?php echo str_pad($order['oid'], 4, '0', STR_PAD_LEFT); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* પ્રિન્ટ કરતી વખતે આ બટનો ગાયબ થઈ જશે */
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; }
            .invoice-box { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-8 font-sans text-slate-800">

    <div class="max-w-4xl mx-auto mb-6 flex justify-end gap-4 no-print">
        <button onclick="window.close()" class="px-5 py-2 bg-slate-300 text-slate-700 font-bold rounded hover:bg-slate-400 transition-colors">Close</button>
        <button onclick="window.print()" class="px-5 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 transition-colors flex items-center gap-2">
            🖨️ Print / Save as PDF
        </button>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-12 rounded-xl shadow-lg border border-slate-200 invoice-box">
        
        <div class="flex justify-between items-start border-b-2 border-[#0A192F] pb-8 mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#0A192F] tracking-tight font-serif">RajaRam & Sons</h1>
                <p class="text-[#B7915F] font-bold text-sm tracking-[0.2em] uppercase mt-1">The Grand Music Mall</p>
                <div class="text-slate-500 text-sm mt-4 leading-relaxed">
                    Uplipaad Road,<br>
                    Opp. Shree Raghunathji Temple,<br>
                    Bhuj (Kutch) 370 001. Gujarat.<br>
                    Phone: +91 98255 80615<br>
                    <span class="font-bold text-slate-700">GSTIN: 24ABCDE1234F1Z5</span> </div>
            </div>
            <div class="text-right">
                <h2 class="text-3xl font-black text-slate-300 uppercase tracking-widest mb-2">Tax Invoice</h2>
                <p class="text-sm font-bold text-slate-700">Invoice No: <span class="text-[#0A192F]">#INV-<?php echo str_pad($order['oid'], 4, '0', STR_PAD_LEFT); ?></span></p>
                <p class="text-sm font-bold text-slate-700 mt-1">Order Date: <span class="font-medium text-slate-600"><?php echo date('d M, Y'); ?></span></p>
                <p class="text-sm font-bold text-slate-700 mt-1">State Code: <span class="font-medium text-slate-600">24 (Gujarat)</span></p>
            </div>
        </div>

        <div class="mb-10 p-6 bg-slate-50 rounded-lg border border-slate-100">
            <h3 class="text-lg font-bold text-[#0A192F] border-b border-slate-200 pb-2 mb-4 uppercase tracking-wider">Billed To</h3>
            <p class="font-extrabold text-slate-800 text-lg"><?php echo htmlspecialchars($order['u_name']); ?></p>
            <p class="text-slate-600 mt-1">Phone: <?php echo htmlspecialchars($order['mobile_number']); ?></p>
            <p class="text-slate-600">Email: <?php echo htmlspecialchars($order['email_id']); ?></p>
            <p class="text-slate-600 mt-3 font-medium">Shipping Address:</p>
            <p class="text-slate-700 text-sm mt-1 max-w-md"><?php echo htmlspecialchars($order['full_address']); ?></p>
        </div>

        <table class="w-full text-left border-collapse mb-10">
            <thead>
                <tr class="bg-[#0A192F] text-white">
                    <th class="p-4 font-bold rounded-tl-lg">Description of Goods</th>
                    <th class="p-4 font-bold text-center">HSN/SAC</th>
                    <th class="p-4 font-bold text-center">Qty</th>
                    <th class="p-4 font-bold text-center">Rate (₹)</th>
                    <th class="p-4 font-bold text-right rounded-tr-lg">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-slate-200">
                    <td class="p-4 font-bold text-slate-800">
                        <?php echo htmlspecialchars($order['product_name']); ?><br>
                        <span class="text-xs font-normal text-slate-500">Musical Instrument</span>
                    </td>
                    <td class="p-4 text-center text-slate-600">9207</td> <td class="p-4 text-center text-slate-800 font-bold"><?php echo $order['quantity']; ?></td>
                    <td class="p-4 text-center text-slate-600"><?php echo number_format($taxable_value / $order['quantity'], 2); ?></td>
                    <td class="p-4 text-right font-bold text-slate-800"><?php echo number_format($taxable_value, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-end mb-12">
            <div class="w-full md:w-1/2 space-y-3">
                <div class="flex justify-between text-slate-600">
                    <span>Taxable Value:</span>
                    <span class="font-bold">₹<?php echo number_format($taxable_value, 2); ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Add: CGST (9%):</span>
                    <span class="font-bold">₹<?php echo number_format($cgst, 2); ?></span>
                </div>
                <div class="flex justify-between text-slate-600 border-b border-slate-200 pb-3">
                    <span>Add: SGST (9%):</span>
                    <span class="font-bold">₹<?php echo number_format($sgst, 2); ?></span>
                </div>
                <div class="flex justify-between text-xl font-black text-[#0A192F] pt-2">
                    <span>Grand Total:</span>
                    <span class="text-[#B7915F]">₹<?php echo number_format($grand_total, 2); ?></span>
                </div>
                <div class="text-right text-xs text-slate-500 mt-2 font-medium">
                    (Amount includes all applicable taxes)
                </div>
                <div class="text-right text-xs text-slate-400 mt-1">
                    Payment Method: <?php echo htmlspecialchars($order['payment_method']); ?>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-end border-t-2 border-slate-100 pt-8 mt-8">
            <div class="text-slate-500 text-xs">
                <p class="font-bold text-slate-700">Terms & Conditions:</p>
                <p>1. Goods once sold will not be taken back.</p>
                <p>2. Subject to Bhuj jurisdiction.</p>
                <p>3. This is a computer generated invoice.</p>
            </div>
            <div class="text-center">
                <div class="h-16 w-32 border-b border-slate-400 mb-2 mx-auto"></div>
                <p class="font-bold text-[#0A192F] text-sm">For RajaRam & Sons</p>
                <p class="text-xs text-slate-500">Authorized Signatory</p>
            </div>
        </div>
        
    </div>
</body>
</html>