<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/json');
$id = db()->query("SELECT id FROM gems WHERE status='approved' ORDER BY RAND() LIMIT 1")->fetchColumn();
echo json_encode(['url' => $id ? url('gem.php?id='.(int)$id) : url('discover.php')]);
