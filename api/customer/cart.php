<?php
// cart.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/image_upload.php';

$swal_icon = ""; $swal_title = ""; $swal_text = "";

// ૧. જો યુઝર લોગ-ઈન ન હોય તો તેને સીધા લોગીન પેજ પર મોકલી દો
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ૨. કાર્ટની અંદર એક્શન મેનેજમેન્ટ (Delete, Increase, Decrease Quantity)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $cart_id = isset($_GET['cart_id']) ? $_GET['cart_id'] : 0;

    if ($cart_id > 0) {
        try {
            if ($action === 'delete') {
                // આઈટમ કાર્ટમાંથી ડીલીટ કરો (user_id સુધાર્યું)
                $stmt = $pdo->prepare("DELETE FROM carts WHERE cart_id = :cart_id AND user_id = :u_id");
                $stmt->execute([':cart_id' => $cart_id, ':u_id' => $user_id]);
                
                $swal_icon = "success";
                $swal_title = "Removed!";
                $swal_text = "Item has been removed from your cart.";
            } 
            elseif ($action === 'increase') {
                // ક્વોન્ટિટી ૧ વધારો (user_id સુધાર્યું)
                $stmt = $pdo->prepare("UPDATE carts SET quantity = quantity + 1 WHERE cart_id = :cart_id AND user_id = :u_id");
                $stmt->execute([':cart_id' => $cart_id, ':u_id' => $user_id]);
                header("Location: /customer/cart.php");
                exit;
            } 
            elseif ($action === 'decrease') {
                // ચેક કરો કે ક્વોન્ટિટી ૧ કરતા મોટી છે કે નહિ (user_id સુધાર્યું)
                $stmt_chk = $pdo->prepare("SELECT quantity FROM carts WHERE cart_id = :cart_id AND user_id = :u_id");
                $stmt_chk->execute([':cart_id' => $cart_id, ':u_id' => $user_id]);
                $current_qty = $stmt_chk->fetchColumn();

                if ($current_qty > 1) {
                    // ક્વોન્ટિટી ૧ ઓછી કરો
                    $stmt = $pdo->prepare("UPDATE carts SET quantity = quantity - 1 WHERE cart_id = :cart_id AND user_id = :u_id");
                    $stmt->execute([':cart_id' => $cart_id, ':u_id' => $user_id]);
                } else {
                    // જો ૧ જ હોય અને માઇનસ કરે તો સીધું ડીલીટ કરો
                    $stmt = $pdo->prepare("DELETE FROM carts WHERE cart_id = :cart_id AND user_id = :u_id");
                    $stmt->execute([':cart_id' => $cart_id, ':u_id' => $user_id]);
                }
                header("Location: /customer/cart.php");
                exit;
            }
        } catch (PDOException $e) {
            $swal_icon = "error";
            $swal_title = "Error";
            $swal_text = $e->getMessage();
        }
    }
}

