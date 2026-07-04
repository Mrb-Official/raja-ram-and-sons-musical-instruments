<?php
// admin_dashboard.php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /auth/admin_login.php");
    exit;
}

try {
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $products_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $orders_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    $online_rev = $pdo->query("SELECT SUM(total_price) FROM orders WHERE LOWER(status) = 'delivered'")->fetchColumn();
    $offline_rev = $pdo->query("SELECT SUM(sum_total) FROM offline_sales")->fetchColumn();
    $total_revenue = ($online_rev ?: 0) + ($offline_rev ?: 0);

    $recent_orders = $pdo->query("SELECT o.oid, u.u_name, o.payment_method, o.total_price, o.status 
                                  FROM orders o 
                                  LEFT JOIN users u ON o.uid = u.u_id 
                                  ORDER BY o.oid DESC LIMIT 5")->fetchAll();

    $recent_pos = $pdo->query("SELECT offline_sales_id, buyer_name, sum_total 
                               FROM offline_sales 
                               ORDER BY offline_sales_id DESC LIMIT 5")->fetchAll();
                               
    $low_stock_products = $pdo->query("SELECT pid, product_name, stock_quantity FROM products WHERE stock_quantity <= 5 ORDER BY stock_quantity ASC")->fetchAll();

} catch (PDOException $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RajaRam & Sons</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto bg-gradient-to-br from-[#0A192F] via-[#162A4A] to-[#B7915F] relative z-10">
        
        <?php include '../includes/admin_topbar.php'; ?>

        <div class="p-8 max-w-7xl mx-auto w-full">
            
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                <div class="bg-white/95 backdrop-blur-sm p-6 rounded-2xl shadow-lg border border-white/40 flex items-center justify-between border-l-4 border-l-blue-500 hover:-translate-y-1 transition-transform">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Users</p>
                        <h3 class="text-3xl font-black text-[#0A192F]"><?php echo number_format($users_count); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-500 flex items-center justify-center text-xl shadow-inner">👤</div>
                </div>

                <div class="bg-white/95 backdrop-blur-sm p-6 rounded-2xl shadow-lg border border-white/40 flex items-center justify-between border-l-4 border-l-orange-500 hover:-translate-y-1 transition-transform">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Products</p>
                        <h3 class="text-3xl font-black text-[#0A192F]"><?php echo number_format($products_count); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center text-xl shadow-inner">🎸</div>
                </div>

                <div class="bg-white/95 backdrop-blur-sm p-6 rounded-2xl shadow-lg border border-white/40 flex items-center justify-between border-l-4 border-l-purple-500 hover:-translate-y-1 transition-transform">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Orders</p>
                        <h3 class="text-3xl font-black text-[#0A192F]"><?php echo number_format($orders_count); ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-purple-50 text-purple-500 flex items-center justify-center text-xl shadow-inner">📦</div>
                </div>

                <div class="bg-white/95 backdrop-blur-sm p-6 rounded-2xl shadow-lg border border-white/40 flex items-center justify-between border-l-4 border-l-emerald-500 relative overflow-hidden hover:-translate-y-1 transition-transform">
                    <div class="absolute -right-4 -bottom-4 opacity-10 text-6xl">💰</div>
                    <div class="relative z-10">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Revenue (Total)</p>
                        <h3 class="text-3xl font-black text-[#0A192F]">₹<?php echo number_format($total_revenue, 2); ?></h3>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($low_stock_products)): ?>
            <div class="mb-8 bg-red-50/95 backdrop-blur-sm border border-red-200 rounded-2xl p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-4 border-b border-red-200 pb-3">
                    <div class="w-10 h-10 bg-red-100 text-red-600 rounded-full flex justify-center items-center text-lg animate-pulse">⚠️</div>
                    <div>
                        <h3 class="text-lg font-bold text-red-700">Low Stock Warning!</h3>
                        <p class="text-xs text-red-500 font-semibold uppercase tracking-wider">Please reorder the following instruments soon</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach($low_stock_products as $lsp): ?>
                    <div class="bg-white p-4 rounded-xl border border-red-100 flex justify-between items-center shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex-1 pr-2">
                            <p class="font-bold text-slate-800 line-clamp-1" title="<?php echo htmlspecialchars($lsp['product_name']); ?>"><?php echo htmlspecialchars($lsp['product_name']); ?></p>
                            <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-widest">ID: #<?php echo str_pad($lsp['pid'], 3, '0', STR_PAD_LEFT); ?></p>
                        </div>
                        
                        <div class="text-right shrink-0">
                            <?php if($lsp['stock_quantity'] == 0): ?>
                                <span class="bg-red-50 text-red-600 border border-red-200 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest whitespace-nowrap shadow-sm">Out of Stock</span>
                            <?php else: ?>
                                <span class="bg-amber-50 text-amber-600 border border-amber-200 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest whitespace-nowrap shadow-sm">Only <?php echo $lsp['stock_quantity']; ?> Left</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 pb-10">
                
                <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg border border-white/40 overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                        <h3 class="font-bold text-[#0A192F] text-lg flex items-center gap-2">🌐 Online Orders</h3>
                        <a href="manage_orders.php" class="text-xs font-bold text-[#B7915F] hover:text-[#0A192F] uppercase tracking-wider transition-colors border border-[#B7915F]/30 px-3 py-1 rounded bg-[#B7915F]/10">View All</a>
                    </div>
                    <div class="overflow-x-auto flex-1 p-2">
                        <table class="w-full text-left border-collapse min-w-max">
                            <thead>
                                <tr class="bg-white text-xs uppercase font-bold text-slate-400 tracking-wider border-b border-slate-100">
                                    <th class="p-4">Order ID</th>
                                    <th class="p-4">Customer</th>
                                    <th class="p-4 text-right">Amount</th>
                                    <th class="p-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php if(empty($recent_orders)): ?>
                                    <tr><td colspan="4" class="p-8 text-center text-slate-500 font-medium">No recent online orders.</td></tr>
                                <?php else: ?>
                                    <?php foreach($recent_orders as $ro): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 font-bold text-[#0A192F]">#ORD-<?php echo str_pad($ro['oid'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td class="p-4 font-medium text-slate-700"><?php echo htmlspecialchars($ro['u_name'] ?? 'Unknown'); ?></td>
                                        <td class="p-4 font-extrabold text-[#B7915F] text-right">₹<?php echo number_format($ro['total_price'], 2); ?></td>
                                        <td class="p-4 text-center">
                                            <?php if(strtolower($ro['status']) == 'delivered'): ?>
                                                <span class="bg-emerald-50 border border-emerald-200 text-emerald-600 px-3 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider">Delivered</span>
                                            <?php elseif(strtolower($ro['status']) == 'pending'): ?>
                                                <span class="bg-amber-50 border border-amber-200 text-amber-600 px-3 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider">Pending</span>
                                            <?php else: ?>
                                                <span class="bg-slate-50 border border-slate-200 text-slate-600 px-3 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider"><?php echo htmlspecialchars(ucfirst($ro['status'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-lg border border-white/40 overflow-hidden flex flex-col">
                    <div class="px-6 py-5 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                        <h3 class="font-bold text-[#0A192F] text-lg flex items-center gap-2">🏬 Offline POS Sales</h3>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Latest 5 Bills</span>
                    </div>
                    <div class="overflow-x-auto flex-1 p-2">
                        <table class="w-full text-left border-collapse min-w-max">
                            <thead>
                                <tr class="bg-white text-xs uppercase font-bold text-slate-400 tracking-wider border-b border-slate-100">
                                    <th class="p-4">Bill No</th>
                                    <th class="p-4">Walk-in Customer</th>
                                    <th class="p-4 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php if(empty($recent_pos)): ?>
                                    <tr><td colspan="3" class="p-8 text-center text-slate-500 font-medium">No recent offline sales.</td></tr>
                                <?php else: ?>
                                    <?php foreach($recent_pos as $pos): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 font-bold text-[#0A192F]">
                                            <a href="/billing/offline_invoice.php?sale_id=<?php echo $pos['offline_sales_id']; ?>" target="_blank" class="hover:text-blue-600 hover:underline flex items-center gap-1">
                                                #POS-<?php echo str_pad($pos['offline_sales_id'], 4, '0', STR_PAD_LEFT); ?> ↗
                                            </a>
                                        </td>
                                        <td class="p-4 font-medium text-slate-700"><?php echo htmlspecialchars($pos['buyer_name']); ?></td>
                                        <td class="p-4 font-extrabold text-[#162A4A] text-right">₹<?php echo number_format($pos['sum_total'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>