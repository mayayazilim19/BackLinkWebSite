<?php
if (empty($phpSources)) {
    $phpSources = json_decode(file_get_contents(__DIR__ . '/data/sources.json'), true) ?: [];
}
$trSources = array_values(array_filter($phpSources, fn($s) => ($s['language'] ?? '') === 'tr'));
$enSources = array_values(array_filter($phpSources, fn($s) => ($s['language'] ?? '') === 'en'));
?>
<aside class="left-col panel">
  <h3 data-i18n="sidebar.sources">News Sources</h3>

  <?php if ($trSources): ?>
    <p class="source-group-label" data-i18n="src.group.tr">Turkish Newspapers</p>
    <ul class="source-list">
      <?php foreach ($trSources as $src): ?>
        <li>
          <a class="source-link" href="#"
             data-source="<?= htmlspecialchars($src['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($src['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($enSources): ?>
    <p class="source-group-label" data-i18n="src.group.en">English Newspapers</p>
    <ul class="source-list">
      <?php foreach ($enSources as $src): ?>
        <li>
          <a class="source-link" href="#"
             data-source="<?= htmlspecialchars($src['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($src['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <hr>
  <h3 data-i18n="sidebar.links">Quick Links</h3>
  <ul class="source-list">
    <li><a href="all-news.php"         data-i18n="sidebar.allNews">All News</a></li>
    <li><a href="all-news.php?lang=tr" data-i18n="sidebar.trNews">Turkish News</a></li>
    <li><a href="all-news.php?lang=en" data-i18n="sidebar.enNews">English News</a></li>
  </ul>
</aside>
