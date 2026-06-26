<?php
$root = __DIR__;
$id   = trim($_GET['id'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $id)) { header('Location: index.php'); exit; }

$article   = null;
$cacheFile = $root . '/cache/news-cache.json';
if (file_exists($cacheFile)) {
    $data = json_decode(file_get_contents($cacheFile), true);
    foreach ($data['items'] ?? [] as $item) {
        if (($item['id'] ?? '') === $id) { $article = $item; break; }
    }
}
if (!$article) { header('Location: index.php'); exit; }

$safeTitle   = htmlspecialchars($article['title']   ?? 'Article',   ENT_QUOTES, 'UTF-8');
$safeSummary = htmlspecialchars(mb_substr(strip_tags($article['summary'] ?? ''), 0, 160, 'UTF-8'), ENT_QUOTES, 'UTF-8');
$safeUrl     = htmlspecialchars($article['url']     ?? '',           ENT_QUOTES, 'UTF-8');
$safeSource  = htmlspecialchars($article['source']  ?? 'Source',    ENT_QUOTES, 'UTF-8');

$pageTitle       = $safeTitle . ' | NewsDirectory';
$pageDescription = $safeSummary;
$canonical       = 'article.php?id=' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8');

$schema = json_encode([
    '@context'      => 'https://schema.org',
    '@type'         => 'NewsArticle',
    'headline'      => $article['title']    ?? '',
    'description'   => mb_substr(strip_tags($article['summary'] ?? ''), 0, 250, 'UTF-8'),
    'url'           => $article['url']      ?? '',
    'datePublished' => $article['published'] ?? '',
    'publisher'     => ['@type' => 'Organization', 'name' => $article['source'] ?? ''],
    'inLanguage'    => $article['language'] ?? 'en',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$extraHead = '<script type="application/ld+json">' . $schema . '</script>';

$phpSources = json_decode(file_get_contents($root . '/data/sources.json'), true) ?: [];

$fmtDate = function(string $d): string {
    try { return (new DateTime($d))->format('d M Y, H:i'); } catch (Exception $e) { return $d; }
};

// Determine if we have rich body content beyond just the short summary
$body     = $article['body'] ?? '';
$hasRich  = $body && mb_strlen(strip_tags($body), 'UTF-8') > mb_strlen($article['summary'] ?? '', 'UTF-8') + 50;

include 'header.php';
?>
<main class="wrap layout">
  <?php include 'sidebar-left.php'; ?>

  <section class="main-col">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="index.php" data-i18n="art.home">Home</a> &rsaquo;
      <a href="all-news.php" data-i18n="art.allNews">All News</a> &rsaquo;
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

        <!-- Prominent link to original article — always visible at the top -->
        <a class="source-visit-btn" href="<?= $safeUrl ?>" target="_blank" rel="noopener nofollow">
          <span data-i18n="art.cta.btn">Read Full Article</span> at <?= $safeSource ?> &rarr;
        </a>
      </header>

      <?php if ($hasRich): ?>
        <!-- Full article body from RSS content:encoded -->
        <div class="article-body" itemprop="articleBody">
          <?= $body ?>
        </div>
      <?php elseif ($body): ?>
        <!-- RSS description (only content available) -->
        <div class="article-body" itemprop="description">
          <?= $body ?>
        </div>
      <?php endif; ?>

      <!-- Bottom CTA — link back to original -->
      <div class="article-cta">
        <p class="small" data-i18n="art.cta.text">This content is sourced from the RSS feed. For the complete article visit the original publisher:</p>
        <a class="btn btn-lg" href="<?= $safeUrl ?>" target="_blank" rel="noopener nofollow">
          <span data-i18n="art.cta.btn">Read Full Article</span> at <?= $safeSource ?> &rarr;
        </a>
      </div>

      <div class="article-nav">
        <a href="javascript:history.back()" class="back-link" data-i18n="art.back">&larr; Back</a>
        <a href="index.php"    class="back-link" data-i18n="art.home">Home</a>
        <a href="all-news.php" class="back-link" data-i18n="art.allNews">All News</a>
      </div>

    </article>
  </section>

  <?php include 'sidebar-right.php'; ?>
</main>

<script>
const escapeHtml = s => (s||'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
async function loadSponsors() {
    try {
        const sponsors = await fetch('api/sponsors.php').then(r => r.json());
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
