<?php
// product_detail.php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/image_upload.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: /index.php");
    exit;
}

$product_id = $_GET['id'];

// નવો રિવ્યુ સેવ કરવાનું લોજીક (જો ફોર્મ સબમિટ કર્યું હોય તો)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (isset($_SESSION['user_id'])) {
        $rating = $_POST['rating'];
        $review_text = $_POST['review_text'];
        
        $stmt_ins = $pdo->prepare("INSERT INTO product_reviews (pid, user_id, rating, review_text) VALUES (:pid, :uid, :rating, :text)");
        $stmt_ins->execute([
            ':pid' => $product_id,
            ':uid' => $_SESSION['user_id'],
            ':rating' => $rating,
            ':text' => $review_text
        ]);
        // પેજ રિફ્રેશ કરો જેથી નવો રિવ્યુ દેખાય
        header("Location: /customer/product_detail.php?id=" . $product_id . "#reviews");
        exit;
    }
}

// મેઈન પ્રોડક્ટની માહિતી લાવો
try {
    $stmt = $pdo->prepare("SELECT p.*, c.category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.catid = c.c_id 
                           WHERE p.pid = :id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        header("Location: /index.php");
        exit;
    }

    // રિવ્યુ લાવો અને એવરેજ ગણો
    $stmt_rev = $pdo->prepare("SELECT r.*, u.u_name FROM product_reviews r LEFT JOIN users u ON r.user_id = u.u_id WHERE r.pid = :pid ORDER BY r.review_id DESC");
    $stmt_rev->execute([':pid' => $product_id]);
    $reviews = $stmt_rev->fetchAll();
    
    $total_reviews = count($reviews);
    $avg_rating = 0;
    if ($total_reviews > 0) {
        $sum = 0;
        foreach($reviews as $rev) { $sum += $rev['rating']; }
        $avg_rating = round($sum / $total_reviews, 1);
    }

    // Related Products
    $stmt_related = $pdo->prepare("SELECT p.*, IFNULL(AVG(r.rating), 0) as avg_rating, COUNT(r.review_id) as review_count FROM products p LEFT JOIN product_reviews r ON p.pid = r.pid WHERE p.catid = :catid AND p.pid != :pid GROUP BY p.pid ORDER BY RAND() LIMIT 4");
    $stmt_related->execute([':catid' => $product['catid'], ':pid' => $product['pid']]);
    $related_products = $stmt_related->fetchAll();

} catch (Exception $e) {
    die("Error loading product details.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - RajaRam & Sons</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], serif: ['Playfair Display', 'serif'] },
                    colors: { brand: { dark: '#0A192F', gold: '#B7915F', hover: '#D4AF37', light: '#F8FAFC' } }
                }
            }
        }
    </script>
