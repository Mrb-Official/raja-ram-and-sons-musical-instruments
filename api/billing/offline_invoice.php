<?php
// offline_invoice.php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    die("Access Denied!");
}

if (!isset($_GET['inv'])) {
    die("Invoice No is missing!");
}

$inv_no = $_GET['inv'];

try {
    // અહીંથી આપણે billed_by અને created_at પણ ખેંચીએ છીએ
    $stmt = $pdo->prepare("SELECT * FROM offline_sales WHERE field_name = :inv");
    $stmt->execute([':inv' => $inv_no]);
    $sales = $stmt->fetchAll();

    if (empty($sales)) {
        die("Invoice not found!");
    }

    $customer_name = $sales[0]['buyer_name'];
    $customer_phone = $sales[0]['mobile_number'];
    
    // બિલ બનાવનાર સ્ટાફનું નામ અને બિલિંગનો સમય
    $billed_by = $sales[0]['billed_by'] ?? 'Admin User';
    
    date_default_timezone_set('Asia/Kolkata');
    $bill_time = date('d/m/Y h:i A', strtotime($sales[0]['created_at'] ?? 'now'));
    
    $grand_total = 0;
    foreach ($sales as $sale) {
        $grand_total += $sale['sum_total'];
    }

} catch (PDOException $e) {
    die("Error generating invoice: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Retail Invoice <?php echo htmlspecialchars($inv_no); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; }
            .invoice-box { box-shadow: none !important; border: none !important; margin: 0; padding: 0;}
            @page { margin: 0; }
            body { margin: 1.6cm; }
        }
    </style>
</head>
<body class="bg-slate-100 p-8 font-sans text-slate-800">

    <div class="max-w-4xl mx-auto mb-6 flex justify-end gap-4 no-print">
        <button onclick="window.close()" class="px-5 py-2 bg-slate-300 text-slate-700 font-bold rounded hover:bg-slate-400 transition-colors">Close</button>
        <button onclick="window.print()" class="px-5 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700 transition-colors flex items-center gap-2">
            🖨️ Print Invoice
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
                    Phone: +91 98255 80615
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-3xl font-black text-slate-300 uppercase tracking-widest mb-2">Retail Invoice</h2>
                <p class="text-sm font-bold text-slate-700">Invoice No: <span class="text-[#0A192F]"><?php echo htmlspecialchars($inv_no); ?></span></p>
                <p class="text-sm font-bold text-slate-700 mt-1">Time: <span class="font-medium text-slate-600"><?php echo $bill_time; ?></span></p>
                <p class="text-sm font-bold text-slate-700 mt-1">Cashier: <span class="font-medium text-slate-600 uppercase bg-slate-100 px-2 py-0.5 rounded border border-slate-200"><?php echo htmlspecialchars($billed_by); ?></span></p>
            </div>
        </div>

        <div class="mb-10 p-6 bg-slate-50 rounded-lg border border-slate-100">
            <h3 class="text-lg font-bold text-[#0A192F] border-b border-slate-200 pb-2 mb-4 uppercase tracking-wider">Billed To</h3>
            <p class="font-extrabold text-slate-800 text-lg"><?php echo htmlspecialchars($customer_name); ?></p>
            <p class="text-slate-600 mt-1 font-medium">Mobile: +91 <?php echo htmlspecialchars($customer_phone); ?></p>
        </div>

        <table class="w-full text-left border-collapse mb-10">
            <thead>
                <tr class="bg-[#0A192F] text-white">
                    <th class="p-4 font-bold rounded-tl-lg">Description of Goods</th>
                    <th class="p-4 font-bold text-center">Qty</th>
                    <th class="p-4 font-bold text-center">Rate (₹)</th>
                    <th class="p-4 font-bold text-right rounded-tr-lg">Total Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($sales as $item): ?>
                <tr class="border-b border-slate-200">
                    <td class="p-4 font-bold text-slate-800">
                        <?php echo htmlspecialchars($item['product_name']); ?>
                    </td>
                    <td class="p-4 text-center text-slate-800 font-bold"><?php echo $item['quantity']; ?></td>
                    <td class="p-4 text-center text-slate-600"><?php echo number_format($item['price'], 2); ?></td>
                    <td class="p-4 text-right font-bold text-slate-800"><?php echo number_format($item['sum_total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="flex justify-end mb-12">
            <div class="w-full md:w-1/2 space-y-3">
                <div class="flex justify-between text-xl font-black text-[#0A192F] border-t border-slate-200 pt-3">
                    <span>Grand Total:</span>
                    <span class="text-[#B7915F]">₹<?php echo number_format($grand_total, 2); ?></span>
                </div>
                <div class="text-right text-xs text-slate-500 mt-2 font-medium">
                    (Amount includes all applicable taxes)
                </div>
            </div>
        </div>

        <div class="flex justify-between items-end border-t-2 border-slate-100 pt-8 mt-8">
            <div class="text-slate-500 text-xs">
                <p class="font-bold text-slate-700">Terms & Conditions:</p>
                <p>1. Goods once sold will not be taken back.</p>
                <p>2. Subject to Bhuj jurisdiction.</p>
                <p>3. Guarantee/Warranty as per manufacturer terms.</p>
            </div>
            <div class="text-center">
                <p class="font-bold text-[#0A192F] text-sm uppercase"><?php echo htmlspecialchars($billed_by); ?></p>
                <p class="text-xs text-slate-500 mt-1">Authorized Cashier</p>
            </div>
        </div>
        
    </div>
</body>
</html>