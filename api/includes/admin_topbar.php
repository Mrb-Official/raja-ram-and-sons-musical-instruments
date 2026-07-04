<?php
// admin_topbar.php

// અત્યારે કયું પેજ ખુલ્લું છે તે જાણવા
$current_file = basename($_SERVER['PHP_SELF']);
$page_title = "Admin Dashboard"; // ડિફોલ્ટ નામ

// જે પેજ હોય તે પ્રમાણે નામ સેટ કરો
switch ($current_file) {
    case 'admin_dashboard.php':
        $page_title = "Dashboard Overview";
        break;
    case 'manage_products.php':
        $page_title = "Product Management";
        break;
    case 'add_product.php':
        $page_title = "Add New Instrument";
        break;
    case 'edit_product.php':
        $page_title = "Edit Instrument";
        break;
    case 'manage_orders.php':
        $page_title = "Order Management";
        break;
    case 'manage_customers.php':
        $page_title = "Registered Customers";
        break;
    case 'offline_billing.php':
        $page_title = "Offline POS Billing";
        break;
    case 'manage_staff.php':
        $page_title = "Staff Access Control";
        break;
    case 'accounting.php': // આ નવું ઉમેર્યું
        $page_title = "Accounting & Finance";
        break;
}
?>

<header class="bg-white/90 backdrop-blur-md shadow-sm px-6 py-4 flex items-center justify-between gap-6 sticky top-0 z-50 border-b border-white/20 shrink-0">
    
    <h2 class="text-xl md:text-2xl font-black text-[#0A192F] hidden lg:block tracking-tight shrink-0 min-w-max">
        <?php echo $page_title; ?>
    </h2>
    
    <div class="relative flex-1 max-w-lg hidden sm:block mx-auto">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input type="text" id="globalSearch" placeholder="Search across store (Products, Orders, Customers)..." class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-colors text-sm font-medium text-slate-700 shadow-sm relative z-50">
        </div>
        
        <div id="globalSearchResults" class="absolute top-full mt-2 left-0 w-full bg-white border border-slate-200 rounded-xl shadow-2xl hidden overflow-hidden max-h-[400px] overflow-y-auto z-[100]">
            </div>
    </div>

    <div class="flex items-center gap-4 shrink-0 ml-auto">
        <p class="text-sm font-bold text-slate-600 hidden xl:block">Welcome, <span class="text-[#B7915F]"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span></p>
        
        <a href="/index.php" target="_blank" class="text-sm font-bold text-white bg-[#0A192F] px-4 py-2 rounded-lg hover:bg-[#162A4A] transition-colors flex items-center gap-1 shadow-sm shrink-0">
            <span class="hidden md:inline">Live Store</span> ↗
        </a>
        
        <a href="/auth/logout.php" class="text-sm font-bold text-red-500 hover:text-white hover:bg-red-500 px-3 py-2 rounded-lg transition-colors border border-red-500/30 shrink-0">
            Logout
        </a>
    </div>
</header>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('globalSearch');
        const searchResults = document.getElementById('globalSearchResults');

        if(searchInput && searchResults) {
            searchInput.addEventListener('keyup', function() {
                let query = this.value.trim();
                
                // જો બે કે તેથી વધુ અક્ષર ટાઈપ કર્યા હોય તો જ સર્ચ કરો
                if (query.length >= 2) {
                    fetch('/admin/ajax_search.php?q=' + encodeURIComponent(query))
                        .then(response => response.text())
                        .then(data => {
                            searchResults.innerHTML = data;
                            searchResults.classList.remove('hidden');
                        })
                        .catch(error => console.error('Error fetching search results:', error));
                } else {
                    searchResults.innerHTML = '';
                    searchResults.classList.add('hidden');
                }
            });

            // જો યુઝર સર્ચ બોક્સની બહાર ક્લિક કરે તો ડ્રોપડાઉન છુપાવી દો
            document.addEventListener('click', function(event) {
                if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                    searchResults.classList.add('hidden');
                }
            });
        }
    });
</script>