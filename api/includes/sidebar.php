<?php
// અત્યારે કયું પેજ ખુલ્લું છે તે જાણવા માટે
$current_page = basename($_SERVER['PHP_SELF']);

// એક્ટિવ અને ઈન-એક્ટિવ બટનની ડિઝાઈન
$active_class = "flex items-center gap-3 bg-[#B7915F] text-white px-4 py-3 rounded-lg font-bold shadow-md transition-colors";
$inactive_class = "flex items-center gap-3 text-slate-300 hover:text-white hover:bg-white/5 px-4 py-3 rounded-lg font-medium transition-colors";
?>

<aside class="w-64 bg-[#06101E] text-white flex flex-col hidden md:flex shadow-[5px_0_20px_rgba(0,0,0,0.5)] border-r border-[#B7915F]/30 z-20 shrink-0 relative">
    <div class="h-20 flex items-center justify-center border-b border-white/10">
        <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#B7915F] to-[#D4AF37]">RajaRam Admin</h1>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        
        <a href="/admin/admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php') ? $active_class : $inactive_class; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        
        <a href="/admin/offline_billing.php" class="<?php echo ($current_page == 'offline_billing.php') ? $active_class : $inactive_class; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            Offline Billing
        </a>
        
        <a href="/admin/add_product.php" class="<?php echo ($current_page == 'add_product.php') ? $active_class : $inactive_class; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Product
        </a>
        
        <a href="/admin/manage_products.php" class="<?php echo ($current_page == 'manage_products.php') ? $active_class : $inactive_class; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            Manage Products
        </a>
        
        
        
        <a href="/admin/manage_customers.php" class="<?php echo ($current_page == 'manage_customers.php') ? $active_class : $inactive_class; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Customers
        </a>
        <a href="/admin/manage_orders.php" class="<?php echo ($current_page == 'manage_orders.php') ? $active_class : $inactive_class; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
            Manage Orders
        </a>
        
        <a href="/admin/accounting.php" class="<?php echo ($current_page == 'accounting.php') ? $active_class : $inactive_class; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Accounting & Finance
        </a>
        
        <?php if(isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'SuperAdmin'): ?>
        <a href="/admin/manage_staff.php" class="<?php echo ($current_page == 'manage_staff.php') ? $active_class : $inactive_class; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Staff Access
        </a>
        <?php endif; ?>
        
    </nav>
</aside>