<?php
// admin/_auth.php
session_start();

if (empty($_SESSION['admin_id'])) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g. /tour/admin
    header('Location: ' . $base . '/login.php');
    exit;
}
