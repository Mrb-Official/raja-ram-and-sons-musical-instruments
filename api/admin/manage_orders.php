<?php
// manage_orders.php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /auth/admin_login.php");
    exit;
}

$success_msg = "";

// ઓર્ડર અપડેટ કરવાનું લોજીક
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order'])) {
    $oid = $_POST['oid'];
    $new_status = $_POST['status'];
    $tracking_no = trim($_POST['tracking_no']);
    $courier_website = trim($_POST['courier_website']);
    $admin_notes = trim($_POST['admin_notes']);

    try {
        $stmt_check = $pdo->prepare("SELECT status FROM orders WHERE oid = :oid");
        $stmt_check->execute([':oid' => $oid]);
        $old_status = $stmt_check->fetchColumn();

        $stmt = $pdo->prepare("UPDATE orders 
                               SET status = :status, tracking_no = :tracking_no, courier_website = :courier_website, admin_notes = :admin_notes 
                               WHERE oid = :oid");
        $stmt->execute([
            ':status' => $new_status,
            ':tracking_no' => !empty($tracking_no) ? $tracking_no : null,
            ':courier_website' => !empty($courier_website) ? $courier_website : null,
            ':admin_notes' => !empty($admin_notes) ? $admin_notes : null,
            ':oid' => $oid
        ]);

        if ($old_status != $new_status) {
            $msg = "Your order status has been updated to " . $new_status . ".";
            if(!empty($tracking_no)) { $msg .= " Tracking Number: " . $tracking_no; }
            
            $stmt_track = $pdo->prepare("INSERT INTO order_tracking (order_id, status, message) VALUES (:oid, :status, :message)");
            $stmt_track->execute([
                ':oid' => $oid,
                ':status' => $new_status,
                ':message' => $msg
            ]);
        }

        $success_msg = "Order #ORD-" . str_pad($oid, 4, '0', STR_PAD_LEFT) . " updated successfully!";
    } catch (PDOException $e) {
        die("Error updating order: " . $e->getMessage());
    }
}

