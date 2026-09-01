<?php
declare(strict_types=1);
$siteName = setting('site_name', APP_NAME);
$pageTitle = $pageTitle ?? $siteName;
$bodyClass = $bodyClass ?? '';
$user = current_user();
$flashes = pull_flashes();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ed744a">
    <title><?= e($pageTitle) ?> · <?= e($siteName) ?></title>
    <link rel="icon" href="<?= asset('images/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url('index.php') ?>" aria-label="Mzansi Gem home">
            <span class="brand-mark"><i class="bi bi-compass-fill" aria-hidden="true"></i></span>
            <span><?= e($siteName) ?></span>
        </a>
        <nav class="desktop-nav" aria-label="Primary navigation">
            <a href="<?= url('index.php') ?>"><i class="bi bi-house-door"></i> Home</a>
            <a href="<?= url('discover.php') ?>"><i class="bi bi-search"></i> Discover</a>
            <?php if ($user): ?>
                <a href="<?= url('saved.php') ?>"><i class="bi bi-bookmark-heart"></i> Saved</a>
                <a href="<?= url('share.php') ?>"><i class="bi bi-plus-circle"></i> Share a Gem</a>
                <a href="<?= url('profile.php') ?>"><i class="bi bi-person-circle"></i> Profile</a>
                <?php if ($user['role'] === 'admin'): ?><a href="<?= url('admin/index.php') ?>"><i class="bi bi-shield-check"></i> Admin</a><?php endif; ?>
            <?php endif; ?>
        </nav>
        <div class="header-actions">
            <?php if ($user): ?>
                <a class="avatar-link" href="<?= url('profile.php') ?>" title="Open profile">
                    <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                    <span class="hide-small"><?= e(explode(' ', $user['full_name'])[0]) ?></span>
                </a>
            <?php else: ?>
                <a class="btn btn-ghost btn-sm" href="<?= url('login.php') ?>"><i class="bi bi-box-arrow-in-right"></i> Log in</a>
                <a class="btn btn-primary btn-sm" href="<?= url('register.php') ?>"><i class="bi bi-person-plus"></i> Sign up</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php if ($flashes): ?>
<div class="toast-stack" aria-live="polite">
    <?php foreach ($flashes as $flash): ?>
        <div class="toast toast-<?= e($flash['type']) ?>">
            <span><?= e($flash['message']) ?></span>
            <button type="button" data-dismiss-toast aria-label="Dismiss">×</button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
