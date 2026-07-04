<?php
// add_to_wishlist.php
session_start();
require_once '../includes/db_connect.php';

// ૧. જો યુઝર લોગ-ઈન ન હોય તો
if (!isset($_SESSION['user_id'])) {
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>Swal.fire({icon: 'warning',title: 'Please Login First',text: 'You need to login to add items to your wishlist.',confirmButtonColor: '#0A192F',confirmButtonText: 'Go to Login'}).then(() => {window.location.href = 'login.php';});</script></body></html>";
    exit;
}

$product_id = isset($_GET['pid']) ? $_GET['pid'] : 0;
$user_id = $_SESSION['user_id'];

if ($product_id > 0) {
    try {
        // ૨. ચેક કરો કે આ પ્રોડક્ટ પહેલેથી જ wishlist માં છે કે નહિ
        // તમારા ડેટાબેઝ પ્રમાણે wishlist ટેબલમાં user_id અને product_id કોલમ છે
        $check_sql = "SELECT * FROM wishlist WHERE user_id = :uid AND product_id = :pid";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
        $exists = $check_stmt->fetch();

        if ($exists) {
            // જો પહેલેથી હોય તો ઈન્ફો મેસેજ બતાવો
            echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>Swal.fire({icon: 'info',title: 'Already in Wishlist',text: 'This instrument is already saved in your favorites! ❤️',showCancelButton: true,confirmButtonColor: '#0A192F',cancelButtonColor: '#B7915F',confirmButtonText: 'View Wishlist',cancelButtonText: 'Continue Shopping'}).then((result) => { if(result.isConfirmed) { window.location.href = 'wishlist.php'; } else { window.location.href = 'index.php'; }});</script></body></html>";
        } else {
            // ૩. જો ના હોય, તો નવી લાઈન એડ કરો
            $insert_sql = "INSERT INTO wishlist (user_id, product_id) VALUES (:uid, :pid)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([':uid' => $user_id, ':pid' => $product_id]);
            
            // સફળતાનો મેસેજ
            echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>Swal.fire({icon: 'success',title: 'Added to Wishlist! ❤️',text: 'The instrument has been saved to your favorites.',showCancelButton: true,confirmButtonColor: '#0A192F',cancelButtonColor: '#B7915F',confirmButtonText: 'View Wishlist',cancelButtonText: 'Continue Shopping'}).then((result) => { if(result.isConfirmed) { window.location.href = 'wishlist.php'; } else { window.location.href = 'index.php'; }});</script></body></html>";
        }

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: /index.php");
    exit;
}
?>