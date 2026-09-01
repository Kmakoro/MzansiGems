<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'Admin';
$activeAdmin = $activeAdmin ?? 'dashboard';
$user = current_user();
$flashes = pull_flashes();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · Mzansi Gem Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="brand admin-brand" href="<?= url('admin/index.php') ?>"><span class="brand-mark"><i class="bi bi-compass-fill"></i></span><span>Admin Panel</span></a>
        <nav>
            <a class="<?= $activeAdmin === 'dashboard' ? 'active' : '' ?>" href="<?= url('admin/index.php') ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
            <a class="<?= $activeAdmin === 'users' ? 'active' : '' ?>" href="<?= url('admin/users.php') ?>"><i class="bi bi-people-fill"></i> User Management</a>
            <a class="<?= $activeAdmin === 'moderation' ? 'active' : '' ?>" href="<?= url('admin/moderation.php') ?>"><i class="bi bi-shield-check"></i> Content Moderation</a>
            <a class="<?= $activeAdmin === 'analytics' ? 'active' : '' ?>" href="<?= url('admin/analytics.php') ?>"><i class="bi bi-bar-chart-line-fill"></i> Analytics</a>
            <a class="<?= $activeAdmin === 'settings' ? 'active' : '' ?>" href="<?= url('admin/settings.php') ?>"><i class="bi bi-gear-fill"></i> Settings</a>
        </nav>
        <a class="back-profile" href="<?= url('profile.php') ?>"><i class="bi bi-arrow-left"></i> Back to profile</a>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" type="button" data-admin-menu><i class="bi bi-list"></i></button>
            <div><span class="muted">Administrator</span><strong><?= e($user['full_name'] ?? '') ?></strong></div>
        </header>
        <?php if ($flashes): ?><div class="toast-stack admin-toast"><?php foreach ($flashes as $flash): ?><div class="toast toast-<?= e($flash['type']) ?>"><span><?= e($flash['message']) ?></span><button type="button" data-dismiss-toast>×</button></div><?php endforeach; ?></div><?php endif; ?>
