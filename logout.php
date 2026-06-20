<?php
// ============================================================
// logout.php
// Hapus session dan redirect ke halaman login
// ============================================================

require_once 'config/config.php';
require_once 'config/functions.php';

session_destroy();
header('Location: ' . BASE_URL . 'login.php');
exit;