<aside class="left-col panel">
  <h3>News Sources</h3>
  <?php if (!empty($phpSources)): ?>
    <ul id="sourceList" class="source-list">
      <?php foreach ($phpSources as $src): ?>
        <li>
          <strong><?= htmlspecialchars($src['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
          <div class="small"><?= htmlspecialchars($src['category'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; <?= strtoupper(htmlspecialchars($src['language'] ?? '', ENT_QUOTES, 'UTF-8')) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <ul id="sourceList" class="source-list"><li class="small">Loading sources…</li></ul>
  <?php endif; ?>
  <hr>
  <h3>Quick Links</h3>
  <ul class="source-list">
    <li><a href="all-news.php">All News</a></li>
    <li><a href="all-news.php?lang=tr">Turkish News</a></li>
    <li><a href="all-news.php?lang=en">English News</a></li>
  </ul>
</aside>
