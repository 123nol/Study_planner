<?php
require_once __DIR__ . '/../config/app.php';

if (!isset($_SESSION['user_id'])) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $loginPath = str_contains($scriptName, '/pages/') ? 'login.php' : 'pages/login.php';
    header('Location: ' . $loginPath);
    exit;
}

$currentUser = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'email' => $_SESSION['email'],
    'timezone' => $_SESSION['timezone'] ?? 'Africa/Addis_Ababa'
];
