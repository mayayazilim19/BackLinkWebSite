<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$root       = dirname(__DIR__);
$sourcesFile = $root . '/data/sources.json';
$cacheDir   = $root . '/cache';
$cacheFile  = $cacheDir . '/news-cache.json';
$cacheTtl   = 900; // 15 minutes

$page  = max(1, (int)($_GET['page']  ?? 1));
$limit = min(200, max(10, (int)($_GET['limit'] ?? 100)));
$lang  = in_array($_GET['lang'] ?? '', ['tr', 'en'], true) ? $_GET['lang'] : 'all';
$q     = trim($_GET['q'] ?? '');

if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }

$dbFile = $root . '/db/database.php';
$db = null;
if (file_exists($dbFile)) {
    require_once $dbFile;
    $db = getDb();
}

/* ── helpers ─────────────────────────────────────────── */
function clean_text(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = strip_tags($text);
    return trim(preg_replace('/\s+/', ' ', $text));
}

function sanitize_html(string $html): string {
    $html = preg_replace('/<(script|style|iframe|object|embed|form)[^>]*>.*?<\/\1>/is', '', $html);
    $allowed = '<p><br><b><strong><i><em><ul><ol><li><h2><h3><h4><blockquote><a><figure><figcaption>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
    $html = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $html);
    return trim($html);
}

function short_summary(string $text, int $max = 500): string {
    $text = clean_text($text);
    if (mb_strlen($text, 'UTF-8') <= $max) return $text;
    return mb_substr($text, 0, $max, 'UTF-8') . '…';
}

/* ── fetch RSS and persist ───────────────────────────── */
function fetchAndStore(array $sources, ?PDO $db, string $cacheFile): array {
    $items = [];
    foreach ($sources as $source) {
        $feedUrl = $source['feed'] ?? '';
        if (!$feedUrl) continue;
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'NewsDirectoryBot/1.0']]);
        $xml_str = @file_get_contents($feedUrl, false, $ctx);
        if (!$xml_str) continue;
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$xml) continue;
        $ns      = $xml->getNamespaces(true);
        $entries = $xml->channel->item ?? ($xml->entry ?? []);

        foreach ($entries as $entry) {
            $title = clean_text((string)($entry->title ?? ''));
            $link  = clean_text((string)($entry->link  ?? ''));
            if (!$link && isset($entry->link['href'])) $link = (string)$entry->link['href'];
            if (!$title || !$link) continue;

            $descRaw = (string)($entry->description ?? ($entry->summary ?? ''));
            $content = '';
            if (isset($ns['content'])) {
                $cn = $entry->children($ns['content']);
                if (isset($cn->encoded)) $content = sanitize_html((string)$cn->encoded);
            }
            $summary  = short_summary($descRaw);
            $dateRaw  = clean_text((string)($entry->pubDate ?? ($entry->updated ?? ($entry->published ?? ''))));
            $ts       = ($dateRaw ? strtotime($dateRaw) : 0) ?: time();
            $id       = md5($link);
            $pubAtom  = date(DATE_ATOM, $ts);
            $pubMysql = date('Y-m-d H:i:s', $ts);

            $items[] = [
                'id'           => $id,
                'title'        => $title,
                'summary'      => $summary,
                'content'      => $content,
                'url'          => $link,
                'source'       => $source['name']     ?? 'Unknown',
                'language'     => $source['language'] ?? '',
                'category'     => $source['category'] ?? 'News',
                'published'    => $pubAtom,
                'published_at' => $pubMysql,
            ];

            if ($db) {
                try {
                    $db->prepare("INSERT INTO articles
                        (id,title,summary,content,url,source,language,category,published_at)
                        VALUES (?,?,?,?,?,?,?,?,?)
                        ON DUPLICATE KEY UPDATE fetched_at=fetched_at")
                       ->execute([$id,$title,$summary,$content,$link,
                                  $source['name']??'Unknown',
                                  $source['language']??'',
                                  $source['category']??'News',
                                  $pubMysql]);
                } catch (PDOException $e) { /* ignore dup */ }
            }
        }
    }

    usort($items, fn($a,$b) => strtotime($b['published']) <=> strtotime($a['published']));

    // Keep JSON cache for backward-compat (top 100 only, without published_at)
    $cacheItems = array_slice(array_map(function($i){ unset($i['published_at']); return $i; }, $items), 0, 100);
    file_put_contents($cacheFile, json_encode(
        ['updated_at' => date(DATE_ATOM), 'count' => count($cacheItems), 'items' => $cacheItems],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    ));
    return $items;
}

/* ── main logic ──────────────────────────────────────── */
$sources    = json_decode(file_get_contents($sourcesFile), true) ?: [];
$needsFetch = !file_exists($cacheFile) || (time() - filemtime($cacheFile) >= $cacheTtl);

if ($db) {
    if ($needsFetch) fetchAndStore($sources, $db, $cacheFile);

    $where = []; $params = [];
    if ($lang !== 'all')  { $where[] = 'language = ?';              $params[] = $lang; }
    if ($q !== '')        { $where[] = '(title LIKE ? OR summary LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
    $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = (int)$db->prepare("SELECT COUNT(*) FROM articles $whereStr")->execute($params) ? null : 0;
    $stmt  = $db->prepare("SELECT COUNT(*) FROM articles $whereStr"); $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $pages  = max(1, (int)ceil($total / $limit));
    $offset = ($page - 1) * $limit;

    $stmt = $db->prepare("SELECT id,title,summary,url,source,language,category,published_at
                           FROM articles $whereStr ORDER BY published_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $items = array_map(function($r) {
        $r['published'] = (new DateTime($r['published_at']))->format(DATE_ATOM);
        unset($r['published_at']);
        return $r;
    }, $stmt->fetchAll());

    $updatedAt = file_exists($cacheFile)
        ? (json_decode(file_get_contents($cacheFile), true)['updated_at'] ?? date(DATE_ATOM))
        : date(DATE_ATOM);

    echo json_encode([
        'updated_at' => $updatedAt,
        'count'      => count($items),
        'total'      => $total,
        'page'       => $page,
        'pages'      => $pages,
        'limit'      => $limit,
        'items'      => $items,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/* ── no DB: JSON cache fallback ──────────────────────── */
if ($needsFetch) fetchAndStore($sources, null, $cacheFile);

$cached   = json_decode(file_get_contents($cacheFile), true) ?: [];
$allItems = $cached['items'] ?? [];

// Ensure id field exists on cached items
foreach ($allItems as &$item) {
    if (empty($item['id'])) $item['id'] = md5($item['url'] ?? '');
}
unset($item);

if ($lang !== 'all') $allItems = array_values(array_filter($allItems, fn($i) => $i['language'] === $lang));
if ($q !== '') {
    $ql = mb_strtolower($q, 'UTF-8');
    $allItems = array_values(array_filter($allItems, function($i) use($ql) {
        return str_contains(mb_strtolower($i['title'].' '.$i['summary'].' '.$i['source'], 'UTF-8'), $ql);
    }));
}

$total = count($allItems);
$pages = max(1, (int)ceil($total / $limit));
$items = array_slice($allItems, ($page - 1) * $limit, $limit);

echo json_encode([
    'updated_at' => $cached['updated_at'] ?? date(DATE_ATOM),
    'count'      => count($items),
    'total'      => $total,
    'page'       => $page,
    'pages'      => $pages,
    'limit'      => $limit,
    'items'      => $items,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
