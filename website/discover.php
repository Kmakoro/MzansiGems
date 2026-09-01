<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$q = trim((string)($_GET['q'] ?? ''));
$city = trim((string)($_GET['city'] ?? ''));
$vibe = trim((string)($_GET['vibe'] ?? ''));
$budget = trim((string)($_GET['budget'] ?? ''));
$activity = trim((string)($_GET['activity'] ?? ''));
$where = ["g.status = 'approved'"];
$params = [];
if ($q !== '') { $where[] = '(g.title LIKE ? OR g.description LIKE ? OR g.location LIKE ? OR g.city LIKE ?)'; $like = '%' . $q . '%'; array_push($params,$like,$like,$like,$like); }
if ($city !== '') { $where[] = 'g.city = ?'; $params[] = $city; }
if ($vibe !== '') { $where[] = 'FIND_IN_SET(?, REPLACE(g.vibes, ", ", ",")) > 0'; $params[] = $vibe; }
if ($budget !== '') { $where[] = 'g.budget_level = ?'; $params[] = $budget; }
if ($activity !== '') { $where[] = 'g.activity_type = ?'; $params[] = $activity; }
$gems = query_gems(implode(' AND ', $where), $params, 'g.average_rating DESC, g.created_at DESC', 60);
$recommended = [];
if (is_logged_in() && $q === '' && $city === '' && $vibe === '' && $budget === '' && $activity === '' && (int)current_user()['personalized_recommendations'] === 1) {
    $stmt = db()->prepare("SELECT activity_type,COUNT(*) AS total FROM (SELECT g.activity_type FROM saved_gems sg JOIN gems g ON g.id=sg.gem_id WHERE sg.user_id=? UNION ALL SELECT g.activity_type FROM gem_likes gl JOIN gems g ON g.id=gl.gem_id WHERE gl.user_id=?) interests GROUP BY activity_type ORDER BY total DESC LIMIT 2");
    $stmt->execute([(int)current_user()['id'],(int)current_user()['id']]);
    $types = array_column($stmt->fetchAll(), 'activity_type');
    if ($types) {
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $recommended = query_gems("g.status='approved' AND g.activity_type IN ($placeholders)", $types, 'g.average_rating DESC', 3);
    } else {
        $recommended = query_gems("g.status='approved'", [], 'g.average_rating DESC', 3);
    }
}

$pageTitle='Discover Mzansi Gem'; require __DIR__.'/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Discover</span><h1>Find your next Mzansi Gem</h1><p class="muted">Search by city, vibe, budget and activity.</p>
<form class="filters" method="get">
<div class="filter-grid">
<div class="form-group search-wide"><label>Keyword or location</label><input class="form-control" name="q" value="<?=e($q)?>" placeholder="Coffee, rooftop, Braamfontein…"></div>
<div class="form-group"><label>City</label><select class="form-control" name="city"><option value="">All Cities</option><?php foreach(['Cape Town','Johannesburg','Durban','Pretoria'] as $option):?><option value="<?=e($option)?>" <?=$city===$option?'selected':''?>><?=e($option)?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Vibe</label><select class="form-control" name="vibe"><option value="">All Vibes</option><?php foreach(['Chill','Romantic','Adventurous','Social','Quiet'] as $option):?><option <?=$vibe===$option?'selected':''?>><?=e($option)?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Budget</label><select class="form-control" name="budget"><option value="">All Budgets</option><?php foreach(['Free','Budget','Mid-range','Premium'] as $option):?><option <?=$budget===$option?'selected':''?>><?=e($option)?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Activity</label><select class="form-control" name="activity"><option value="">All Activities</option><?php foreach(['Food','Fitness','Study','Nature','Nightlife','Culture'] as $option):?><option <?=$activity===$option?'selected':''?>><?=e($option)?></option><?php endforeach;?></select></div>
</div><div class="filter-actions"><span class="result-count"><?=count($gems)?> gem<?=count($gems)===1?'':'s'?> found</span><div class="action-row"><a class="btn btn-ghost btn-sm" href="<?=url('discover.php')?>">Clear all filters</a><button class="btn btn-primary btn-sm">Apply Filters</button></div></div>
</form></div></section>
<section class="section-tight"><div class="container">
<?php if($recommended):?><div class="section-head"><div><span class="eyebrow">Personalised</span><h2>Recommended For You</h2><p class="muted">Based on the kinds of gems you save.</p></div></div><div class="gem-grid" style="margin-bottom:38px"><?php foreach($recommended as $gem){require __DIR__.'/includes/gem-card.php';}?></div><div class="section-head"><div><span class="eyebrow">All results</span><h2>Explore More Gems</h2></div></div><?php endif;?>
<?php if($gems):?><div class="gem-grid"><?php foreach($gems as $gem){require __DIR__.'/includes/gem-card.php';}?></div><?php else:?><div class="empty-state"><div class="empty-icon">⌕</div><h2>No gems matched those filters</h2><p class="muted">Try a different city, vibe, activity, or a broader search phrase.</p><a class="btn btn-primary" href="<?=url('discover.php')?>">View all gems</a></div><?php endif;?>
</div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
