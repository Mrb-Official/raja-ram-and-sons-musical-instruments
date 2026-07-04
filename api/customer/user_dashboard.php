<?php
// user_dashboard.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/image_upload.php';

// જો યુઝર લોગ-ઈન ન હોય તો સીધા લોગીન પેજ પર મોકલો
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // ૧. યુઝરની સંપૂર્ણ માહિતી લાવો
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE u_id = :u_id");
    $stmt_user->execute([':u_id' => $user_id]);
    $user = $stmt_user->fetch();

    // ૨. યુઝરના કરેલા ઓર્ડર્સ લાવો (અહીં tracking_no ઉમેર્યું છે)
    $sql_orders = "SELECT o.oid, o.quantity, o.total_price, o.status, o.payment_method, o.tracking_no, 
                          p.product_name, p.image, 
                          osa.full_address 
                   FROM orders o 
                   INNER JOIN products p ON o.product_id = p.pid 
                   LEFT JOIN order_shipping_addresses osa ON o.oid = osa.order_id 
                   WHERE o.uid = :uid 
                   ORDER BY o.oid DESC";
                   
    $stmt_orders = $pdo->prepare($sql_orders);
    $stmt_orders->execute([':uid' => $user_id]);
    $orders = $stmt_orders->fetchAll();

    // ૩. કાર્ટમાં કેટલી આઈટમ છે તે જાણવા
    $stmt_cart = $pdo->prepare("SELECT COUNT(*) FROM carts WHERE user_id = :u_id");
    $stmt_cart->execute([':u_id' => $user_id]);
    $cart_count = $stmt_cart->fetchColumn();

    // ૪. કુલ કેટલા ઓર્ડર કર્યા છે
    $total_orders = is_array($orders) ? count($orders) : 0;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - RajaRam & Sons</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], serif: ['Playfair Display', 'serif'] },
                    colors: {
                        brand: { dark: '#0A192F', gold: '#B7915F', hover: '#D4AF37', light: '#F8FAFC' }
                    },
                    animation: { 'fade-in-up': 'fadeInUp 0.6s ease-out forwards' },
                    keyframes: { fadeInUp: { '0%': { opacity: 0, transform: 'translateY(20px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } } }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-light font-sans antialiased text-gray-800 flex flex-col min-h-screen">

    <?php include '../includes/header.php'; ?>

    <main class="max-w-7xl mx-auto px-6 py-10 grid grid-cols-1 lg:grid-cols-4 gap-10 items-start flex-1 w-full">
        
        <div class="lg:col-span-1 bg-white rounded-3xl shadow-[0_15px_40px_rgba(10,25,47,0.06)] border border-gray-100 p-8 animate-fade-in-up">
            <div class="text-center border-b border-gray-100 pb-8 mb-8 relative">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-24 h-24 bg-brand-gold/20 rounded-full blur-xl"></div>
                
                <div class="relative w-28 h-28 mx-auto bg-gradient-to-tr from-brand-dark to-[#162A4A] rounded-full border-4 border-white shadow-xl flex items-center justify-center text-4xl font-extrabold text-brand-hover mb-4 font-serif">
                    <?php echo strtoupper(substr($user['u_name'], 0, 1)); ?>
                </div>
                
                <h2 class="text-2xl font-bold text-brand-dark font-serif"><?php echo htmlspecialchars($user['u_name']); ?></h2>
                <p class="text-brand-gold font-semibold text-sm mt-1 uppercase tracking-widest">Premium Member</p>
            </div>

            <div class="space-y-6 text-sm">
                <div>
                    <p class="text-gray-400 font-bold uppercase tracking-wider text-xs mb-1">Email Address</p>
                    <p class="text-gray-700 font-medium flex items-center gap-2"><svg class="w-4 h-4 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg> <?php echo htmlspecialchars($user['email_id']); ?></p>
                </div>
                <div>
                    <p class="text-gray-400 font-bold uppercase tracking-wider text-xs mb-1">Mobile Number</p>
                    <p class="text-gray-700 font-medium flex items-center gap-2"><svg class="w-4 h-4 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg> +91 <?php echo htmlspecialchars($user['mobile_number']); ?></p>
                </div>
            </div>
            
            <a href="/index.php" class="mt-10 block w-full bg-gray-50 border border-gray-200 text-brand-dark text-center py-3 rounded-xl font-bold hover:bg-brand-dark hover:text-white transition-all duration-300">
                Explore Instruments
            </a>
        </div>

        <div class="lg:col-span-3 space-y-10">
            
            <div class="bg-gradient-to-r from-brand-dark via-[#162A4A] to-brand-dark rounded-3xl p-10 shadow-2xl relative overflow-hidden animate-fade-in-up" style="animation-delay: 100ms;">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23b7915f\' fill-opacity=\'0.1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2v-4h4v-2H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <p class="text-brand-hover font-bold tracking-widest uppercase text-sm mb-2">Welcome Back to the Symphony</p>
                        <h2 class="text-3xl md:text-5xl font-extrabold font-serif text-white drop-shadow-lg">Hello, <?php echo htmlspecialchars($user['u_name']); ?>! 🎶</h2>
                    </div>
                    <div class="flex gap-4">
                        <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 text-center min-w-[120px]">
                            <p class="text-4xl font-extrabold text-brand-hover font-serif"><?php echo $total_orders; ?></p>
                            <p class="text-white/80 text-xs font-bold uppercase tracking-wider mt-1">Total Orders</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-[0_15px_40px_rgba(10,25,47,0.04)] border border-brand-light p-8 animate-fade-in-up" style="animation-delay: 200ms;">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h3 class="text-2xl font-extrabold font-serif text-brand-dark">My Orders History</h3>
                        <p class="text-slate-500 text-sm mt-1">Track your recent musical purchases</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-max">
                        <thead>
                            <tr class="bg-slate-50 border-y border-slate-100 text-slate-500 text-xs uppercase tracking-widest font-bold">
                                <th class="p-5 rounded-tl-xl rounded-bl-xl">Product</th>
                                <th class="p-5">Order ID & Tracking</th>
                                <th class="p-5">Shipping Address</th>
                                <th class="p-5 text-center">Total Amount</th>
                                <th class="p-5 text-center">Status</th>
                                <th class="p-5 text-center rounded-tr-xl rounded-br-xl">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="p-16 text-center">
                                        <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-50 rounded-full text-slate-300 mb-4">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                        </div>
                                        <p class="text-slate-500 font-bold text-lg">No orders found.</p>
                                        <p class="text-slate-400 text-sm mt-1 mb-6">You haven't placed any orders yet.</p>
                                        <a href="/index.php" class="text-brand-gold font-bold hover:underline">Start Shopping →</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <td class="p-5">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 shrink-0">
                                                    <?php if (!empty($order['image'])): ?>
                                                        <img src="<?php echo render_image_src($order['image']); ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-slate-400">🎶</div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-brand-dark line-clamp-1 max-w-[150px]"><?php echo htmlspecialchars($order['product_name']); ?></p>
                                                    <p class="text-xs text-brand-gold font-bold mt-0.5">Qty: <?php echo $order['quantity']; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="p-5">
                                            <p class="font-bold text-[#162A4A]">#ORD-<?php echo str_pad($order['oid'], 4, '0', STR_PAD_LEFT); ?></p>
                                            <p class="text-xs text-slate-500 mt-0.5 font-semibold mb-2"><?php echo htmlspecialchars($order['payment_method']); ?></p>
                                            
                                            <?php if(!empty($order['tracking_no'])): ?>
                                                <div class="inline-flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-md px-2.5 py-1">
                                                    <span class="text-xs font-bold text-blue-700 tracking-widest" id="track-<?php echo $order['oid']; ?>">
                                                        <?php echo htmlspecialchars($order['tracking_no']); ?>
                                                    </span>
                                                    <button onclick="copyTracking('track-<?php echo $order['oid']; ?>')" class="text-blue-500 hover:text-blue-800 transition-colors" title="Copy Tracking Number">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="p-5 text-sm text-slate-500 max-w-[200px] truncate" title="<?php echo htmlspecialchars($order['full_address'] ?? 'Address pending'); ?>">
                                            <?php echo htmlspecialchars($order['full_address'] ?? 'No address found'); ?>
                                        </td>
                                        
                                        <td class="p-5 text-center">
                                            <span class="font-extrabold text-[#162A4A]">₹<?php echo number_format($order['total_price'], 2); ?></span>
                                        </td>
                                        
                                        <td class="p-5 text-center">
                                            <?php if (strtolower($order['status']) == 'pending'): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-600 border border-amber-200 uppercase tracking-wider">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span> Pending
                                                </span>
                                            <?php elseif (strtolower($order['status']) == 'delivered'): ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 uppercase tracking-wider">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Delivered
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wider">
                                                    <?php echo htmlspecialchars(ucfirst($order['status'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="p-5 text-center">
                                            <a href="track_order.php?oid=<?php echo $order['oid']; ?>" class="inline-flex items-center justify-center gap-1.5 bg-[#162A4A] text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-[#B7915F] transition-colors shadow-sm uppercase tracking-wide">
                                                📍 Track
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        function copyTracking(elementId) {
            var trackingText = document.getElementById(elementId).innerText.trim();
            navigator.clipboard.writeText(trackingText).then(function() {
                // SweetAlert Toast Notification
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Copied: ' + trackingText,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                    iconColor: '#B7915F'
                });
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>