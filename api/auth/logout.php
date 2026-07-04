<?php
session_start();
session_destroy(); // સેશન ખતમ કરો (લોગ-આઉટ)
header("Location: /auth/login.php"); // પાછા લોગીન પેજ પર
exit;
?>