<?php
// offline_billing.php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /auth/admin_login.php");
    exit;
}

// Logged-in user nu name
$billed_by_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin User';

$success_msg = "";
$error_msg = "";
$last_invoice_no = "";

$products = $pdo->query("SELECT pid, product_name, price, stock_quantity FROM products WHERE stock_quantity > 0 ORDER BY product_name ASC")->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_bill'])) {
    $c_name = trim($_POST['customer_name']);
    $c_phone = trim($_POST['customer_phone']);
    $p_ids = $_POST['product_ids'] ?? [];
    $qtys = $_POST['quantities'] ?? [];

    if (empty($c_name) || empty($c_phone)) {
        $error_msg = "Please fill in all customer details.";
    } elseif (!preg_match('/^[0-9]{10}$/', $c_phone)) {
        $error_msg = "Mobile number must be exactly 10 digits.";
    } elseif (empty($p_ids)) {
        $error_msg = "Please add at least one product to the bill.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $invoice_no = "INV-" . strtoupper(substr(uniqid(), -6)); 

            for ($i = 0; $i < count($p_ids); $i++) {
                $pid = $p_ids[$i];
                $qty = (int)$qtys[$i];

                $stmt = $pdo->prepare("SELECT price, stock_quantity, product_name FROM products WHERE pid = :pid FOR UPDATE");
                $stmt->execute([':pid' => $pid]);
                $product = $stmt->fetch();

                if ($product && $product['stock_quantity'] >= $qty) {
                    $total_amount = $product['price'] * $qty;

                    $stmt_insert = $pdo->prepare("INSERT INTO offline_sales (buyer_name, mobile_number, product_name, quantity, price, sum_total, field_name, billed_by) 
                                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt_insert->execute([$c_name, $c_phone, $product['product_name'], $qty, $product['price'], $total_amount, $invoice_no, $billed_by_name]);

                    $stmt_update = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE pid = ?");
                    $stmt_update->execute([$qty, $pid]);
                } else {
                    throw new Exception("Not enough stock for " . $product['product_name']);
                }
            }

            $pdo->commit();
            $success_msg = "Sale successful! Bill generated.";
            $last_invoice_no = $invoice_no;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Offline POS Billing - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto bg-gradient-to-br from-[#0A192F] via-[#162A4A] to-[#B7915F] relative z-10">
        
        <?php include '../includes/admin_topbar.php'; ?>
        
        <div class="max-w-5xl w-full mx-auto bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/40 my-auto">
            
            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 border-b border-slate-200 pb-4 gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-[#0A192F]">Offline Store Billing</h2>
                    <p class="text-sm text-slate-500 mt-1 font-medium">Scan barcode to add multiple items to the bill.</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-xs font-bold bg-blue-100 text-blue-700 px-3 py-1.5 rounded-lg border border-blue-200 shadow-sm whitespace-nowrap">
                        👤 Logged in as: <?php echo htmlspecialchars($billed_by_name); ?>
                    </span>
                </div>
            </div>

            <?php if(!empty($success_msg)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl font-bold mb-6 flex justify-between items-center shadow-sm">
                    <div class="flex items-center gap-2"><span>✅</span> <?php echo $success_msg; ?></div>
                    <a href="/billing/offline_invoice.php?inv=<?php echo $last_invoice_no; ?>" target="_blank" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-emerald-700 transition-colors shadow flex items-center gap-2 animate-pulse">
                        📄 Print Invoice
                    </a>
                </div>
            <?php endif; ?>

            <?php if(!empty($error_msg)): ?>
                <script>Swal.fire({icon: 'error', title: 'Oops...', text: '<?php echo $error_msg; ?>'});</script>
            <?php endif; ?>

            <form action="offline_billing.php" method="POST" id="billingForm">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-slate-50 p-5 rounded-xl border border-slate-200 shadow-inner">
                            <h3 class="font-bold text-slate-700 uppercase tracking-widest text-xs border-b border-slate-200 pb-2 mb-4">Customer Details</h3>
                            <div class="space-y-4">
                                <div>
                                    <input type="text" name="customer_name" required placeholder="Customer Name *" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-white text-sm shadow-sm">
                                </div>
                                <div>
                                    <input type="text" name="customer_phone" required pattern="[0-9]{10}" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="10-digit Mobile *" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-white text-sm shadow-sm">
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 shadow-inner">
                            <h3 class="font-bold text-blue-800 uppercase tracking-widest text-xs border-b border-blue-200 pb-2 mb-4">Add Item to Cart</h3>
                            <div>
                                <label class="block text-xs font-bold text-blue-900 mb-1">Scan Barcode / Enter ID & Press Enter</label>
                                <input type="text" id="barcode_scanner" placeholder="RR0001" class="w-full px-4 py-3 border-2 border-blue-300 rounded-lg focus:border-[#0A192F] focus:ring-0 outline-none bg-white font-mono text-blue-700 font-bold text-center tracking-widest uppercase shadow-sm">
                            </div>
                            <div class="text-center text-[10px] font-bold text-blue-400 my-2">--- OR SELECT MANUALLY ---</div>
                            <div class="flex gap-2">
                                <select id="product_select" class="w-full px-3 py-2 border border-slate-300 rounded-lg outline-none bg-white text-sm font-medium shadow-sm">
                                    <option value="" disabled selected>-- Select Product --</option>
                                    <?php foreach($products as $p): ?>
                                        <option value="<?php echo $p['pid']; ?>" data-name="<?php echo htmlspecialchars($p['product_name']); ?>" data-price="<?php echo $p['price']; ?>" data-stock="<?php echo $p['stock_quantity']; ?>">
                                            <?php echo htmlspecialchars($p['product_name']); ?> (₹<?php echo $p['price']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" onclick="addManualItem()" class="bg-[#B7915F] text-white px-3 py-2 rounded-lg font-bold hover:bg-[#96764a] text-sm whitespace-nowrap shadow-sm">Add</button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col h-full">
                        <div class="p-4 border-b border-slate-100 bg-slate-50 rounded-t-xl flex justify-between items-center">
                            <h3 class="font-bold text-slate-700 uppercase tracking-widest text-sm">🛒 Current Bill Items</h3>
                        </div>
                        
                        <div class="flex-1 p-4 overflow-y-auto max-h-80">
                            <table class="w-full text-left border-collapse" id="cartTable">
                                <thead>
                                    <tr class="text-xs uppercase font-bold text-slate-400 border-b border-slate-100">
                                        <th class="pb-2">Item</th>
                                        <th class="pb-2 text-center">Price</th>
                                        <th class="pb-2 text-center">Qty</th>
                                        <th class="pb-2 text-right">Total</th>
                                        <th class="pb-2 text-center">Del</th>
                                    </tr>
                                </thead>
                                <tbody id="cartBody" class="text-sm divide-y divide-slate-100">
                                    </tbody>
                            </table>
                            <div id="emptyCartMsg" class="text-center text-slate-400 font-medium py-10">Scan a product to add it to the bill.</div>
                        </div>

                        <div class="p-4 border-t border-slate-200 bg-slate-50 rounded-b-xl">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-lg font-bold text-slate-600">Grand Total:</span>
                                <span class="text-3xl font-black text-[#0A192F]" id="grandTotalDisplay">₹0.00</span>
                            </div>
                            <button type="submit" name="generate_bill" onclick="return validateCart()" class="w-full bg-gradient-to-r from-[#0A192F] to-[#162A4A] text-white font-black py-4 rounded-xl hover:from-[#162A4A] hover:to-[#0A192F] transition-all shadow-lg text-lg uppercase tracking-widest">
                                Confirm Sale & Print Bill
                            </button>
                        </div>
                    </div>
                </div>
                <div id="hiddenInputs"></div>
            </form>
        </div>
    </main>

    <script>
        let cart = {}; 

        function updateCartUI() {
            let tbody = document.getElementById('cartBody');
            let hiddenDiv = document.getElementById('hiddenInputs');
            tbody.innerHTML = '';
            hiddenDiv.innerHTML = '';
            
            let grandTotal = 0;
            let itemCount = 0;

            for (let pid in cart) {
                let item = cart[pid];
                let lineTotal = item.price * item.qty;
                grandTotal += lineTotal;
                itemCount++;

                let tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="py-3 font-bold text-[#0A192F]">${item.name}</td>
                    <td class="py-3 text-center text-slate-500">₹${item.price}</td>
                    <td class="py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" onclick="updateQty('${pid}', -1)" class="bg-slate-200 text-slate-700 w-6 h-6 rounded-full font-bold leading-none hover:bg-slate-300">-</button>
                            <span class="font-bold w-4 text-center">${item.qty}</span>
                            <button type="button" onclick="updateQty('${pid}', 1)" class="bg-slate-200 text-slate-700 w-6 h-6 rounded-full font-bold leading-none hover:bg-slate-300">+</button>
                        </div>
                    </td>
                    <td class="py-3 text-right font-black text-[#B7915F]">₹${lineTotal.toFixed(2)}</td>
                    <td class="py-3 text-center">
                        <button type="button" onclick="removeItem('${pid}')" class="text-red-500 hover:text-red-700 text-lg">🗑️</button>
                    </td>
                `;
                tbody.appendChild(tr);

                hiddenDiv.innerHTML += `<input type="hidden" name="product_ids[]" value="${pid}">`;
                hiddenDiv.innerHTML += `<input type="hidden" name="quantities[]" value="${item.qty}">`;
            }

            document.getElementById('grandTotalDisplay').innerText = "₹" + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
            document.getElementById('emptyCartMsg').style.display = itemCount > 0 ? 'none' : 'block';
        }

        function addToCart(pid, name, price, stock) {
            if (cart[pid]) {
                if (cart[pid].qty < stock) {
                    cart[pid].qty++;
                } else {
                    Swal.fire({icon: 'warning', title: 'Stock Limit Reached', text: 'Only ' + stock + ' items available!'});
                }
            } else {
                if(stock > 0) {
                    cart[pid] = { name: name, price: parseFloat(price), qty: 1, stock: parseInt(stock) };
                } else {
                    Swal.fire({icon: 'error', title: 'Out of Stock', text: 'This item is out of stock.'});
                }
            }
            updateCartUI();
        }

        function updateQty(pid, change) {
            if (cart[pid]) {
                let newQty = cart[pid].qty + change;
                if (newQty > 0 && newQty <= cart[pid].stock) {
                    cart[pid].qty = newQty;
                } else if (newQty > cart[pid].stock) {
                    Swal.fire({icon: 'warning', title: 'Stock Limit Reached', text: 'Only ' + cart[pid].stock + ' items available!'});
                }
                updateCartUI();
            }
        }

        function removeItem(pid) {
            delete cart[pid];
            updateCartUI();
        }

        document.getElementById('barcode_scanner').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                let val = this.value.trim().toUpperCase();
                if (val === '') return;

                let pid = val.replace(/[^0-9]/g, ''); 
                pid = parseInt(pid, 10).toString(); 

                let select = document.getElementById("product_select");
                let found = false;

                for (let i = 1; i < select.options.length; i++) {
                    if (select.options[i].value === pid) {
                        found = true;
                        let opt = select.options[i];
                        addToCart(pid, opt.getAttribute('data-name'), opt.getAttribute('data-price'), opt.getAttribute('data-stock'));
                        break;
                    }
                }

                if (!found) {
                    Swal.fire({icon: 'error', title: 'Oops...', text: 'Product not found!'});
                }
                this.value = ''; 
            }
        });

        function addManualItem() {
            let select = document.getElementById("product_select");
            if(select.selectedIndex > 0) {
                let opt = select.options[select.selectedIndex];
                addToCart(opt.value, opt.getAttribute('data-name'), opt.getAttribute('data-price'), opt.getAttribute('data-stock'));
                select.selectedIndex = 0; 
            }
        }

        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') {
                e.preventDefault();
            }
        });

        function validateCart() {
            if (Object.keys(cart).length === 0) {
                Swal.fire({icon: 'error', title: 'Empty Bill', text: 'Please scan or add at least one item to the bill.'});
                return false;
            }
            return true;
        }
    </script>
</body>
</html>