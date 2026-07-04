<?php
// wishlist.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/image_upload.php';

$swal_icon = ""; $swal_title = ""; $swal_text = "";

// ૧. જો યુઝર લોગ-ઈન ન હોય તો સીધા લોગીન પેજ પર મોકલો
if (!isset($_SESSION['user_id'])) {
    header("Location: /auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ૨. Wishlist માંથી આઈટમ ડીલીટ કરવાનું લોજીક
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $w_id = isset($_GET['w_id']) ? $_GET['w_id'] : 0;
    if ($w_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE w_id = :w_id AND user_id = :uid");
            $stmt->execute([':w_id' => $w_id, ':uid' => $user_id]);
            
            $swal_icon = "success";
            $swal_title = "Removed!";
            $swal_text = "Item has been removed from your wishlist.";
        } catch (PDOException $e) {
            $swal_icon = "error"; $swal_title = "Error"; $swal_text = $e->getMessage();
        }
    }
}

// ૩. ડેટાબેઝમાંથી Wishlist ની બધી આઈટમ્સ લાવો (JOIN products)
try {
    $sql = "SELECT w.w_id, p.pid, p.product_name, p.price, p.image 
            FROM wishlist w 
            INNER JOIN products p ON w.product_id = p.pid 
            WHERE w.user_id = :uid 
            ORDER BY w.w_id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $user_id]);
    $wishlist_items = $stmt->fetchAll();

    // કાર્ટ કાઉન્ટ માટે (હેડરમાં બતાવવા)
    $stmt_cart = $pdo->prepare("SELECT COUNT(*) FROM carts WHERE user_id = :u_id");
    $stmt_cart->execute([':u_id' => $user_id]);
    $cart_count = $stmt_cart->fetchColumn();

} catch (PDOException $e) {
    $wishlist_items = [];
    $cart_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - RajaRam & Sons</title>
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
        <div class="flex items-center gap-3 mb-10 border-b border-gray-200 pb-5">
            <svg class="w-7 h-7 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path></svg>
            <div>
                <h4 class="text-brand-gold font-bold uppercase tracking-widest text-xs mb-1">Saved For Later</h4>
                <h2 class="text-3xl font-serif font-bold text-brand-dark">My Wishlist</h2>
            </div>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center max-w-xl mx-auto">
                <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6 text-red-300">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                </div>
                <h3 class="text-2xl font-serif font-bold text-brand-dark mb-3">Your Wishlist is Empty</h3>
                <p class="text-gray-500 mb-8 leading-relaxed">You haven't saved any instruments yet. Find something you love and tap the heart icon to save it here!</p>
                <a href="/index.php" class="inline-block bg-brand-dark text-white px-8 py-3.5 rounded-full font-bold hover:bg-brand-gold transition-colors shadow-md uppercase tracking-wider text-sm">
                    Discover Instruments
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col">
                        
                        <div class="h-48 bg-gray-50 relative p-4 flex items-center justify-center">
                            <a href="wishlist.php?action=delete&w_id=<?php echo $item['w_id']; ?>" class="absolute top-3 right-3 w-8 h-8 bg-white/80 backdrop-blur rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-white shadow-sm transition-all z-10" title="Remove from wishlist">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                            
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?php echo render_image_src($item['image']); ?>" class="max-h-full max-w-full object-contain mix-blend-multiply group-hover:scale-110 transition-transform duration-500">
                            <?php else: ?>
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"></path></svg>
                            <?php endif; ?>
                        </div>

                        <div class="p-6 flex flex-col flex-grow">
                            <h4 class="font-bold text-lg text-brand-dark mb-2 line-clamp-1"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            <p class="text-brand-gold font-black text-xl mb-6">₹<?php echo number_format($item['price'], 2); ?></p>
                            
                            <div class="mt-auto space-y-3">
                                <a href="add_to_cart.php?pid=<?php echo $item['pid']; ?>" class="block w-full text-center bg-brand-dark text-white font-bold py-3 rounded-full hover:bg-brand-gold transition-colors text-sm uppercase tracking-wide">
                                    Move to Cart
                                </a>
                                <a href="product_detail.php?id=<?php echo $item['pid']; ?>" class="block w-full text-center bg-white border border-gray-200 text-gray-600 font-bold py-2.5 rounded-full hover:bg-gray-50 transition-colors text-sm">
                                    View Details
                                </a>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
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
            confirmButtonColor: '#0A192F',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.location.href = 'wishlist.php'; // પેજ રીફ્રેશ કરો
        });
    </script>
    <?php endif; ?>

</body>
</html>