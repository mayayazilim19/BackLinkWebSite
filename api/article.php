<?php
header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__);
$id   = trim($_GET['id'] ?? '');

if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid id']);
    exit;
}

$cacheFile = $root . '/cache/news-cache.json';
if (file_exists($cacheFile)) {
    $data = json_decode(file_get_contents($cacheFile), true);
    foreach ($data['items'] ?? [] as $item) {
        if (($item['id'] ?? '') === $id) {
            echo json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

http_response_code(404);
echo json_encode(['error' => 'Article not found']);
