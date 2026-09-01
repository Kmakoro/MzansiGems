<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$featured = query_gems("g.status = 'approved'", [], 'g.average_rating DESC, g.review_count DESC', 6);
$pageTitle = 'Discover the hidden side of your city';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-content">
        <span class="eyebrow" style="color:#fff;opacity:.8">South Africa's local discovery community</span>
        <h1>Discover the hidden side of your city</h1>
        <p><?= e(setting('site_description', 'Explore lesser-known gems, authentic experiences, and community favourites across South Africa.')) ?></p>
        <form class="search-panel" action="<?= url('discover.php') ?>" method="get">
            <input name="q" aria-label="Search" placeholder="Search Mzansi Gem…">
            <select name="city" aria-label="City">
                <option value="">All cities</option>
                <option>Cape Town</option><option>Johannesburg</option><option>Durban</option><option>Pretoria</option>
            </select>
            <button class="btn btn-secondary" type="submit">Explore</button>
        </form>
        <div class="hero-actions">
            <button class="btn btn-ghost" type="button" data-surprise-me>✦ Surprise Me!</button>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Trending</span><h2>Trending Categories</h2></div></div>
        <div class="category-grid">
            <a class="category-card" href="<?= url('discover.php?vibe=Romantic') ?>"><span class="category-icon">♡</span><strong>Date Spots</strong></a>
            <a class="category-card" href="<?= url('discover.php?activity=Food') ?>"><span class="category-icon">☕</span><strong>Food & Drinks</strong></a>
            <a class="category-card" href="<?= url('discover.php?activity=Fitness') ?>"><span class="category-icon">♟</span><strong>Fitness</strong></a>
            <a class="category-card" href="<?= url('discover.php?activity=Culture') ?>"><span class="category-icon">◉</span><strong>Culture</strong></a>
            <a class="category-card" href="<?= url('discover.php?activity=Nightlife') ?>"><span class="category-icon">◔</span><strong>Nightlife</strong></a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div><span class="eyebrow">Community favourites</span><h2>Featured on Mzansi Gem</h2><p class="muted">Highly rated places worth adding to your list.</p></div>
            <a class="btn btn-ghost btn-sm" href="<?= url('discover.php') ?>">View all gems →</a>
        </div>
        <div class="gem-grid">
            <?php foreach ($featured as $gem) { require __DIR__ . '/includes/gem-card.php'; } ?>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="community-cta">
            <h2>Join Our Community</h2>
            <p>Share your favourite spots, earn badges, and help others discover the hidden side of South Africa.</p>
            <?php if (is_logged_in()): ?>
                <a class="btn btn-ghost" href="<?= url('share.php') ?>">Share a Mzansi Gem</a>
                <a class="btn btn-ghost" href="<?= url('profile.php') ?>">View Your Profile</a>
            <?php else: ?>
                <a class="btn btn-ghost" href="<?= url('register.php') ?>">Create an Account</a>
                <a class="btn btn-ghost" href="<?= url('login.php') ?>">Sign In</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
