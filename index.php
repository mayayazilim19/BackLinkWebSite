<?php $pageTitle='Latest Turkish & English News | Portfolio Directory'; $pageDescription='Latest curated Turkish and English headlines with short summaries and direct links to original newspapers.'; include 'header.php'; ?>
<section class="hero"><div class="wrap"><h1>Turkish & English News Headlines</h1><p>Fresh headlines, short summaries, and direct links to the original newspapers — plus a clean advertising area for your portfolio companies.</p></div></section>
<section class="sponsor-strip"><div class="wrap"><strong>Sponsored portfolio directory:</strong> promote real companies with honest descriptions and clearly marked links.</div></section>
<main class="wrap layout">
  <?php include 'sidebar-left.php'; ?>
  <section class="main-col">
    <div class="toolbar">
      <input id="searchInput" class="search" type="search" placeholder="Search headline, source, or category">
      <div class="filters"><button class="chip active" data-filter="all">All</button><button class="chip" data-filter="tr">Turkish</button><button class="chip" data-filter="en">English</button></div>
    </div>
    <div class="notice"><strong>Curated feed:</strong> only headline, source, date, and short summary are shown. Use “Read full article” for the publisher page. <span id="updatedAt" class="small"></span></div>
    <div id="newsGrid" class="grid"><div class="notice">Loading latest news…</div></div>
    <div id="pagination" class="pagination"></div>
  </section>
  <?php include 'sidebar-right.php'; ?>
</main>
<script>window.NEWS_PER_PAGE=100;</script><script src="assets/js/app.js"></script>
<?php include 'footer.php'; ?>
