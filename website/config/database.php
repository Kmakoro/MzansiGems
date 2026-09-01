<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        $message = APP_ENV === 'development'
            ? 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            : 'Database connection failed.';
        exit('<div style="font-family:Arial;padding:2rem;max-width:760px;margin:auto"><h1>Mzansi Gem setup required</h1><p>' . $message . '</p><p>Import <code>database/install.sql</code> and check <code>config/config.php</code> or your environment variables.</p></div>');
    }

    return $pdo;
}
