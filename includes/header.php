<?php require_once __DIR__ . '/../config/app.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/base.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">

    <!-- Page Specific CSS -->
    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="../assets/css/<?= htmlspecialchars($pageCss, ENT_QUOTES, 'UTF-8') ?>">
    <?php else: ?>
        <link rel="stylesheet" href="../assets/css/schedule.css">
    <?php endif; ?>
</head>
<body>
    <div class="app-shell">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content" id="main-content">