// ૩. ડેટાબેઝમાંથી લૉગ્ડ-ઇન યુઝરની બધી કાર્ટ આઈટમ્સ લાવો (JOIN products)
// (અહીં JOIN માં product_id અને WHERE માં user_id સેટ કર્યું છે)
try {
    $sql = "SELECT c.cart_id, c.quantity, p.pid, p.product_name, p.price, p.image 
            FROM carts c 
            INNER JOIN products p ON c.product_id = p.pid 
            WHERE c.user_id = :u_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':u_id' => $user_id]);
    $cart_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $cart_items = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - RajaRam & Sons</title>
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
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-light text-brand-dark font-sans antialiased flex flex-col min-h-screen">

    <?php include '../includes/header.php'; ?>

    <section class="max-w-7xl mx-auto px-6 py-12 flex-1 w-full">
        <div class="mb-10 border-b border-gray-200 pb-5">
            <h4 class="text-brand-gold font-bold uppercase tracking-widest text-xs mb-1">Review Your Selection</h4>
            <h2 class="text-3xl font-serif font-bold text-brand-dark">Your Shopping Cart</h2>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center max-w-xl mx-auto">
                <div class="w-24 h-24 bg-brand-light rounded-full flex items-center justify-center mx-auto mb-6 text-brand-gold">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <h3 class="text-2xl font-serif font-bold text-brand-dark mb-3">Your Cart is Empty</h3>
                <p class="text-gray-500 mb-8 leading-relaxed">Looks like you haven't added any musical instruments to your cart yet. Explore our royal collections now!</p>
                <a href="/index.php" class="bg-brand-dark text-white px-8 py-3.5 rounded-full font-bold hover:bg-brand-gold transition-colors shadow-md uppercase tracking-wider text-sm">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
                
                <div class="lg:col-span-2 space-y-6">
                    <?php 
                    $subtotal = 0;
                    foreach ($cart_items as $item): 
                        $item_total = $item['price'] * $item['quantity'];
                        $subtotal += $item_total;
                    ?>
                        <div class="bg-white rounded-2xl border border-brand-light p-6 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-5 w-full sm:w-auto">
                                <div class="w-20 h-20 bg-brand-light/50 rounded-xl overflow-hidden flex items-center justify-center border border-brand-light p-1 shrink-0">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="<?php echo render_image_src($item['image']); ?>" class="w-full h-full object-cover rounded-lg">
                                    <?php else: ?>
                                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"></path></svg>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg text-brand-dark mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <p class="text-brand-gold font-semibold text-sm">₹<?php echo number_format($item['price'], 2); ?> each</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-8 w-full sm:w-auto border-t sm:border-none pt-4 sm:pt-0">
                                <div class="flex items-center border border-slate-200 rounded-lg bg-slate-50 overflow-hidden shadow-sm">
                                    <a href="cart.php?action=decrease&cart_id=<?php echo $item['cart_id']; ?>" class="px-3 py-1.5 hover:bg-slate-200 font-bold text-slate-600 transition-colors">-</a>
                                    <span class="px-4 py-1.5 bg-white text-slate-800 font-bold text-sm min-w-[40px] text-center"><?php echo $item['quantity']; ?></span>
                                    <a href="cart.php?action=increase&cart_id=<?php echo $item['cart_id']; ?>" class="px-3 py-1.5 hover:bg-slate-200 font-bold text-slate-600 transition-colors">+</a>
                                </div>

                                <div class="text-right min-w-[100px]">
                                    <p class="font-extrabold text-lg text-brand-dark font-serif">₹<?php echo number_format($item_total, 2); ?></p>
                                </div>

                                <a href="cart.php?action=delete&cart_id=<?php echo $item['cart_id']; ?>" class="text-red-400 hover:text-red-600 transition-colors p-2 bg-red-50 hover:bg-red-100 rounded-lg" title="Remove item">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bg-white rounded-2xl border border-brand-light p-8 shadow-sm space-y-6">
                    <h3 class="text-xl font-bold text-brand-dark pb-4 border-b border-brand-light">Order Summary</h3>
                    
                    <div class="space-y-4 text-sm font-medium text-gray-500">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="text-brand-dark font-bold">₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Shipping Charges</span>
                            <span class="text-green-600 font-bold">FREE Delivery</span>
                        </div>
                        <div class="border-t border-brand-light pt-4 flex justify-between text-lg font-extrabold text-brand-dark">
                            <span>Total Amount</span>
                            <span class="text-brand-gold font-serif">₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                    </div>

                    <a href="checkout.php" class="w-full bg-gradient-to-r from-brand-gold to-brand-hover text-brand-dark font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transition-all block text-center uppercase tracking-wider text-sm transform hover:-translate-y-0.5">
                        Proceed to Checkout
                    </a>
                    
                    <a href="/index.php" class="w-full bg-white border border-slate-200 text-slate-600 font-bold py-3 rounded-xl transition-all block text-center text-sm hover:bg-slate-50">
                        Continue Shopping
                    </a>
                </div>

            </div>
        <?php endif; ?>
    </section>

    <?php include '../includes/footer.php'; ?>

    <?php if (!empty($swal_icon)): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $swal_icon; ?>',
            title: '<?php echo $swal_title; ?>',
            text: '<?php echo $swal_text; ?>',
            confirmButtonColor: '#0A192F'
        }).then(() => {
            window.location.href = 'cart.php'; 
        });
    </script>
    <?php endif; ?>

</body>
</html>