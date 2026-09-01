<?php
declare(strict_types=1);

const APP_NAME = 'Mzansi Gem';
const APP_TIMEZONE = 'Africa/Johannesburg';
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
const MAX_GEM_IMAGES = 3;

date_default_timezone_set(APP_TIMEZONE);

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
$scriptDir = trim(dirname($scriptName), '/.');
$parts = $scriptDir === '' ? [] : explode('/', $scriptDir);
foreach (['admin', 'api'] as $specialDirectory) {
    $position = array_search($specialDirectory, $parts, true);
    if ($position !== false) {
        $parts = array_slice($parts, 0, $position);
        break;
    }
}
$basePath = $parts ? '/' . implode('/', $parts) : '';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

define('APP_URL', rtrim((string)(getenv('APP_URL') ?: $scheme . '://' . $host . $basePath), '/'));
define('APP_ENV', (string)(getenv('APP_ENV') ?: 'development'));
define('DB_HOST', (string)(getenv('DB_HOST') ?: '127.0.0.1'));
define('DB_PORT', (string)(getenv('DB_PORT') ?: '3306'));
define('DB_NAME', (string)(getenv('DB_NAME') ?: 'hidden_gems'));
define('DB_USER', (string)(getenv('DB_USER') ?: 'root'));
define('DB_PASS', (string)(getenv('DB_PASS') ?: ''));
define('GOOGLE_CLIENT_ID', (string)(getenv('GOOGLE_CLIENT_ID') ?: ''));
define('GOOGLE_CLIENT_SECRET', (string)(getenv('GOOGLE_CLIENT_SECRET') ?: ''));
define('MAIL_FROM', (string)(getenv('MAIL_FROM') ?: ''));
