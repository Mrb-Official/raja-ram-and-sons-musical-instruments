<?php
// track_order.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/image_upload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

if (!isset($_GET['oid'])) {
    die("Order ID is missing!");
}

$oid = $_GET['oid'];
$user_id = $_SESSION['user_id'];

try {
    // ઓર્ડરની માહિતી લાવો
    $stmt = $pdo->prepare("SELECT o.*, p.product_name, p.image FROM orders o 
                           INNER JOIN products p ON o.product_id = p.pid 
                           WHERE o.oid = :oid AND o.uid = :uid");
    $stmt->execute([':oid' => $oid, ':uid' => $user_id]);
    $order = $stmt->fetch();

    if (!$order) { die("Order not found or access denied."); }

    // ટ્રેકિંગ હિસ્ટ્રી લાવો
    $stmt_history = $pdo->prepare("SELECT * FROM order_tracking WHERE order_id = :oid ORDER BY created_at ASC");
    $stmt_history->execute([':oid' => $oid]);
    $history = $stmt_history->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// આઇકોન અને કલર સેટ કરવાનું ફંક્શન
function getStatusUI($status) {
    $s = strtolower($status);
    if ($s == 'pending') return ['icon' => '⏳', 'color' => 'bg-amber-100 text-amber-600', 'border' => 'ring-amber-50'];
    if ($s == 'packed') return ['icon' => '📦', 'color' => 'bg-blue-100 text-blue-600', 'border' => 'ring-blue-50'];
    if ($s == 'shipped') return ['icon' => '🚚', 'color' => 'bg-indigo-100 text-indigo-600', 'border' => 'ring-indigo-50'];
    if ($s == 'delivered') return ['icon' => '🎉', 'color' => 'bg-emerald-100 text-emerald-600', 'border' => 'ring-emerald-50'];
    if ($s == 'cancelled') return ['icon' => '❌', 'color' => 'bg-red-100 text-red-600', 'border' => 'ring-red-50'];
    return ['icon' => '📌', 'color' => 'bg-slate-100 text-slate-600', 'border' => 'ring-slate-50'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Order - RajaRam & Sons</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 'dark': '#0A192F', 'gold': '#B7915F', 'hover': '#D4AF37', 'light': '#F8FAFC' }
                    },
                    fontFamily: { 'sans': ['Inter', 'sans-serif'], 'display': ['Playfair Display', 'serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-light font-sans antialiased text-slate-800 relative min-h-screen">

    <div class="absolute top-0 left-0 w-full h-80 bg-[#0A192F] z-0 overflow-hidden">
        <div class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23b7915f\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2v-4h4v-2H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
    </div>

    <div class="max-w-3xl mx-auto relative z-10 pt-10 pb-20 px-6">
        
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold font-serif text-white tracking-tight">Track Your Order</h1>
                <div class="flex items-center gap-3 mt-1.5">
                    <p class="text-sm text-slate-300 font-medium">Order ID: <span class="text-brand-hover font-bold">#ORD-<?php echo str_pad($order['oid'], 4, '0', STR_PAD_LEFT); ?></span></p>
                    <span class="inline-flex items-center gap-1 bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-widest">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        Verified
                    </span>
                </div>
            </div>
            <a href="user_dashboard.php" class="text-sm font-bold text-white hover:text-brand-dark hover:bg-white bg-white/10 border border-white/20 px-4 py-2 rounded-lg backdrop-blur-sm transition-all shadow-sm">← Dashboard</a>
        </div>

        <div class="bg-white p-8 md:p-10 rounded-2xl shadow-[0_20px_50px_rgba(10,25,47,0.12)] border border-slate-100">
            
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 border-b border-slate-100 pb-6 mb-8 text-center sm:text-left">
                <div class="w-20 h-20 bg-slate-50 rounded-xl overflow-hidden border border-slate-200 flex-shrink-0 shadow-sm p-1">
                    <?php if(!empty($order['image'])): ?>
                        <img src="<?php echo render_image_src($order['image']); ?>" class="w-full h-full object-cover rounded-lg">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-2xl">🎸</div>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <h3 class="font-extrabold text-xl text-brand-dark"><?php echo htmlspecialchars($order['product_name']); ?></h3>
                    <p class="text-sm text-slate-500 font-medium mt-1">Total Paid: <span class="text-brand-dark font-extrabold text-lg">₹<?php echo number_format($order['total_price'], 2); ?></span></p>
                </div>
            </div>

            <?php if(!empty($order['tracking_no'])): ?>
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100/50 rounded-xl p-6 mb-10 flex flex-col sm:flex-row justify-between items-center gap-4 shadow-inner">
                    <div class="text-center sm:text-left">
                        <p class="text-[11px] font-bold text-blue-500 uppercase tracking-widest mb-1.5">Courier Tracking Number</p>
                        <div class="flex items-center justify-center sm:justify-start gap-3">
                            <p class="text-xl font-black text-blue-900 tracking-widest" id="tracking-number"><?php echo htmlspecialchars($order['tracking_no']); ?></p>
                            <button onclick="copyTracking()" class="text-blue-600 hover:text-white hover:bg-blue-600 bg-white p-2 rounded shadow-sm border border-blue-200 transition-all" title="Copy">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </button>
                        </div>
                    </div>
                    <?php if(!empty($order['courier_website'])): ?>
                        <a href="<?php echo htmlspecialchars($order['courier_website']); ?>" target="_blank" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold shadow-md shadow-blue-600/20 hover:bg-blue-700 hover:-translate-y-0.5 transition-all text-sm w-full sm:w-auto text-center flex items-center justify-center gap-2">
                            Track Courier <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="flex items-center gap-3 mb-8">
                <svg class="w-5 h-5 text-brand-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h4 class="text-sm font-bold text-brand-dark uppercase tracking-widest">Order Updates</h4>
            </div>
            
            <?php if(empty($history)): ?>
                <div class="text-center p-8 bg-slate-50 rounded-xl text-slate-500 font-medium border border-dashed border-slate-300">
                    Your order has just been placed. Updates will appear here shortly! ⏳
                </div>
            <?php else: ?>
                <div class="relative border-l-2 border-brand-gold/30 ml-6 pl-8 space-y-10 pb-4">
                    
                    <?php foreach($history as $index => $track): 
                        $ui = getStatusUI($track['status']);
                        // છેલ્લું સ્ટેટસ હાઈલાઈટ કરવા માટે
                        $is_last = ($index === array_key_last($history)); 
                    ?>
                        <div class="relative group">
                            <span class="absolute flex items-center justify-center w-10 h-10 rounded-full -left-[3.25rem] ring-[6px] ring-white <?php echo $ui['color']; ?> <?php echo $is_last ? 'shadow-md scale-110 z-10' : 'opacity-80 group-hover:scale-110'; ?> transition-all duration-300">
                                <?php echo $ui['icon']; ?>
                            </span>
                            
                            <div class="bg-white <?php echo $is_last ? 'bg-slate-50/50 -mt-2 p-4 rounded-xl border border-slate-100' : ''; ?> transition-all">
                                <h3 class="flex items-center mb-1 text-lg font-bold <?php echo $is_last ? 'text-[#0A192F]' : 'text-slate-600'; ?>">
                                    <?php echo ucfirst(htmlspecialchars($track['status'])); ?>
                                    <?php if($is_last): ?>
                                        <span class="bg-emerald-100 text-emerald-700 text-[10px] font-bold me-2 px-2.5 py-0.5 rounded ml-3 uppercase tracking-wider animate-pulse">Current</span>
                                    <?php endif; ?>
                                </h3>
                                <time class="block mb-2 text-xs font-bold text-slate-400 tracking-wide uppercase">
                                    <?php echo date('d M Y • h:i A', strtotime($track['created_at'])); ?>
                                </time>
                                
                                <p class="text-sm font-medium leading-relaxed <?php echo $is_last ? 'text-slate-700' : 'text-slate-500'; ?>">
                                    <?php echo htmlspecialchars($track['message']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function copyTracking() {
            var trackingNumber = document.getElementById("tracking-number").innerText;
            navigator.clipboard.writeText(trackingNumber).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Tracking Number Copied!',
                    showConfirmButton: false,
                    timer: 2000,
                    iconColor: '#B7915F'
                });
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }
    </script>

</body>
</html>