<?php
// ajax_search.php
require_once '../includes/db_connect.php';

if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 2) {
    exit; // જો સર્ચ ખાલી હોય અથવા ૨ અક્ષરથી નાનું હોય તો કંઈ બતાવવું નહિ
}

$query = "%" . trim($_GET['q']) . "%";
$html = "";

try {
    // ==========================================
    // ૧. પ્રોડક્ટ્સ (Products) સર્ચ કરો
    // ==========================================
    // ફિક્સ: :q ની જગ્યાએ :q1 અને :q2 અલગ અલગ વાપર્યા
    $stmt_prod = $pdo->prepare("SELECT pid, product_name, image, price FROM products WHERE product_name LIKE :q1 OR pid LIKE :q2 LIMIT 5");
    $stmt_prod->execute([':q1' => $query, ':q2' => $query]);
    $products = $stmt_prod->fetchAll();

    if ($products) {
        $html .= '<div class="px-4 py-2 bg-slate-50 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest border-b border-slate-200">🎸 Products</div>';
        foreach ($products as $p) {
            $img = !empty($p['image']) ? "/uploads/".$p['image'] : "/img/placeholder.png";
            $html .= '<a href="/admin/edit_product.php?id='.$p['pid'].'" class="flex items-center gap-3 px-4 py-3 hover:bg-[#B7915F]/10 border-b border-slate-100 transition-colors group">';
            $html .= '  <div class="w-10 h-10 rounded bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">';
            $html .= '      <img src="'.$img.'" class="w-full h-full object-cover group-hover:scale-110 transition-transform" onerror="this.style.display=\'none\'">';
            $html .= '  </div>';
            $html .= '  <div>';
            $html .= '      <p class="text-sm font-bold text-[#0A192F] group-hover:text-[#B7915F] transition-colors line-clamp-1">'.htmlspecialchars($p['product_name']).'</p>';
            $html .= '      <p class="text-xs text-slate-500 font-medium">ID: #'.str_pad($p['pid'], 4, '0', STR_PAD_LEFT).' &bull; ₹'.number_format($p['price']).'</p>';
            $html .= '  </div>';
            $html .= '</a>';
        }
    }

    // ==========================================
    // ૨. ઓર્ડર્સ (Orders) સર્ચ કરો
    // ==========================================
    $stmt_order = $pdo->prepare("SELECT o.oid, o.status, o.total_price, u.u_name 
                                 FROM orders o 
                                 LEFT JOIN users u ON o.uid = u.u_id 
                                 WHERE o.oid LIKE :q1 OR u.u_name LIKE :q2 LIMIT 5");
    $stmt_order->execute([':q1' => $query, ':q2' => $query]);
    $orders = $stmt_order->fetchAll();

    if ($orders) {
        $html .= '<div class="px-4 py-2 bg-slate-50 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest border-b border-slate-200">📦 Orders</div>';
        foreach ($orders as $o) {
            $html .= '<a href="/billing/invoice.php?oid='.$o['oid'].'" target="_blank" class="flex items-center justify-between px-4 py-3 hover:bg-[#B7915F]/10 border-b border-slate-100 transition-colors group">';
            $html .= '  <div>';
            $html .= '      <p class="text-sm font-bold text-[#0A192F] group-hover:text-[#B7915F] transition-colors">#ORD-'.str_pad($o['oid'], 4, '0', STR_PAD_LEFT).'</p>';
            $html .= '      <p class="text-xs text-slate-500 font-medium">By: '.htmlspecialchars($o['u_name'] ?? 'Guest').'</p>';
            $html .= '  </div>';
            $html .= '  <div class="text-right">';
            $html .= '      <span class="text-[10px] font-bold px-2 py-1 rounded bg-slate-100 text-slate-600">'.$o['status'].'</span>';
            $html .= '      <p class="text-xs font-black text-[#162A4A] mt-1">₹'.number_format($o['total_price']).'</p>';
            $html .= '  </div>';
            $html .= '</a>';
        }
    }

    // ==========================================
    // ૩. કસ્ટમર્સ (Customers) સર્ચ કરો
    // ==========================================
    $stmt_user = $pdo->prepare("SELECT u_id, u_name, mobile_number FROM users WHERE u_name LIKE :q1 OR mobile_number LIKE :q2 LIMIT 5");
    $stmt_user->execute([':q1' => $query, ':q2' => $query]);
    $users = $stmt_user->fetchAll();

    if ($users) {
        $html .= '<div class="px-4 py-2 bg-slate-50 text-[10px] font-extrabold text-slate-500 uppercase tracking-widest border-b border-slate-200">👥 Customers</div>';
        foreach ($users as $u) {
            $html .= '<a href="/admin/manage_customers.php" class="flex items-center gap-3 px-4 py-3 hover:bg-[#B7915F]/10 border-b border-slate-100 transition-colors group">';
            $html .= '  <div class="w-8 h-8 rounded-full bg-[#0A192F] text-white flex items-center justify-center font-bold text-xs shrink-0">';
            $html .= '      '.strtoupper(substr($u['u_name'], 0, 1)).'';
            $html .= '  </div>';
            $html .= '  <div>';
            $html .= '      <p class="text-sm font-bold text-[#0A192F] group-hover:text-[#B7915F] transition-colors">'.htmlspecialchars($u['u_name']).'</p>';
            $html .= '      <p class="text-xs text-slate-500 font-medium">📞 '.$u['mobile_number'].'</p>';
            $html .= '  </div>';
            $html .= '</a>';
        }
    }

    // જો કંઈ જ ના મળે તો 
    if ($html === "") {
        $html = '<div class="p-6 text-center text-slate-500 text-sm font-medium">No results found for "<span class="font-bold text-[#0A192F]">' . htmlspecialchars($_GET['q']) . '</span>"</div>';
    }

    echo $html;

} catch (PDOException $e) {
    // જો ડેટાબેઝમાં કોઈ પ્રોબ્લેમ હશે તો સીધી એરર સ્ક્રીન પર દેખાડશે
    echo '<div class="p-6 text-center text-red-600 text-sm font-bold bg-red-50">Database Error:<br> ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>