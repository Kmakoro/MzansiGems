<?php
declare(strict_types=1);
require dirname(__DIR__).'/includes/bootstrap.php';require_admin();
$months=[];$growth=[];for($i=6;$i>=0;$i--){$date=new DateTimeImmutable("first day of -{$i} month");$months[]=$date->format('M');$stmt=db()->prepare('SELECT COUNT(*) FROM users WHERE created_at < ?');$stmt->execute([$date->modify('+1 month')->format('Y-m-d')]);$growth[]=(int)$stmt->fetchColumn();}
$engagement=[
 'Posts'=>(int)db()->query('SELECT COUNT(*) FROM gems')->fetchColumn(),
 'Comments'=>(int)db()->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
 'Likes'=>(int)db()->query('SELECT COUNT(*) FROM gem_likes')->fetchColumn(),
 'Saves'=>(int)db()->query('SELECT COUNT(*) FROM saved_gems')->fetchColumn(),
];
$stmt=db()->query("SELECT activity_type,COUNT(*) AS total FROM gems WHERE status='approved' GROUP BY activity_type ORDER BY total DESC");$categoryRows=$stmt->fetchAll();$categoryLabels=array_column($categoryRows,'activity_type');$categoryData=array_map('intval',array_column($categoryRows,'total'));$categoryTotal=max(1,array_sum($categoryData));
$pageTitle='Analytics';$activeAdmin='analytics';require dirname(__DIR__).'/includes/admin-header.php';
?>
<div class="admin-page-head"><div><span class="eyebrow">Platform insights</span><h1>Analytics</h1><p class="muted">Monitor user growth, engagement and category performance.</p></div><button class="btn btn-ghost" onclick="window.print()">Print Report</button></div>
<div class="metric-grid"><?php foreach($engagement as $label=>$value):?><div class="metric-card"><span><?=e($label)?></span><strong><?=(int)$value?></strong><small>Recorded interactions</small></div><?php endforeach;?></div>
<div class="analytics-grid"><section class="chart-card wide"><h2>User Growth</h2><canvas id="growthChart"></canvas></section><section class="chart-card"><h2>Engagement Metrics</h2><canvas id="engagementChart"></canvas></section><section class="chart-card"><h2>Popular Categories</h2><canvas id="categoryChart"></canvas></section><section class="admin-card wide"><h2>Category Breakdown</h2><div class="table-wrap"><table class="data-table"><thead><tr><th>Category</th><th>Posts</th><th>Share</th></tr></thead><tbody><?php foreach($categoryRows as $row):?><tr><td><?=e($row['activity_type'])?></td><td><?=(int)$row['total']?></td><td><?=number_format((int)$row['total']/$categoryTotal*100,1)?>%</td></tr><?php endforeach;?></tbody></table></div></section></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const orange='#ed744a',teal='#0c4b59',grid='rgba(80,70,65,.08)';
 new Chart(document.getElementById('growthChart'),{type:'line',data:{labels:<?=json_encode($months)?>,datasets:[{label:'Total users',data:<?=json_encode($growth)?>,borderColor:orange,backgroundColor:'rgba(237,116,74,.12)',fill:true,tension:.35}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,grid:{color:grid}},x:{grid:{display:false}}}}});
 new Chart(document.getElementById('engagementChart'),{type:'bar',data:{labels:<?=json_encode(array_keys($engagement))?>,datasets:[{label:'Interactions',data:<?=json_encode(array_values($engagement))?>,backgroundColor:orange,borderRadius:8}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:grid}},x:{grid:{display:false}}}}});
 new Chart(document.getElementById('categoryChart'),{type:'doughnut',data:{labels:<?=json_encode($categoryLabels)?>,datasets:[{data:<?=json_encode($categoryData)?>}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
});
</script>
<?php require dirname(__DIR__).'/includes/admin-footer.php'; ?>
