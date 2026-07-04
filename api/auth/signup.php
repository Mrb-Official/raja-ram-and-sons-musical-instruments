<?php
// signup.php
require_once '../includes/db_connect.php';
include '../includes/loading.php'; 


$swal_icon = ""; $swal_title = ""; $swal_text = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $u_name = $_POST['u_name'];
    $email_id = $_POST['email_id'];
    $mobile_number = $_POST['mobile_number'];
    $password = $_POST['password'];

    try {
        // ૧. ચેક કરો કે ઈમેલ પહેલેથી રજીસ્ટર્ડ તો નથી ને
        $check_sql = "SELECT COUNT(*) FROM users WHERE email_id = :email";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':email' => $email_id]);
        
        if ($check_stmt->fetchColumn() > 0) {
            $swal_icon = "warning";
            $swal_title = "Already Registered";
            $swal_text = "This email is already registered. Please login.";
        } else {
            // ૨. પાસવર્ડને સુરક્ષિત (Hash) કરો
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // ૩. ડેટાબેઝમાં યુઝર ઉમેરો
            $sql = "INSERT INTO users (u_name, email_id, mobile_number, password, status) 
                    VALUES (:u_name, :email_id, :mobile_number, :password, 'active')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':u_name' => $u_name,
                ':email_id' => $email_id,
                ':mobile_number' => $mobile_number,
                ':password' => $hashed_password
            ]);

            $swal_icon = "success";
            $swal_title = "Registration Successful!";
            $swal_text = "Your account has been created. Redirecting to login...";
            $redirect = true;
        }
    } catch (PDOException $e) {
        $swal_icon = "error";
        $swal_title = "Registration Failed";
        $swal_text = "Error: " . addslashes($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - RajaRam & Sons</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { serif: ['Playfair Display', 'serif'] } } }
        }
    </script>
    <style>
        /* From Uiverse.io by Yaya12085 - adapted for signup popup */
        .card {
            overflow: hidden;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            text-align: left;
            border-radius: 0.5rem;
            max-width: 448px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            background-color: #fff;
            z-index: 999;
        }

        .dismiss {
            position: absolute;
            right: 10px;
            top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            background-color: #fff;
            color: black;
            border: 2px solid #D1D5DB;
            font-size: 1rem;
            font-weight: 300;
            width: 30px;
            height: 30px;
            border-radius: 7px;
            transition: .3s ease;
            cursor: pointer;
        }

        .dismiss:hover {
            background-color: #ee0d0d;
            border: 2px solid #ee0d0d;
            color: #fff;
        }

        .header {
            padding: 2.25rem 2rem 2rem 2rem;
        }

        .image {
            display: flex;
            margin-left: auto;
            margin-right: auto;
            flex-shrink: 0;
            justify-content: center;
            align-items: center;
            width: 3rem;
            height: 3rem;
            border-radius: 9999px;
            animation: animate .6s linear alternate-reverse infinite;
            transition: .6s ease;
        }

        .image.success { background-color: #e2feee; }
        .image.error { background-color: #fdeaea; }

        .image svg {
            width: 2rem;
            height: 2rem;
        }

        .image.success svg { color: #0afa2a; }
        .image.error svg { color: #fa0a0a; }

        .content {
            margin-top: 0.75rem;
            text-align: center;
        }

        .title {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.5rem;
        }

        .title.success { color: #066e29; }
        .title.error { color: #8a1212; }

        .message {
            margin-top: 0.5rem;
            color: #595b5f;
            font-size: 0.875rem;
            line-height: 1.25rem;
        }

        .actions {
            margin: 0.75rem 1rem;
        }

        .history {
            display: inline-flex;
            padding: 0.5rem 1rem;
            background-color: #1aa06d;
            color: #ffffff;
            font-size: 1rem;
            line-height: 1.5rem;
            font-weight: 500;
            justify-content: center;
            width: 100%;
            border-radius: 0.375rem;
            border: none;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        @keyframes animate {
            from { transform: scale(1); }
            to { transform: scale(1.09); }
        }
    </style>
</head>
<body class="bg-[#0A192F] text-slate-800 font-sans min-h-screen flex items-center justify-center p-6 bg-cover bg-center" style="background-image: linear-gradient(rgba(10, 25, 74, 0.85), rgba(10, 25, 74, 0.85)), url('https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?auto=format&fit=crop&w=1920&q=80');">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-slate-100 overflow-hidden">
        <div class="bg-slate-50 px-8 py-6 border-b border-slate-200 text-center">
            <h2 class="text-3xl font-extrabold font-serif text-[#0A192F]">Create Account</h2>
            <p class="text-sm text-slate-500 mt-1">Join RajaRam & Sons Music Mall</p>
        </div>

        <form action="signup.php" method="POST" class="p-8 space-y-5">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-2">Full Name</label>
                <input type="text" name="u_name" required placeholder="John Doe" class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-slate-50 focus:ring-2 focus:ring-[#B7915F]/50 focus:border-[#B7915F] outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-2">Email Address</label>
                <input type="email" name="email_id" required placeholder="john@example.com" class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-slate-50 focus:ring-2 focus:ring-[#B7915F]/50 focus:border-[#B7915F] outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-2">Mobile Number</label>
                <input type="tel" name="mobile_number" required placeholder="9898XXXXXX" class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-slate-50 focus:ring-2 focus:ring-[#B7915F]/50 focus:border-[#B7915F] outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-2">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-slate-50 focus:ring-2 focus:ring-[#B7915F]/50 focus:border-[#B7915F] outline-none transition-all">
            </div>

            <button type="submit" class="w-full bg-[#0A192F] text-white font-bold py-3.5 rounded-lg hover:bg-[#B7915F] transition-all shadow-lg transform hover:-translate-y-0.5 uppercase tracking-wider text-sm">
                Sign Up
            </button>

            <p class="text-center text-sm text-slate-500 pt-2">
                Already have an account? <a href="login.php" class="text-[#B7915F] font-bold hover:underline">Login here</a>
            </p>
        </form>
    </div>

    <?php if (!empty($swal_icon)): ?>
    <div class="card" id="resultCard">
        <button class="dismiss" type="button" onclick="document.getElementById('resultCard').remove()">×</button>
        <div class="header">
            <div class="image <?php echo $swal_icon; ?>">
                <?php if ($swal_icon === 'success'): ?>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M20 7L9.00004 18L3.99994 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <?php endif; ?>
            </div>
            <div class="content">
                <span class="title <?php echo $swal_icon; ?>"><?php echo htmlspecialchars($swal_title); ?></span>
                <p class="message"><?php echo htmlspecialchars($swal_text); ?></p>
            </div>
            <div class="actions">
                <button class="history" type="button" id="popupActionBtn">
                    <?php echo isset($redirect) ? 'Continue to Login' : 'Try Again'; ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('popupActionBtn').addEventListener('click', function() {
            <?php if (isset($redirect)): ?>
                window.location.href = './login.php';
            <?php else: ?>
                document.getElementById('resultCard').remove();
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

</body>
</html>
