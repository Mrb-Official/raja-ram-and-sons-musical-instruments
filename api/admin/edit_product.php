<?php
// edit_product.php
require_once '../includes/db_connect.php';
require_once '../includes/image_upload.php';
session_start();

// જો એડમિન લોગ-ઈન ન હોય તો અટકાવો
if (!isset($_SESSION['admin_id'])) {
    header("Location: /auth/admin_login.php");
    exit;
}

// SweetAlert માટેના ચલો
$swal_icon = "";
$swal_title = "";
$swal_text = "";

$product_id = isset($_GET['id']) ? $_GET['id'] : 0;

// જો યુઝરે ફોર્મ અપડેટ કરવા સબમિટ કર્યું હોય (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pid = $_POST['pid'];
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $catid = $_POST['catid'];
    $stock_quantity = $_POST['stock_quantity'];
    
    // ઇમેજ અપડેટ કરવાનું લોજીક
    $update_image_query = "";
    $image_params = [];
    $upload_success = true;

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        // Uploads to free image host (ImgBB) + auto-watermarks before upload.
        $uploadResult = upload_product_image($_FILES['product_image'], "RajaRam & Sons");

        if ($uploadResult['success']) {
            // Note: old image (whether a hosted URL or legacy local filename)
            // is simply left as-is; ImgBB free tier has no delete API via key auth,
            // and old local files can't be deleted on Vercel's filesystem anyway.
            $update_image_query = ", image = :image";
            $image_params = [':image' => $uploadResult['url']];
        } else {
             $upload_success = false;
             $swal_icon = "error";
             $swal_title = "Upload Failed";
             $swal_text = "Failed to upload new image: " . htmlspecialchars($uploadResult['error']);
        }
    }

    if ($upload_success) {
        try {
            $sql = "UPDATE products SET 
                    catid = :catid, 
                    product_name = :product_name, 
                    description = :description, 
                    price = :price, 
                    stock_quantity = :stock_quantity 
                    $update_image_query 
                    WHERE pid = :pid";
            
            $params = [
                ':catid' => $catid,
                ':product_name' => $product_name,
                ':description' => $description,
                ':price' => $price,
                ':stock_quantity' => $stock_quantity,
                ':pid' => $pid
            ];
            
            $final_params = array_merge($params, $image_params);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($final_params);

            $swal_icon = "success";
            $swal_title = "Updated!";
            $swal_text = "Product details have been updated successfully.";
            
        } catch (PDOException $e) {
            $swal_icon = "error";
            $swal_title = "Database Error";
            $swal_text = "Failed to update: " . addslashes($e->getMessage());
        }
    }
}

// ડેટાબેઝમાંથી પ્રોડક્ટની જૂની માહિતી લાવો (GET)
if ($product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE pid = :id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        die("Product not found!");
    }
} else {
    header("Location: /admin/manage_products.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - RajaRam Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-screen overflow-y-auto bg-gradient-to-br from-[#0A192F] via-[#162A4A] to-[#B7915F] relative z-10">
        
        <?php include '../includes/admin_topbar.php'; ?>

        <div class="p-8 max-w-5xl mx-auto w-full my-auto">
            
            <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/40 p-8 md:p-10">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 pb-6 border-b border-slate-200 gap-4">
                    <div>
                        <h2 class="text-3xl font-black text-[#0A192F] tracking-tight">Edit Instrument</h2>
                        <p class="text-sm text-slate-500 mt-1 font-medium">Update details for <span class="font-bold"><?php echo htmlspecialchars($product['product_name']); ?></span>.</p>
                    </div>
                    <a href="manage_products.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm transition-colors border border-slate-200 shrink-0 flex items-center gap-2">
                        ← Back to List
                    </a>
                </div>

                <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data" class="space-y-8">
                    
                    <input type="hidden" name="pid" value="<?php echo htmlspecialchars($product['pid']); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Product Name <span class="text-red-500">*</span></label>
                            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 shadow-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Category <span class="text-red-500">*</span></label>
                            <select name="catid" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 shadow-sm">
                                <option value="1" <?php if($product['catid'] == 1) echo 'selected'; ?>>Classical Strings</option>
                                <option value="2" <?php if($product['catid'] == 2) echo 'selected'; ?>>Western & Keys</option>
                                <option value="3" <?php if($product['catid'] == 3) echo 'selected'; ?>>Rhythm & Beats</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Price (₹) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 font-bold text-[#0A192F] shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Stock Quantity <span class="text-red-500">*</span></label>
                            <input type="number" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all font-bold text-blue-600 shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Description <span class="text-red-500">*</span></label>
                        <textarea name="description" rows="4" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 shadow-sm"><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="bg-slate-50 p-6 rounded-xl border border-dashed border-slate-300 text-center flex items-center justify-between shadow-inner hover:bg-slate-100 transition-colors">
                        <div class="text-left flex-1">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Update Product Image</label>
                            <p class="text-xs text-slate-500 mb-4">(Leave empty if you want to keep the current image. Max 2MB)</p>
                            <input type="file" name="product_image" accept="image/*" class="text-sm cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#B7915F]/10 file:text-[#B7915F] hover:file:bg-[#B7915F]/20">
                        </div>
                        
                        <?php if (!empty($product['image'])): ?>
                        <div class="w-24 h-24 bg-white border border-slate-200 rounded-lg p-1 shadow-sm shrink-0 relative group">
                            <img src="<?php echo render_image_src($product['image']); ?>" class="w-full h-full object-cover rounded">
                            <div class="absolute inset-0 bg-black/50 rounded flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <span class="text-[10px] font-bold text-white uppercase tracking-wider">Current</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="bg-[#0A192F] text-white px-10 py-3.5 rounded-xl font-bold text-lg hover:bg-[#B7915F] transition-colors shadow-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Update Product
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </main>

    <?php if (!empty($swal_icon)): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $swal_icon; ?>',
            title: '<?php echo $swal_title; ?>',
            text: '<?php echo $swal_text; ?>',
            showConfirmButton: true,
            confirmButtonColor: '#B7915F',
            timer: <?php echo ($swal_icon == 'success') ? 3000 : 'null'; ?> 
        }).then((result) => {
            <?php if($swal_icon == 'success'): ?>
            window.location.href = 'manage_products.php';
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

</body>
</html>