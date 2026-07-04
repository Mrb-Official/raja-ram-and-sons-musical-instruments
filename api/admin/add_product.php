<?php
// add_product.php
require_once '../includes/db_connect.php';
require_once '../includes/image_upload.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /auth/admin_login.php");
    exit;
}

$swal_icon = "";
$swal_title = "";
$swal_text = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $gst_rate = $_POST['gst_rate']; 
    $catid = $_POST['catid'];
    $stock_quantity = $_POST['stock_quantity'];
    
    $image_name = ""; 
    $upload_success = true; 

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        // Uploads to free image host (ImgBB) + auto-watermarks before upload.
        $uploadResult = upload_product_image($_FILES['product_image'], "RajaRam & Sons");

        if ($uploadResult['success']) {
            $image_name = $uploadResult['url']; // full hosted URL saved directly
        } else {
            $upload_success = false;
            $swal_icon = "error";
            $swal_title = "Upload Failed";
            $swal_text = "Image upload karvama kai problem thayo chhe: " . htmlspecialchars($uploadResult['error']);
        }
    }

    if ($upload_success) {
        try {
            $sql = "INSERT INTO products (catid, product_name, description, image, price, gst_rate, stock_quantity) 
                    VALUES (:catid, :product_name, :description, :image, :price, :gst_rate, :stock_quantity)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':catid' => $catid,
                ':product_name' => $product_name,
                ':description' => $description,
                ':image' => $image_name,
                ':price' => $price,
                ':gst_rate' => $gst_rate,
                ':stock_quantity' => $stock_quantity
            ]);
            $swal_icon = "success";
            $swal_title = "Success!";
            $swal_text = "Product added with RajaRam & Sons Watermark.";
        } catch (PDOException $e) {
            $swal_icon = "error";
            $swal_title = "Database Error";
            $swal_text = "Something went wrong: " . addslashes($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - RajaRam Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include '../includes/sidebar.php'; ?>

    <main class="flex-1 overflow-y-auto bg-gradient-to-br from-[#0A192F] via-[#162A4A] to-[#B7915F] p-8 md:p-0 flex flex-col relative z-10">

            <?php include '../includes/admin_topbar.php'; ?>    

        <div class="max-w-5xl w-full mx-auto my-auto">
            
            <div class="mb-8">
                <h2 class="text-3xl font-black text-[#0A192F] tracking-tight"></h2>
                <p class="text-sm text-slate-500 mt-1 font-medium"></p>
            </div>

            <div class="bg-white/95 backdrop-blur-md rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/40 p-8">
                
                <form action="add_product.php" method="POST" enctype="multipart/form-data" class="space-y-8">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Product Name <span class="text-red-500">*</span></label>
                            <input type="text" name="product_name" required placeholder="e.g. Premium Flute" class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 shadow-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Category <span class="text-red-500">*</span></label>
                            <select name="catid" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 shadow-sm">
                                <option value="" disabled selected>Select a category</option>
                                <option value="1">Classical Strings</option>
                                <option value="2">Western & Keys</option>
                                <option value="3">Rhythm & Beats</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Price (₹) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="price" placeholder="0.00" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 font-bold text-[#0A192F] shadow-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">GST Rate (%) <span class="text-red-500">*</span></label>
                            <select name="gst_rate" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 shadow-sm">
                                <option value="0">0% (Exempted)</option>
                                <option value="5">5% GST</option>
                                <option value="12">12% GST</option>
                                <option value="18" selected>18% GST</option>
                                <option value="28">28% GST</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Initial Stock <span class="text-red-500">*</span></label>
                            <input type="number" name="stock_quantity" value="1" min="0" required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all font-bold text-blue-600 shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Description <span class="text-red-500">*</span></label>
                        <textarea name="description" rows="4" placeholder="Enter detailed product description..." required class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-slate-800 shadow-sm"></textarea>
                    </div>

                    <div class="bg-slate-50 p-8 rounded-xl border-2 border-dashed border-slate-300 text-center hover:bg-slate-100 transition-colors shadow-inner">
                        <label class="block text-sm font-bold text-slate-700 mb-4">Upload Product Image <span class="text-red-500">*</span></label>
                        <div class="flex items-center justify-center w-full max-w-md mx-auto">
                            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-40 cursor-pointer bg-white rounded-xl border border-slate-200 shadow-sm hover:border-[#B7915F] transition-all">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-10 h-10 mb-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    <p class="mb-2 text-sm text-slate-600 font-bold">Click to browse image</p>
                                    <p class="text-xs text-slate-400 font-medium">PNG, JPG or JPEG (MAX. 2MB)</p>
                                </div>
                                <input id="dropzone-file" type="file" name="product_image" accept="image/*" required class="hidden" />
                            </label>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex justify-end">
                        <button type="submit" class="bg-[#0A192F] text-white px-8 py-3.5 rounded-xl font-bold text-lg hover:bg-[#B7915F] transition-colors shadow-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Save Product
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
            confirmButtonText: 'Great!',
            confirmButtonColor: '#B7915F',
            timer: 4000
        });
    </script>
    <?php endif; ?>

</body>
</html>
