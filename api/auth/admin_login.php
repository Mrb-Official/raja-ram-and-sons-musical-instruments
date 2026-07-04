<?php
session_start();
// ડેટાબેઝ કનેક્શન ફાઈલ (auth ફોલ્ડરની બહાર હોવાથી ../ વાપર્યું છે)
require_once '../includes/db_connect.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    try {
        // ડેટાબેઝમાંથી યુઝર ચેક કરો
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin) {
            // પાસવર્ડ સાદો છે કે હેશ (Hash) થયેલો છે તે બંને ચેક કરશે
            if ($password === $admin['password'] || password_verify($password, $admin['password'])) {
                
                // જો પાસવર્ડ સાચો પડે, તો જ અહી સેશન (Session) બનશે
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                
                // લોગ-ઈન થયા પછી ડેશબોર્ડ પર મોકલી દો
                header("Location: ../admin/admin_dashboard.php");
                exit;
            } else {
                $error = "Incorrect password! Please try again.";
            }
        } else {
            $error = "Username not found!";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - RajaRam Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; } 
    </style>
</head>
<body class="bg-gradient-to-br from-[#0A192F] via-[#162A4A] to-[#B7915F] flex h-screen items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-8 sm:p-10 border-t-4 border-[#B7915F]">
        
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black text-[#0A192F] tracking-tight">RajaRam <span class="text-[#B7915F]">& Sons</span></h1>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Admin Portal</p>
        </div>

        <h2 class="text-2xl font-bold text-slate-800 text-center mb-6">Secure Login</h2>

        <?php if(!empty($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r">
                <p class="text-sm font-bold text-red-600"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="admin_login.php" class="space-y-5">
            <div>
                <label class="block text-sm font-bold text-slate-600 mb-1">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <input type="text" name="username" required placeholder="Enter admin ID" class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-sm font-medium">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-600 mb-1">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input type="password" name="password" required placeholder="Enter password" class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#B7915F] outline-none bg-slate-50 focus:bg-white transition-all text-sm font-medium">
                </div>
            </div>

            <button type="submit" class="w-full bg-[#0A192F] text-white font-bold py-3.5 rounded-xl hover:bg-[#162A4A] transition-colors shadow-lg mt-2 uppercase tracking-wide">
                Login to Dashboard
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="../index.php" class="text-sm font-medium text-slate-500 hover:text-[#B7915F] transition-colors">
                ← Back to Main Store
            </a>
        </div>

    </div>

</body>
</html>