</head>
<body class="bg-brand-light font-sans text-gray-800 flex flex-col min-h-screen">

    <?php include '../includes/header.php'; ?>

    <main class="max-w-[1400px] mx-auto w-full px-6 py-8 flex-1">
        
        <nav class="flex text-sm text-gray-500 font-medium mb-6">
            <a href="/index.php" class="hover:text-brand-gold">Home</a>
            <span class="mx-2">/</span>
            <a href="search.php?category=<?php echo $product['catid']; ?>" class="hover:text-brand-gold"><?php echo htmlspecialchars($product['category_name'] ?? 'Category'); ?></a>
            <span class="mx-2">/</span>
            <span class="text-brand-dark font-bold truncate max-w-xs"><?php echo htmlspecialchars($product['product_name']); ?></span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-10 bg-white p-8 rounded-3xl shadow-sm border border-gray-100 mb-16">
            
            <div class="w-full lg:w-5/12 flex gap-4">
                <div class="hidden md:flex flex-col gap-3 w-20 shrink-0">
                    <div class="w-full aspect-square border-2 border-brand-gold rounded-lg p-1 cursor-pointer">
                        <img src="<?php echo render_image_src($product['image']); ?>" class="w-full h-full object-contain">
                    </div>
                </div>
                <div class="flex-1 bg-gray-50 border border-gray-100 rounded-2xl p-8 relative flex items-center justify-center min-h-[400px] overflow-hidden">
                    <?php if(!empty($product['image'])): ?>
                        <img src="<?php echo render_image_src($product['image']); ?>" class="w-full max-w-md object-contain mix-blend-multiply">
                    <?php else: ?>
                        <span class="text-9xl text-gray-200">🎵</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="w-full lg:w-4/12 flex flex-col">
                <span class="text-xs uppercase font-black tracking-widest text-brand-gold mb-2"><?php echo htmlspecialchars($product['category_name'] ?? 'Instrument'); ?></span>
                <h1 class="text-2xl md:text-3xl font-black text-brand-dark leading-tight mb-2"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex text-lg">
                        <?php 
                        $star_rating = round($avg_rating);
                        for($i=1; $i<=5; $i++) {
                            echo ($i <= $star_rating) ? '<span class="text-yellow-400">★</span>' : '<span class="text-gray-300">★</span>';
                        }
                        ?>
                    </div>
                    <a href="#reviews" class="text-sm font-bold text-brand-gold hover:underline"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> Reviews)</a>
                </div>
                
                <div class="flex items-end gap-3 mb-6 border-b border-gray-100 pb-6">
                    <p class="text-4xl font-black text-[#B12704]">₹<?php echo number_format($product['price']); ?></p>
                </div>

                <div class="mb-8">
                    <h3 class="font-bold text-brand-dark mb-3">About this instrument:</h3>
                    <ul class="list-disc pl-5 space-y-2 text-gray-600 text-sm">
                        <?php 
                        $features = explode("\n", $product['description']);
                        $count = 0;
                        foreach($features as $feature): 
                            if(trim($feature) != '' && $count < 5): 
                        ?>
                            <li><?php echo htmlspecialchars(trim($feature)); ?></li>
                        <?php $count++; endif; endforeach; ?>
                        <li><strong>Quality:</strong> 100% Handcrafted & Tested</li>
                    </ul>
                </div>
            </div>

            <div class="w-full lg:w-3/12">
                <div class="bg-white border-2 border-brand-gold/20 rounded-2xl p-6 shadow-sm">
                    <p class="text-2xl font-black text-brand-dark mb-2">₹<?php echo number_format($product['price']); ?></p>
                    <p class="text-sm text-gray-500 mb-4">FREE delivery & Returns available.</p>

                    <p class="font-bold text-lg mb-4 <?php echo ($product['stock_quantity'] > 0) ? (($product['stock_quantity'] <= 5) ? 'text-orange-500' : 'text-emerald-600') : 'text-red-600'; ?>">
                        <?php 
                            if($product['stock_quantity'] == 0) echo 'Out of Stock';
                            elseif($product['stock_quantity'] <= 5) echo "Hurry! Only {$product['stock_quantity']} left";
                            else echo 'In Stock'; 
                        ?>
                    </p>
                    
                    <form action="add_to_cart.php" method="POST" class="space-y-4">
                        <input type="hidden" name="pid" value="<?php echo $product['pid']; ?>">
                        <?php if($product['stock_quantity'] > 0): ?>
                            <div class="flex items-center gap-4 mb-4">
                                <label class="font-bold text-sm text-gray-700">Qty:</label>
                                <div class="flex items-center border border-gray-300 rounded overflow-hidden h-9 w-24">
                                    <button type="button" onclick="decreaseQty()" class="w-8 h-full bg-gray-50 font-bold">-</button>
                                    <input type="number" id="qty" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="flex-1 h-full text-center text-sm font-bold outline-none" readonly>
                                    <button type="button" onclick="increaseQty()" class="w-8 h-full bg-gray-50 font-bold">+</button>
                                </div>
                            </div>
                            <button type="submit" name="action" value="cart" class="w-full bg-[#FFD814] hover:bg-[#F7CA00] text-black py-3 rounded-full font-medium shadow-sm">Add to Cart</button>
                            <button type="submit" name="action" value="buy_now" class="w-full bg-[#FFA41C] hover:bg-[#FA8900] text-black py-3 rounded-full font-medium shadow-sm">Buy Now</button>
                        <?php else: ?>
                            <button type="button" disabled class="w-full bg-gray-200 text-gray-400 py-3 rounded-full font-bold cursor-not-allowed">Unavailable</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

        </div>

        <div id="reviews" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 mb-16">
            <h3 class="text-2xl font-serif font-bold text-brand-dark mb-6 border-b pb-4">Customer Reviews</h3>
            
            <div class="flex flex-col md:flex-row gap-10">
                <div class="w-full md:w-1/3 bg-slate-50 p-6 rounded-2xl border border-slate-200 h-max">
                    <h4 class="font-bold text-lg mb-2">Review this product</h4>
                    <p class="text-sm text-gray-500 mb-6">Share your thoughts with other customers</p>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form action="product_detail.php?id=<?php echo $product_id; ?>" method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold mb-2">Rating</label>
                                <select name="rating" required class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:border-brand-gold">
                                    <option value="5">⭐⭐⭐⭐⭐ (5/5) Excellent</option>
                                    <option value="4">⭐⭐⭐⭐ (4/5) Very Good</option>
                                    <option value="3">⭐⭐⭐ (3/5) Average</option>
                                    <option value="2">⭐⭐ (2/5) Poor</option>
                                    <option value="1">⭐ (1/5) Terrible</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold mb-2">Write your review</label>
                                <textarea name="review_text" rows="3" required placeholder="What did you like or dislike?" class="w-full border border-gray-300 rounded-lg p-3 outline-none focus:border-brand-gold"></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="w-full bg-brand-dark text-white font-bold py-3 rounded-lg hover:bg-brand-gold transition-colors">Submit Review</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center p-4 border border-dashed border-gray-300 rounded-xl">
                            <p class="text-sm font-bold text-gray-600 mb-3">Please login to write a review.</p>
                            <a href="/auth/login.php" class="bg-brand-dark text-white px-6 py-2 rounded-full text-sm font-bold">Login Now</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="w-full md:w-2/3 space-y-6">
                    <?php if(empty($reviews)): ?>
                        <p class="text-gray-500 italic">No reviews yet. Be the first to review this instrument!</p>
                    <?php else: ?>
                        <?php foreach($reviews as $rev): ?>
                            <div class="border-b border-gray-100 pb-6">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-8 h-8 rounded-full bg-brand-gold text-brand-dark flex items-center justify-center font-bold text-xs">
                                        <?php echo strtoupper(substr($rev['u_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <p class="font-bold text-brand-dark"><?php echo htmlspecialchars($rev['u_name'] ?? 'User'); ?></p>
                                    <span class="text-xs text-emerald-600 font-bold flex items-center gap-1">✓ Verified Purchase</span>
                                </div>
                                <div class="flex text-yellow-400 text-sm mb-2">
                                    <?php for($i=1; $i<=5; $i++) echo ($i <= $rev['rating']) ? '★' : '<span class="text-gray-200">★</span>'; ?>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed"><?php echo htmlspecialchars($rev['review_text']); ?></p>
                                <p class="text-xs text-gray-400 mt-2"><?php echo date('d M, Y', strtotime($rev['review_date'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        const qtyInput = document.getElementById('qty');
        const maxQty = <?php echo $product['stock_quantity'] ?? 0; ?>;
        function increaseQty() { if (qtyInput && qtyInput.value < maxQty) qtyInput.value = parseInt(qtyInput.value) + 1; }
        function decreaseQty() { if (qtyInput && qtyInput.value > 1) qtyInput.value = parseInt(qtyInput.value) - 1; }
    </script>
</body>
</html>