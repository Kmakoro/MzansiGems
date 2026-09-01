<?php
/** @var array $gem */
$compact = $compact ?? false;
?>
<article class="gem-card<?= $compact ? ' gem-card-compact' : '' ?>">
    <a class="gem-image-wrap" href="<?= url('gem.php?id=' . (int)$gem['id']) ?>">
        <img class="gem-image" src="<?= e(gem_image_url($gem)) ?>" alt="<?= e($gem['title']) ?>" loading="lazy" onerror="this.onerror=null;this.src='<?= asset('images/gem-placeholder.svg') ?>'">
        <span class="budget-pill"><?= e($gem['budget_level']) ?></span>
    </a>
    <div class="gem-card-body">
        <div class="gem-card-heading">
            <div>
                <h3><a href="<?= url('gem.php?id=' . (int)$gem['id']) ?>"><?= e($gem['title']) ?></a></h3>
                <p class="muted"><i class="bi bi-geo-alt"></i> <?= e($gem['location']) ?></p>
            </div>
            <?php if (is_logged_in()): ?>
                <form method="post" action="<?= url('toggle-save.php') ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="gem_id" value="<?= (int)$gem['id'] ?>">
                    <input type="hidden" name="return_to" value="<?= e(ltrim($_SERVER['REQUEST_URI'] ?? 'index.php', '/')) ?>">
                    <button class="icon-btn<?= is_gem_saved((int)$gem['id']) ? ' active' : '' ?>" title="Save gem" aria-label="Save gem"><i class="bi bi-bookmark-heart-fill"></i></button>
                </form>
            <?php endif; ?>
        </div>
        <div class="tag-row">
            <?php foreach (array_slice(tag_list($gem['vibes']), 0, 3) as $tag): ?><span class="tag"><?= e($tag) ?></span><?php endforeach; ?>
            <span class="tag"><?= e($gem['activity_type']) ?></span>
        </div>
        <div class="gem-meta-row">
            <span class="rating"><span class="stars"><?= e(render_stars((float)$gem['average_rating'])) ?></span> <?= number_format((float)$gem['average_rating'], 1) ?></span>
            <span><i class="bi bi-chat-dots"></i> <?= (int)$gem['review_count'] ?></span>
            <span><i class="bi bi-bookmark-heart"></i> <?= (int)($gem['saves'] ?? 0) ?></span>
        </div>
    </div>
</article>
