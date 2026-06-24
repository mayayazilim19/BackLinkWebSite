<?php
$root = __DIR__;
$id   = trim($_GET['id'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $id)) { header('Location: index.php'); exit; }

/* ── load article ───────────────────────────────────── */
$article = null;
$dbFile  = $root . '/db/database.php';
if (file_exists($dbFile)) {
    require_once $dbFile;
    $db = getDb();
    if ($db) {
        $stmt = $db->prepare('SELECT * FROM articles WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['published'] = (new DateTime($row['published_at']))->format(DATE_ATOM);
            $article = $row;
        }
    }
}
if (!$article) {
    $cacheFile = $root . '/cache/news-cache.json';
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        foreach ($data['items'] ?? [] as $item) {
            if (($item['id'] ?? md5($item['url'] ?? '')) === $id) { $article = $item; break; }
        }
    }
}
if (!$article) { header('Location: index.php'); exit; }

/* ── page meta ──────────────────────────────────────── */
$safeTitle   = htmlspecialchars($article['title']   ?? 'Article', ENT_QUOTES, 'UTF-8');
$safeSummary = htmlspecialchars(mb_substr(strip_tags($article['summary'] ?? ''), 0, 160, 'UTF-8'), ENT_QUOTES, 'UTF-8');
$safeUrl     = htmlspecialchars($article['url']     ?? '', ENT_QUOTES, 'UTF-8');
$safeSource  = htmlspecialchars($article['source']  ?? 'Source', ENT_QUOTES, 'UTF-8');

$pageTitle       = $safeTitle . ' | NewsDirectory';
$pageDescription = $safeSummary;
$canonical       = 'article.php?id=' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

// Schema.org for SEO — injected before </head> via $extraHead
$schema = json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'NewsArticle',
    'headline'      => $article['title'] ?? '',
    'description'   => mb_substr(strip_tags($article['summary'] ?? ''), 0, 250, 'UTF-8'),
    'url'           => $article['url'] ?? '',
    'datePublished' => $article['published'] ?? '',
    'publisher'     => ['@type' => 'Organization', 'name' => $article['source'] ?? ''],
    'inLanguage'    => $article['language'] ?? 'en',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$extraHead = '<script type="application/ld+json">' . $schema . '</script>';

$phpSources = json_decode(file_get_contents($root . '/data/sources.json'), true) ?: [];

$fmtDate = function(string $d): string {
    try { return (new DateTime($d))->format('d M Y, H:i'); } catch (Exception $e) { return $d; }
};

include 'header.php';
?>

<main class="wrap layout">
  <?php include 'sidebar-left.php'; ?>

  <section class="main-col">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php">Home</a> &rsaquo;
      <a href="all-news.php">All News</a> &rsaquo;
      <span><?= $safeSource ?></span>
    </nav>

    <article class="article-full panel" itemscope itemtype="https://schema.org/NewsArticle">

      <header class="article-header">
        <div class="news-meta">
          <span class="badge" itemprop="publisher"><?= $safeSource ?></span>
          <?php if (!empty($article['category'])): ?>
            <span><?= htmlspecialchars($article['category'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
          <?php if (!empty($article['language'])): ?>
            <span class="badge-lang"><?= strtoupper(htmlspecialchars($article['language'], ENT_QUOTES, 'UTF-8')) ?></span>
          <?php endif; ?>
          <time itemprop="datePublished" datetime="<?= htmlspecialchars($article['published'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?= $fmtDate($article['published'] ?? '') ?>
          </time>
        </div>
        <h1 itemprop="headline"><?= $safeTitle ?></h1>
      </header>

      <?php if (!empty($article['content'])): ?>
        <div class="article-content" itemprop="articleBody">
          <?= $article['content'] /* already sanitized server-side */ ?>
        </div>
      <?php elseif (!empty($article['summary'])): ?>
        <div class="article-summary" itemprop="description">
          <?= nl2br(htmlspecialchars($article['summary'], ENT_QUOTES, 'UTF-8')) ?>
        </div>
      <?php endif; ?>

      <div class="article-cta">
        <p class="small">This is the RSS summary. Read the complete story at the original publisher:</p>
        <a class="btn btn-lg" href="<?= $safeUrl ?>" target="_blank" rel="noopener nofollow">
          Read Full Article at <?= $safeSource ?> &rarr;
        </a>
      </div>

      <div class="article-nav">
        <a href="javascript:history.back()" class="back-link">&larr; Back</a>
        <a href="index.php" class="back-link">Home</a>
        <a href="all-news.php" class="back-link">All News</a>
      </div>

    </article>
  </section>

  <?php include 'sidebar-right.php'; ?>
</main>

<script>
/* minimal JS: sidebars only — no full news load needed on this page */
const escapeHtml = s => (s||'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
async function loadSponsors() {
    try {
        const res = await fetch('api/sponsors.php');
        const sponsors = await res.json();
        document.querySelectorAll('[data-sponsors]').forEach(slot => {
            slot.innerHTML = sponsors.map(s =>
                `<a class="sponsor-card" href="${escapeHtml(s.url)}" target="_blank" rel="sponsored noopener">
                    <strong>${escapeHtml(s.name)}</strong>
                    <em>${escapeHtml(s.category||'Sponsor')}</em>
                    <span class="small">${escapeHtml(s.description||'')}</span>
                </a>`
            ).join('');
        });
    } catch(e) {}
}
document.addEventListener('DOMContentLoaded', loadSponsors);
</script>

<?php include 'footer.php'; ?>
