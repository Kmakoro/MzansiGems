<?php $user = current_user(); $siteName = setting('site_name', APP_NAME); ?>
<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <strong><?= e($siteName) ?></strong>
            <p>Discover South Africa, one Mzansi Gem at a time.</p>
        </div>
        <div class="footer-links">
            <a href="<?= url('discover.php') ?>">Explore</a>
            <a href="<?= url('share.php') ?>">Share</a>
            <a href="<?= url('profile.php') ?>">Profile</a>
        </div>
        <small>© <?= date('Y') ?> <?= e($siteName) ?></small>
    </div>
</footer>
<nav class="mobile-nav" aria-label="Mobile navigation">
    <a href="<?= url('index.php') ?>"><i class="bi bi-house-door-fill"></i>Home</a>
    <a href="<?= url('discover.php') ?>"><i class="bi bi-search"></i>Search</a>
    <?php if ($user): ?>
        <a class="mobile-add" href="<?= url('share.php') ?>" aria-label="Share a Mzansi Gem"><i class="bi bi-plus-lg"></i></a>
        <a href="<?= url('saved.php') ?>"><i class="bi bi-bookmark-heart"></i>Saved</a>
        <a href="<?= url('profile.php') ?>"><i class="bi bi-person-circle"></i>Profile</a>
    <?php else: ?>
        <a class="mobile-add" href="<?= url('register.php') ?>" aria-label="Create account"><i class="bi bi-plus-lg"></i></a>
        <a href="<?= url('login.php') ?>"><i class="bi bi-box-arrow-in-right"></i>Login</a>
        <a href="<?= url('register.php') ?>"><i class="bi bi-person-plus"></i>Join</a>
    <?php endif; ?>
</nav>
<script>window.HIDDEN_GEMS = {baseUrl: <?= json_encode(APP_URL) ?>, csrf: <?= json_encode(csrf_token()) ?>};</script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
