<?php $pageTitle='All News | Turkish & English News Directory'; $pageDescription='Browse all curated headlines from Turkish and English news sources.'; include 'header.php'; ?>
<section class="hero"><div class="wrap"><h1>All News</h1><p>Browse the complete list of currently cached headlines from configured RSS sources.</p></div></section>
<main class="wrap layout">
  <?php include 'sidebar-left.php'; ?>
  <section class="main-col">
    <div class="toolbar">
      <input id="searchInput" class="search" type="search" placeholder="Search all news">
      <div class="filters"><button class="chip active" data-filter="all">All</button><button class="chip" data-filter="tr" id="turkish">Turkish</button><button class="chip" data-filter="en" id="english">English</button></div>
    </div>
    <div id="newsGrid" class="grid"><div class="notice">Loading all news…</div></div>
    <div id="pagination" class="pagination"></div>
  </section>
  <?php include 'sidebar-right.php'; ?>
</main>
<script src="assets/js/app.js"></script>
<?php include 'footer.php'; ?>
