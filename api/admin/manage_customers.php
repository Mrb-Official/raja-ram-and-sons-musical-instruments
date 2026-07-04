<?php
// manage_customers.php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /auth/admin_login.php");
    exit;
}

try {
    $sql = "SELECT u.u_id, u.u_name, u.email_id, u.mobile_number, 
                   (SELECT full_address FROM order_shipping_addresses WHERE user_id = u.u_id ORDER BY o_ship_id DESC LIMIT 1) as user_address,
                   COUNT(o.oid) as total_orders, 
                   SUM(o.total_price) as total_spent 
            FROM users u 
            LEFT JOIN orders o ON u.u_id = o.uid 
            GROUP BY u.u_id 
            ORDER BY u.u_id DESC";
            
    $customers = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    die("Error fetching customers: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Customers - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include '../includes/sidebar.php'; ?>

   <main class="flex-1 flex flex-col h-screen overflow-y-auto bg-gradient-to-br from-[#0A192F] via-[#162A4A] to-[#B7915F] relative z-10">
        
        <?php include '../includes/admin_topbar.php'; ?>
         
        <div class="max-w-7xl w-full mx-auto bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/40 my-auto">
            
            <div class="mb-8">
                <h2 class="text-3xl font-black text-[#0A192F] tracking-tight">Registered Customers</h2>
                <p class="text-sm text-slate-500 mt-1 font-medium">View your online customers and their purchase history.</p>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-end gap-6 mb-8">
                <div class="bg-gradient-to-br from-[#0A192F] to-[#162A4A] p-6 rounded-2xl text-white shadow-lg relative overflow-hidden w-full md:w-64 shrink-0">
                    <div class="absolute -right-4 -bottom-4 opacity-10 text-7xl">👥</div>
                    <p class="text-xs font-bold text-slate-300 uppercase tracking-widest mb-1">Total Customers</p>
                    <h3 class="text-3xl font-black"><?php echo count($customers); ?></h3>
                </div>
                
                <div class="w-full md:w-96 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="customerSearch" placeholder="Search by name or number..." class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#B7915F] outline-none bg-white transition-colors text-sm font-medium text-slate-700 shadow-sm">
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left border-collapse" id="customersTable">
                        <thead>
                            <tr class="bg-[#0A192F] text-xs uppercase font-bold text-white tracking-wider">
                                <th class="p-4 w-16 text-center border-b border-slate-300">ID</th>
                                <th class="p-4 border-b border-slate-300">Customer Name</th>
                                <th class="p-4 border-b border-slate-300">Contact & Address Info</th>
                                <th class="p-4 text-center border-b border-slate-300">Total Orders</th>
                                <th class="p-4 text-right border-b border-slate-300">Total Spent (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php if(empty($customers)): ?>
                                <tr id="noCustomersRow"><td colspan="5" class="p-10 text-center text-slate-500 font-bold text-lg">No customers registered yet.</td></tr>
                            <?php else: ?>
                                <?php foreach($customers as $c): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors group customer-row">
                                    
                                    <td class="p-5 text-center font-bold text-slate-400 align-top group-hover:text-blue-500 transition-colors">
                                        #<?php echo str_pad($c['u_id'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>

                                    <td class="p-5 align-top">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-100 shrink-0">
                                                <?php echo strtoupper(substr($c['u_name'], 0, 1)); ?>
                                            </div>
                                            <p class="font-extrabold text-slate-800 text-base customer-name"><?php echo htmlspecialchars($c['u_name']); ?></p>
                                        </div>
                                    </td>

                                    <td class="p-5 align-top">
                                        <div class="space-y-1.5">
                                            <p class="font-medium text-slate-700 flex items-center gap-2 customer-phone">
                                                <span class="text-slate-400">📞</span> +91 <?php echo htmlspecialchars($c['mobile_number']); ?>
                                            </p>
                                            
                                            <?php if(!empty($c['email_id'])): ?>
                                                <p class="font-medium text-slate-500 flex items-center gap-2 text-xs">
                                                    <span class="text-slate-400">✉️</span> <?php echo htmlspecialchars($c['email_id']); ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if(!empty($c['user_address'])): ?>
                                                <p class="font-semibold text-slate-600 flex items-start gap-2 mt-2 text-xs bg-slate-50 p-2.5 rounded-lg w-max max-w-sm leading-relaxed border border-slate-100 shadow-inner">
                                                    <span class="text-slate-400">🏠</span> <?php echo htmlspecialchars($c['user_address']); ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="text-xs text-slate-400 mt-2 font-medium italic">No address provided</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="p-5 text-center align-top">
                                        <?php if($c['total_orders'] > 0): ?>
                                            <span class="bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-lg text-xs font-extrabold shadow-sm">
                                                <?php echo $c['total_orders']; ?> Orders
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-slate-50 text-slate-500 border border-slate-200 px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm">
                                                No Orders
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-5 text-right align-top">
                                        <?php if($c['total_spent'] > 0): ?>
                                            <p class="font-black text-emerald-600 text-lg tracking-tight">₹<?php echo number_format($c['total_spent'], 2); ?></p>
                                        <?php else: ?>
                                            <p class="font-bold text-slate-300 text-lg">₹0.00</p>
                                        <?php endif; ?>
                                    </td>

                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="noSearchResults" class="hidden">
                                <td colspan="5" class="p-10 text-center text-slate-500 font-bold text-sm">No matching customers found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('customerSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.customer-row');
            let hasResults = false;

            rows.forEach(row => {
                let name = row.querySelector('.customer-name').textContent.toLowerCase();
                let phone = row.querySelector('.customer-phone').textContent.toLowerCase();
                
                if (name.includes(filter) || phone.includes(filter)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            });

            let noCustomersFound = document.getElementById('noCustomersRow'); 
            let noSearchResults = document.getElementById('noSearchResults'); 
            
            if(noCustomersFound && noCustomersFound.style.display !== 'none') {
                return; 
            }

            if(hasResults) {
                noSearchResults.classList.add('hidden');
            } else {
                noSearchResults.classList.remove('hidden');
            }
        });
    </script>
</body>
</html>