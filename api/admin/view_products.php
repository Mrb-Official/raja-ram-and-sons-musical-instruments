<?php
// view_products.php
require_once '../includes/db_connect.php';
require_once '../includes/image_upload.php';

// SweetAlert માટેના ચલો
$swal_icon = "";
$swal_title = "";
$swal_text = "";

// જો પ્રોડક્ટ ડિલીટ કરવાની રિક્વેસ્ટ આવે (Delete Action)
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    try {
        // ફોટો પણ ફોલ્ડરમાંથી ડીલીટ કરવા માટે ફોટાનું નામ મેળવો
        $stmt_img = $pdo->prepare("SELECT image FROM products WHERE pid = :id");
        $stmt_img->execute([':id' => $delete_id]);
        $img_row = $stmt_img->fetch();
        
        if ($img_row && !empty($img_row['image'])) {
            $img_path = __DIR__ . '/../uploads/' . $img_row['image'];
            if (file_exists($img_path)) {
                unlink($img_path); // સર્વરમાંથી ફોટો ઉડાવી દો
            }
        }

        // ડેટાબેઝમાંથી પ્રોડક્ટ ડીલીટ કરો
        $stmt_del = $pdo->prepare("DELETE FROM products WHERE pid = :id");
        $stmt_del->execute([':id' => $delete_id]);
        
        // SweetAlert Success Setup
        $swal_icon = "success";
        $swal_title = "Deleted!";
        $swal_text = "Product and its image have been deleted successfully.";
        
    } catch (PDOException $e) {
        // SweetAlert Error Setup
       $swal_icon = "error";
       $swal_title = "Action Failed";
       $swal_text = "Delete error: " . addslashes($e->getMessage());
    }
}

// ડેટાબેઝમાંથી બધી પ્રોડક્ટ્સ લાવો
try {
    $sql = "SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.catid = c.c_id 
            ORDER BY p.pid DESC";
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $swal_icon = "error";
    $swal_title = "Database Error";
    $swal_text = "Error fetching data: " . addslashes($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - RajaRam Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <aside class="w-64 bg-[#0A192F] text-white flex flex-col hidden md:flex shadow-2xl z-20">
        <div class="h-20 flex items-center justify-center border-b border-white/10">
            <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#B7915F] to-[#D4AF37]">RajaRam Admin</h1>
        </div>
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
            <a href="/admin/admin_dashboard.php" class="flex items-center gap-3 text-slate-300 hover:text-white hover:bg-white/5 px-4 py-3 rounded-lg font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="/admin/add_product.php" class="flex items-center gap-3 text-slate-300 hover:text-white hover:bg-white/5 px-4 py-3 rounded-lg font-medium transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Product
            </a>
            <a href="/admin/view_products.php" class="flex items-center gap-3 bg-[#B7915F]/20 text-[#D4AF37] px-4 py-3 rounded-lg font-semibold transition-colors border border-[#B7915F]/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                View Products
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto">
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-2xl font-bold text-slate-800">Manage Products</h2>
            <a href="/admin/add_product.php" class="bg-[#0A192F] text-white px-5 py-2.5 rounded-lg font-semibold hover:bg-[#B7915F] transition-colors text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add New Product
            </a>
        </header>

        <div class="p-8">

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 text-sm uppercase tracking-wider">
                                <th class="p-4 font-bold">Image</th>
                                <th class="p-4 font-bold">Product Name</th>
                                <th class="p-4 font-bold">Category</th>
                                <th class="p-4 font-bold">Price</th>
                                <th class="p-4 font-bold text-center">Stock</th>
                                <th class="p-4 font-bold text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-500 font-medium">No products found in the database.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $row): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        
                                        <td class="p-4">
                                            <div class="w-16 h-16 bg-slate-100 rounded-lg overflow-hidden flex items-center justify-center border border-slate-200">
                                                <?php if (!empty($row['image'])): ?>
                                                    <img src="<?php echo render_image_src($row['image']); ?>" alt="img" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-400">No Img</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        
                                        <td class="p-4">
                                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($row['product_name']); ?></p>
                                        </td>
                                        
                                        <td class="p-4">
                                            <span class="bg-blue-50 text-blue-600 px-3 py-1 rounded-full text-xs font-semibold border border-blue-100">
                                                <?php echo htmlspecialchars($row['category_name'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        
                                        <td class="p-4 font-bold text-[#0A192F]">
                                            ₹<?php echo number_format($row['price'], 2); ?>
                                        </td>
                                        
                                        <td class="p-4 text-center">
                                            <?php if ($row['stock_quantity'] > 0): ?>
                                                <span class="text-green-600 font-bold"><?php echo $row['stock_quantity']; ?></span>
                                            <?php else: ?>
                                                <span class="text-red-500 font-bold bg-red-50 px-2 py-1 rounded text-xs">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td class="p-4 text-center flex justify-center gap-2">
                                            <a href="/admin/edit_product.php?id=<?php echo $row['pid']; ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 text-blue-500 hover:bg-blue-500 hover:text-white transition-colors border border-blue-100" title="Edit Product">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>

                                            <a href="/admin/view_products.php?delete_id=<?php echo $row['pid']; ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors border border-red-100" title="Delete Product">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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

    <?php if (!empty($swal_icon)): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $swal_icon; ?>',
            title: '<?php echo $swal_title; ?>',
            text: '<?php echo $swal_text; ?>',
            showConfirmButton: false, // ડીલીટ થયા પછી ઓટોમેટિક બંધ થવા દો
            timer: 2500
        }).then(() => {
            // પોપ-અપ બંધ થાય એટલે URL માંથી ?delete_id ક્લીન કરી નાખો 
            // જેથી રિફ્રેશ કરવા પર ફરીથી ડીલીટ ના થાય
            window.location.href = 'view_products.php';
        });
    </script>
    <?php endif; ?>

</body>
</html>