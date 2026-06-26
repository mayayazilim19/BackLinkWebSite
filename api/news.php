<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$root        = dirname(__DIR__);
$sourcesFile = $root . '/data/sources.json';
$cacheDir    = $root . '/cache';
$cacheFile   = $cacheDir . '/news-cache.json';
$cacheTtl    = 900; // 15 minutes

if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }

/* ── helpers ─────────────────────────────────────────── */
function clean_text(string $t): string {
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = strip_tags($t);
    return trim(preg_replace('/\s+/', ' ', $t));
}

function sanitize_html(string $html): string {
    // Strip dangerous elements entirely
    $html = preg_replace('/<(script|style|iframe|object|embed|form)[^>]*>.*?<\/\1>/is', '', $html);
    // Keep safe formatting tags only
    $allowed = '<p><br><b><strong><i><em><ul><ol><li><h2><h3><h4><h5><blockquote><a><figure><figcaption><img>';
    $html = strip_tags($html, $allowed);
    // Remove event handlers and javascript: hrefs
    $html = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $html);
    $html = preg_replace("/\s+on\w+\s*=\s*'[^']*'/i", '', $html);
    $html = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $html);
    return trim($html);
}

function short_summary(string $t, int $max = 300): string {
    $t = clean_text($t);
    return mb_strlen($t, 'UTF-8') <= $max ? $t : mb_substr($t, 0, $max, 'UTF-8') . '…';
}

/* ── serve from cache if fresh ───────────────────────── */
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    echo file_get_contents($cacheFile);
    exit;
}

$sources = json_decode(file_get_contents($sourcesFile), true) ?: [];
$items   = [];

foreach ($sources as $source) {
    $feedUrl = $source['feed'] ?? '';
    if (!$feedUrl) continue;

    $ctx = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'NewsBot/1.0']]);
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

        // Try content:encoded for full article HTML (most professional RSS feeds include this)
        $bodyHtml = '';
        if (isset($ns['content'])) {
            $cn = $entry->children($ns['content']);
            if (isset($cn->encoded) && trim((string)$cn->encoded) !== '') {
                $bodyHtml = sanitize_html((string)$cn->encoded);
            }
        }
        // Fall back: use full description text if no content:encoded
        if (!$bodyHtml) {
            $fullDesc = clean_text($descRaw);
            if ($fullDesc) {
                $bodyHtml = '<p>' . implode('</p><p>', array_filter(array_map('trim', explode("\n", htmlspecialchars($fullDesc, ENT_QUOTES, 'UTF-8'))))) . '</p>';
            }
        }

        $dateRaw = clean_text((string)($entry->pubDate ?? ($entry->updated ?? ($entry->published ?? ''))));
        $ts      = ($dateRaw ? strtotime($dateRaw) : 0) ?: time();

        $items[] = [
            'id'        => md5($link),
            'title'     => $title,
            'summary'   => short_summary($descRaw),   // short — for the list cards
            'body'      => $bodyHtml,                  // full content — for article page
            'url'       => $link,
            'source'    => $source['name']     ?? 'Unknown',
            'language'  => $source['language'] ?? '',
            'category'  => $source['category'] ?? 'News',
            'published' => date(DATE_ATOM, $ts),
        ];
    }
}

usort($items, fn($a, $b) => strtotime($b['published']) <=> strtotime($a['published']));

$out = json_encode(
    ['updated_at' => date(DATE_ATOM), 'count' => count($items), 'items' => $items],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);

file_put_contents($cacheFile, $out);
echo $out;
