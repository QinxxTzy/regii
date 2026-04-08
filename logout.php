<?php
// ============================================
// logout.php — GameVault
// Compatible: PHP 5.x
// ============================================

session_start();

// Hapus semua data session
$_SESSION = array();

// Hapus session cookie jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login dengan pesan
header("Location: login.php?logout=1");
exit();
?>