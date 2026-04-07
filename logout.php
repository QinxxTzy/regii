<?php
session_start();
session_destroy(); // Hapus semua data sesi
header("Location: login.php"); // Kembali ke halaman login
exit();
?>