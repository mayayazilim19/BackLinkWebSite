<?php
// Run this once via browser or CLI: php db/setup.php
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT),
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS articles (
        id          VARCHAR(64)   PRIMARY KEY,
        title       TEXT          NOT NULL,
        summary     TEXT,
        content     MEDIUMTEXT,
        url         TEXT          NOT NULL,
        source      VARCHAR(200),
        language    VARCHAR(10),
        category    VARCHAR(100),
        published_at DATETIME,
        fetched_at  DATETIME      DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_published (published_at),
        INDEX idx_language  (language),
        INDEX idx_source    (source(100)),
        FULLTEXT  ft_text   (title, summary)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB");
    echo "OK — database and articles table created in '" . DB_NAME . "'.\n";
    echo "Edit db/config.php if you need different credentials.\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Check db/config.php — host, port, user, password.\n";
}
