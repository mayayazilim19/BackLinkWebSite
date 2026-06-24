<?php
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
$id   = trim($_GET['id'] ?? '');

if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid id']);
    exit;
}

$dbFile = $root . '/db/database.php';
if (file_exists($dbFile)) {
    require_once $dbFile;
    $db = getDb();
    if ($db) {
        $stmt = $db->prepare('SELECT * FROM articles WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['published'] = (new DateTime($row['published_at']))->format(DATE_ATOM);
            echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

// Fallback: JSON cache
$cacheFile = $root . '/cache/news-cache.json';
if (file_exists($cacheFile)) {
    $data = json_decode(file_get_contents($cacheFile), true);
    foreach ($data['items'] ?? [] as $item) {
        $itemId = $item['id'] ?? md5($item['url'] ?? '');
        if ($itemId === $id) {
            echo json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

http_response_code(404);
echo json_encode(['error' => 'Article not found']);
