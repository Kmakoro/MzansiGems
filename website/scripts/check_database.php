<?php
declare(strict_types=1);

// Read-only CLI diagnostic. Never expose database diagnostics to web visitors.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

$required = [
    'users', 'gems', 'gem_images', 'reviews', 'saved_gems',
    'gem_likes', 'review_likes', 'reports', 'api_tokens', 'settings',
    'activity_log',
];

echo "Configured DB_NAME: " . DB_NAME . PHP_EOL;
echo "Configured DB_HOST: " . DB_HOST . PHP_EOL;
echo "Configured DB_PORT: " . DB_PORT . PHP_EOL;

$pdo = db();
$actualName = $pdo->query('SELECT DATABASE()')->fetchColumn();
echo "Connected database: " . (string)$actualName . PHP_EOL;

$stmt = $pdo->prepare(
    'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME'
);
$stmt->execute([DB_NAME]);
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
$missing = array_values(array_diff($required, $tables));

echo 'Existing tables: ' . (count($tables) ? implode(', ', $tables) : '(none)') . PHP_EOL;

if ($missing === []) {
    echo "PASS: required core tables exist." . PHP_EOL;
    exit(0);
}

echo 'MISSING TABLES: ' . implode(', ', $missing) . PHP_EOL;
echo 'Do NOT import install.sql into an existing database before backing up: it drops tables.' . PHP_EOL;
echo 'Check your databases in phpMyAdmin and ensure your PHP website and API use the correct DB_NAME.' . PHP_EOL;
exit(1);
