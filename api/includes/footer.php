
<?php
// footer.php
?>
<footer class="bg-brand-dark text-white pt-10 sm:pt-14 pb-6 border-t-4 border-brand-gold mt-auto relative z-50">
    <div class="max-w-[1400px] mx-auto px-5 sm:px-6">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 sm:gap-10 mb-10 text-center sm:text-left">
            
            <div class="sm:col-span-2 lg:col-span-1">
                <h1 class="text-2xl font-black font-serif tracking-tight mb-2">RajaRam <span class="text-brand-gold italic font-normal">&</span> Sons</h1>
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-4">Preserving Heritage &amp; Melodies Since 1951</p>
                <p class="text-sm text-gray-400 leading-relaxed max-w-xs mx-auto sm:mx-0">Handcrafted instruments, trusted by musicians across Gujarat for over seven decades.</p>
            </div>

            <div>
                <h3 class="text-brand-gold font-bold mb-4 uppercase tracking-wider text-sm">Quick Links</h3>
                <ul class="space-y-2.5 text-sm text-gray-300">
                    <li><a href="/index.php" class="hover:text-brand-gold transition-colors">Home</a></li>
                    <li><a href="/customer/search.php" class="hover:text-brand-gold transition-colors">All Instruments</a></li>
                    <li><a href="/customer/wishlist.php" class="hover:text-brand-gold transition-colors">My Wishlist</a></li>
                    <li><a href="/customer/cart.php" class="hover:text-brand-gold transition-colors">My Cart</a></li>
                    <li><a href="/customer/user_dashboard.php" class="hover:text-brand-gold transition-colors">My Account</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-brand-gold font-bold mb-4 uppercase tracking-wider text-sm">Categories</h3>
                <ul class="space-y-2.5 text-sm text-gray-300">
                    <?php
                    try {
                        $footer_categories = $pdo->query("SELECT * FROM categories ORDER BY c_id ASC LIMIT 5")->fetchAll();
                    } catch (Exception $e) {
                        $footer_categories = [];
                    }
                    ?>
                    <?php foreach($footer_categories as $fcat): ?>
                        <li><a href="/customer/search.php?category=<?php echo $fcat['c_id']; ?>" class="hover:text-brand-gold transition-colors"><?php echo htmlspecialchars($fcat['category_name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div>
                <h3 class="text-brand-gold font-bold mb-4 uppercase tracking-wider text-sm">Contact Us</h3>
                <p class="text-sm text-gray-300 mb-1">Uplipad Road, Opp. Hatkeshwar Temple</p>
                <p class="text-sm text-gray-300 mb-4">Bhuj, Kutch, Gujarat - 370001</p>
                <p class="text-sm text-gray-300 font-bold flex flex-col sm:flex-row items-center sm:items-start justify-center sm:justify-start gap-1 sm:gap-2">
                     <span><span class="text-brand-gold">📞</span> +91 98799 57792</span>
                     <span><span class="text-brand-gold">📞</span> +91 98255 80615</span>
                </p>
            </div>
        </div>

        <div class="flex justify-center gap-6 mb-8 border-y border-white/10 py-5">
            <div class="flex items-center gap-2 text-xs font-bold text-gray-300"><span class="text-brand-gold text-base">✓</span> 100% Genuine</div>
            <div class="flex items-center gap-2 text-xs font-bold text-gray-300"><span class="text-brand-gold text-base">✓</span> Free Shipping</div>
            <div class="flex items-center gap-2 text-xs font-bold text-gray-300 hidden sm:flex"><span class="text-brand-gold text-base">✓</span> Easy Returns</div>
        </div>

        <div class="text-gray-400 text-xs flex flex-col md:flex-row justify-between items-center gap-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> RajaRam & Sons Music Mall. All Rights Reserved.</p>
            
            <p class="font-medium tracking-wide">
                Developed by <span class="text-brand-gold font-bold">Yuvraj Chudasama</span> & <span class="text-brand-gold font-bold">Meet Sonara</span> (74339 65406)
            </p>
        </div>
        
    </div>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('frontSearchInput');
        const searchCategory = document.getElementById('frontSearchCategory');
        const searchResults = document.getElementById('frontSearchResults');

        if(searchInput && searchResults) {
            searchInput.addEventListener('keyup', fetchResults);
            if(searchCategory) searchCategory.addEventListener('change', fetchResults);

            function fetchResults() {
                let query = searchInput.value.trim();
                let category = searchCategory ? searchCategory.value : 'all';
                
                if (query.length >= 2) {
                    fetch('/customer/front_ajax_search.php?q=' + encodeURIComponent(query) + '&category=' + encodeURIComponent(category))
                        .then(response => response.text())
                        .then(data => {
                            searchResults.innerHTML = data;
                            searchResults.classList.remove('hidden');
                        });
                } else {
                    searchResults.innerHTML = '';
                    searchResults.classList.add('hidden');
                }
            }

            document.addEventListener('click', function(event) {
                if (!searchInput.contains(event.target) && !searchResults.contains(event.target) && (!searchCategory || !searchCategory.contains(event.target))) {
                    searchResults.classList.add('hidden');
                }
            });
        }
    });
</script>