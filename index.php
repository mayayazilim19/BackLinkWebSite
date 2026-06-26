<?php
$pageTitle       = 'Latest Turkish & English News | Portfolio Directory';
$pageDescription = 'Latest curated Turkish and English headlines with short summaries and direct links to original newspapers.';
include 'header.php';
?>
<section class="hero">
  <div class="wrap">
    <h1 data-i18n="hero.title">Turkish &amp; English News Headlines</h1>
    <p data-i18n="hero.sub">Fresh headlines, short summaries, and direct links to the original newspapers — plus a clean advertising area for your portfolio companies.</p>
  </div>
</section>
<section class="sponsor-strip">
  <div class="wrap"><strong data-i18n="sponsor.strip">Sponsored portfolio directory: promote real companies with honest descriptions and clearly marked links.</strong></div>
</section>
<main class="wrap layout">
  <?php include 'sidebar-left.php'; ?>
  <section class="main-col">
    <div class="toolbar">
      <input id="searchInput" class="search" type="search"
             placeholder="Search headline, source, or category"
             data-i18n-ph="search.ph">
      <div class="filters">
        <button class="chip active" data-filter="all" data-i18n="filter.all">All</button>
        <button class="chip"        data-filter="tr"  data-i18n="filter.tr">Turkish</button>
        <button class="chip"        data-filter="en"  data-i18n="filter.en">English</button>
      </div>
    </div>
    <div class="notice">
      <strong data-i18n="curated.label">Curated feed:</strong><span data-i18n="curated.text"> only headline, source, date, and short summary are shown. Use "Read full article" for the publisher page.</span>
      <span id="updatedAt" class="small"></span>
    </div>
    <div id="newsGrid" class="grid"><div class="notice" data-i18n-id="loadingMsg">Loading latest news…</div></div>
    <div id="pagination" class="pagination"></div>
  </section>
  <?php include 'sidebar-right.php'; ?>
</main>
<script>window.NEWS_PER_PAGE = 100;</script>
<script src="assets/js/app.js"></script>
<?php include 'footer.php'; ?>