$orders = $pdo->query("SELECT o.*, u.u_name, p.product_name, osa.full_address 
                       FROM orders o 
                       LEFT JOIN users u ON o.uid = u.u_id 
                       LEFT JOIN products p ON o.product_id = p.pid 
                       LEFT JOIN order_shipping_addresses osa ON o.oid = osa.order_id
                       ORDER BY o.oid DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Advanced Order Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">

    <?php include '../includes/sidebar.php'; ?>

   <main class="flex-1 flex flex-col h-screen overflow-y-auto bg-gradient-to-br from-[#0A192F] via-[#162A4A] to-[#B7915F] relative z-10">
        
        <?php include '../includes/admin_topbar.php'; ?>
         

        <div class="max-w-7xl w-full mx-auto bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/40 my-auto">
            
            <div class="mb-8 border-b border-slate-200 pb-6">
                <h2 class="text-3xl font-black text-[#0A192F] tracking-tight">Live Order Management</h2>
                <p class="text-sm text-slate-500 mt-1 font-medium">Manage statuses, pack parcels, and add tracking details here.</p>
            </div>

            <div class="mb-6 relative w-full md:w-1/2 lg:w-1/3">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" id="orderSearch" placeholder="Search by Order ID, Name, or Product..." class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#B7915F] outline-none bg-white transition-colors text-sm font-medium text-slate-700 shadow-sm">
            </div>

            <?php if(!empty($success_msg)): ?>
                <div class="bg-emerald-50 text-emerald-600 border border-emerald-200 p-4 rounded-xl text-sm font-bold mb-6 flex items-center gap-2 shadow-sm">
                    ✅ <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-[#0A192F] text-xs uppercase font-bold text-white tracking-wider">
                                <th class="p-4 border border-slate-300">Order Info</th>
                                <th class="p-4 border border-slate-300">Customer & Address</th>
                                <th class="p-4 border border-slate-300">Product & Total</th>
                                <th class="p-4 border border-slate-300 w-1/3">Tracking & Dispatch Details</th>
                                <th class="p-4 border border-slate-300 text-center">Invoice</th>
                                <th class="p-4 border border-slate-300 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-200 bg-white">
                            <?php if(empty($orders)): ?>
                                <tr id="noOrdersRow"><td colspan="6" class="p-8 text-center text-slate-500 font-bold">No orders found.</td></tr>
                            <?php else: ?>
                                <?php foreach($orders as $o): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors order-row">
                                    <td class="p-4 border align-top">
                                        <span class="font-extrabold text-[#0A192F] text-base search-id">#ORD-<?php echo str_pad($o['oid'], 4, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td class="p-4 border align-top max-w-xs">
                                        <p class="font-bold text-slate-800 text-base mb-1 search-name"><?php echo htmlspecialchars($o['u_name'] ?? 'Unknown'); ?></p>
                                        <p class="text-xs text-slate-500 leading-relaxed"><?php echo htmlspecialchars($o['full_address'] ?? 'No address found'); ?></p>
                                    </td>
                                    <td class="p-4 border align-top">
                                        <p class="font-semibold text-slate-700 search-product"><?php echo htmlspecialchars($o['product_name'] ?? 'N/A'); ?></p>
                                        <p class="text-xs text-slate-400 mt-0.5">Qty: <?php echo $o['quantity']; ?></p>
                                        <p class="font-black text-[#B7915F] text-base mt-2">₹<?php echo number_format($o['total_price'], 2); ?></p>
                                    </td>
                                    <td class="p-4 border bg-slate-50/50">
                                        <form id="form-<?php echo $o['oid']; ?>" action="manage_orders.php" method="POST" class="space-y-3">
                                            <input type="hidden" name="oid" value="<?php echo $o['oid']; ?>">
                                            <input type="hidden" name="update_order" value="1">
                                            <div class="flex items-center gap-2">
                                                <label class="text-xs font-bold text-slate-500 w-24">Status:</label>
                                                <select name="status" class="flex-1 px-2 py-1 text-xs border rounded bg-white font-bold text-slate-700 focus:ring-1 focus:ring-[#B7915F] outline-none">
                                                    <option value="Pending" <?php echo ($o['status'] == 'Pending') ? 'selected' : ''; ?>>Pending ⏳</option>
                                                    <option value="Packed" <?php echo ($o['status'] == 'Packed') ? 'selected' : ''; ?>>Packed 📦</option>
                                                    <option value="Shipped" <?php echo ($o['status'] == 'Shipped') ? 'selected' : ''; ?>>Shipped 🚚</option>
                                                    <option value="Delivered" <?php echo ($o['status'] == 'Delivered') ? 'selected' : ''; ?>>Delivered ✅</option>
                                                    <option value="Cancelled" <?php echo ($o['status'] == 'Cancelled') ? 'selected' : ''; ?>>Cancelled ❌</option>
                                                </select>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="text-xs font-bold text-slate-500 w-24">Tracking No:</label>
                                                <input type="text" name="tracking_no" value="<?php echo htmlspecialchars($o['tracking_no'] ?? ''); ?>" placeholder="e.g. AMZ123456" class="flex-1 px-2 py-1 text-xs border rounded bg-white outline-none">
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label class="text-xs font-bold text-slate-500 w-24">Courier URL:</label>
                                                <input type="url" name="courier_website" value="<?php echo htmlspecialchars($o['courier_website'] ?? ''); ?>" placeholder="e.g. https://shreemaruticourier.com" class="flex-1 px-2 py-1 text-xs border rounded bg-white outline-none">
                                            </div>
                                            <div class="flex items-start gap-2">
                                                <label class="text-xs font-bold text-slate-500 w-24 mt-1">Admin Notes:</label>
                                                <textarea name="admin_notes" rows="1" placeholder="Add parcel notes..." class="flex-1 px-2 py-1 text-xs border rounded bg-white outline-none resize-none"><?php echo htmlspecialchars($o['admin_notes'] ?? ''); ?></textarea>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="p-4 border text-center align-top space-y-2">
                                        <a href="/billing/invoice.php?oid=<?php echo $o['oid']; ?>" target="_blank" class="flex items-center justify-center gap-1 text-blue-600 hover:text-white hover:bg-blue-600 font-bold text-[11px] uppercase tracking-wider bg-blue-50 px-3 py-2 rounded border border-blue-100 shadow-sm transition-colors">
                                            📄 Invoice
                                        </a>
                                        <a href="shipping_label.php?oid=<?php echo $o['oid']; ?>" target="_blank" class="flex items-center justify-center gap-1 text-slate-800 hover:text-white hover:bg-slate-800 font-bold text-[11px] uppercase tracking-wider bg-slate-100 px-3 py-2 rounded border border-slate-300 shadow-sm transition-colors">
                                            🏷️ Print Label
                                        </a>
                                    </td>
                                    <td class="p-4 border text-center align-top">
                                        <button type="submit" form="form-<?php echo $o['oid']; ?>" class="w-full bg-emerald-500 text-white px-3 py-2 rounded-lg shadow-sm text-xs font-bold hover:bg-emerald-600 transition-colors uppercase tracking-wide">
                                            Save Changes
                                        </button>
                                        <?php if(strtolower($o['status']) == 'delivered'): ?>
                                            <p class="text-[10px] text-emerald-600 font-bold mt-2">Completed ✅</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr id="noSearchResults" class="hidden">
                                <td colspan="6" class="p-8 text-center text-slate-500 font-bold text-sm">No matching orders found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('orderSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.order-row');
            let hasResults = false;
            rows.forEach(row => {
                let id = row.querySelector('.search-id').textContent.toLowerCase();
                let name = row.querySelector('.search-name').textContent.toLowerCase();
                let product = row.querySelector('.search-product').textContent.toLowerCase();
                if (id.includes(filter) || name.includes(filter) || product.includes(filter)) {
                    row.style.display = '';
                    hasResults = true;
                } else {
                    row.style.display = 'none';
                }
            });
            let noOrdersFound = document.getElementById('noOrdersRow');
            let noSearchResults = document.getElementById('noSearchResults');
            if(noOrdersFound && noOrdersFound.style.display !== 'none') return;
            if(hasResults) { noSearchResults.classList.add('hidden'); } else { noSearchResults.classList.remove('hidden'); }
        });
    </script>
</body>
</html>