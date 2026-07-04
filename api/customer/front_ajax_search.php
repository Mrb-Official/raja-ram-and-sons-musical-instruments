<?php
// front_ajax_search.php
require_once '../includes/db_connect.php';

if (!isset($_GET['q']) || strlen(trim($_GET['q'])) < 2) {
    exit;
}

$query = "%" . trim($_GET['q']) . "%";
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

try {
    $sql = "SELECT pid, product_name, image, price FROM products WHERE product_name LIKE :q";
    $params = [':q' => $query];

    if ($category !== 'all') {
        $sql .= " AND catid = :cat";
        $params[':cat'] = $category;
    }
    
    $sql .= " LIMIT 5"; // માત્ર 5 જ રિઝલ્ટ બતાવો જેથી ડ્રોપડાઉન બહુ લાંબુ ના થાય

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $html = "";
    if ($products) {
        foreach ($products as $p) {
            $img = !empty($p['image']) ? "/uploads/".$p['image'] : "/img/placeholder.png";
            $html .= '<a href="product_detail.php?id='.$p['pid'].'" class="flex items-center gap-4 px-4 py-3 hover:bg-slate-50 border-b border-slate-100 transition-colors">';
            $html .= '  <div class="w-12 h-12 bg-white border border-slate-200 rounded flex items-center justify-center p-1 shrink-0">';
            $html .= '      <img src="'.$img.'" class="w-full h-full object-contain" onerror="this.style.display=\'none\'">';
            $html .= '  </div>';
            $html .= '  <div class="flex-1">';
            $html .= '      <p class="text-sm font-bold text-[#0A192F] line-clamp-1">'.htmlspecialchars($p['product_name']).'</p>';
            $html .= '      <p class="text-xs font-black text-[#B7915F] mt-0.5">₹'.number_format($p['price']).'</p>';
            $html .= '  </div>';
            $html .= '</a>';
        }
        // નીચે બધી પ્રોડક્ટ જોવા માટેની લિંક
        $html .= '<a href="search.php?q='.urlencode($_GET['q']).'&category='.$category.'" class="block text-center py-3 text-xs font-bold text-[#0A192F] bg-slate-100 hover:bg-slate-200 transition-colors uppercase tracking-wider">View All Results &rarr;</a>';
    } else {
        $html = '<div class="p-4 text-center text-sm font-medium text-slate-500">No instruments found.</div>';
    }

    echo $html;

} catch (PDOException $e) {
    echo '';
}
?>