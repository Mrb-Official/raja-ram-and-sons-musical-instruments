<?php
// manage_staff.php
session_start();
require_once '../includes/db_connect.php';

// ૧. સિક્યોરિટી ચેક: માત્ર એડમિન લોગ-ઈન હોવો જોઈએ
if (!isset($_SESSION['admin_id'])) {
    header("Location: /auth/admin_login.php");
    exit;
}

// ૨. સુપર સિક્યોરિટી ચેક: માત્ર 'SuperAdmin' જ આ પેજ ખોલી શકે!
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'SuperAdmin') {
    echo "<script>alert('Access Denied! Only SuperAdmin can access this page.'); window.location.href='admin_dashboard.php';</script>";
    exit;
}

$success_msg = "";
$error_msg = "";

// નવો સ્ટાફ એડ કરવાનું લોજીક
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']); 
    $password = $_POST['password'];
    $role = $_POST['role'];

    // પાસવર્ડને સિક્યોર (હેશ) કરો
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO admins (name, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $username, $hashed_password, $role]);
        $success_msg = "New staff account created successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error! Username might already exist.";
    }
}

// પાસવર્ડ બદલવાનું લોજીક
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $target_id = $_POST['staff_id'];
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE admin_id = ?");
        $stmt->execute([$hashed_password, $target_id]);
        $success_msg = "Password updated successfully!";
    } catch (PDOException $e) {
        $error_msg = "Error updating password.";
    }
}

// બધા સ્ટાફનું લિસ્ટ લાવો (admin_id પ્રમાણે)
$staff_list = $pdo->query("SELECT admin_id, name, username, role FROM admins ORDER BY admin_id ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Staff - RajaRam Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden text-slate-800">
    <?php include '../includes/sidebar.php'; ?>
    

      <main class="flex-1 flex flex-col h-screen overflow-y-auto bg-gradient-to-br from-[#0A192F] via-[#162A4A] to-[#B7915F] relative z-10">
        
        <?php include '../includes/admin_topbar.php'; ?>
        <div class="max-w-7xl w-full mx-auto bg-white/95 backdrop-blur-md p-8 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/40 my-auto">
            
            <div class="mb-8">
                <h2 class="text-3xl font-black text-[#0A192F] tracking-tight">Staff Access Dashboard</h2>
                <p class="text-sm text-slate-500 mt-1 font-medium">Create new staff IDs and manage their passwords. (SuperAdmin Only)</p>
            </div>

            <?php if(!empty($success_msg)): ?>
                <script>Swal.fire({icon: 'success', title: 'Done!', text: '<?php echo $success_msg; ?>', confirmButtonColor: '#B7915F'});</script>
            <?php endif; ?>
            <?php if(!empty($error_msg)): ?>
                <script>Swal.fire({icon: 'error', title: 'Oops...', text: '<?php echo $error_msg; ?>'});</script>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                        <h3 class="font-bold text-[#0A192F] text-lg border-b border-slate-100 pb-3 mb-4">Add New User</h3>
                        <form action="manage_staff.php" method="POST" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Full Name</label>
                                <input type="text" name="name" required placeholder="e.g. Rahul Kumar" class="w-full px-3 py-2 border rounded-lg bg-slate-50 focus:ring-2 focus:ring-[#B7915F] outline-none text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Login ID (Username)</label>
                                <input type="text" name="username" required placeholder="e.g. rahul123" class="w-full px-3 py-2 border rounded-lg bg-slate-50 focus:ring-2 focus:ring-[#B7915F] outline-none text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Password</label>
                                <input type="password" name="password" required placeholder="Enter secure password" class="w-full px-3 py-2 border rounded-lg bg-slate-50 focus:ring-2 focus:ring-[#B7915F] outline-none text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Access Level (Role)</label>
                                <select name="role" class="w-full px-3 py-2 border rounded-lg bg-slate-50 focus:ring-2 focus:ring-[#B7915F] outline-none text-sm font-bold">
                                    <option value="Staff">Staff (Billing & Products)</option>
                                    <option value="SuperAdmin">SuperAdmin (Full Access)</option>
                                </select>
                            </div>
                            <button type="submit" name="add_staff" class="w-full bg-[#0A192F] text-white font-bold py-3 rounded-lg hover:bg-[#162A4A] transition-colors mt-2 text-sm uppercase tracking-wide">
                                Create Account
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider border-b border-slate-200">
                                    <th class="p-4">Staff Details</th>
                                    <th class="p-4 text-center">Role</th>
                                    <th class="p-4 text-right">Update Password</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <?php foreach($staff_list as $staff): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4">
                                        <p class="font-bold text-[#0A192F] text-base"><?php echo htmlspecialchars($staff['name']); ?></p>
                                        <p class="text-xs text-slate-500">ID: <?php echo htmlspecialchars($staff['username']); ?></p>
                                    </td>
                                    <td class="p-4 text-center">
                                        <?php if($staff['role'] == 'SuperAdmin'): ?>
                                            <span class="bg-[#B7915F]/20 text-[#96764a] border border-[#B7915F]/30 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">SuperAdmin</span>
                                        <?php else: ?>
                                            <span class="bg-blue-50 text-blue-600 border border-blue-200 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">Staff</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <form action="manage_staff.php" method="POST" class="flex gap-2 justify-end">
                                            <input type="hidden" name="staff_id" value="<?php echo $staff['admin_id']; ?>">
                                            <input type="password" name="new_password" required placeholder="New password" class="w-32 px-2 py-1.5 border rounded bg-white outline-none focus:border-[#B7915F] text-xs">
                                            <button type="submit" name="change_password" class="bg-emerald-50 text-emerald-600 border border-emerald-200 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded font-bold text-xs transition-colors">
                                                Update
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>