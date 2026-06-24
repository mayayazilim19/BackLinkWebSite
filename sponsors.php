<?php $pageTitle='Portfolio Companies | Sponsored Directory'; $pageDescription='A directory of sponsored portfolio companies with direct website links.'; include 'header.php'; ?>
<section class="hero"><div class="wrap"><h1>Portfolio Companies</h1><p>Use this page to promote your companies with original descriptions, categories, and sponsored links.</p></div></section>
<main class="wrap" style="padding-top:22px;padding-bottom:34px">
  <div class="notice">Edit <code>data/sponsors.json</code> to add your real company names, URLs, categories, and descriptions.</div>
  <section class="grid" data-sponsors></section>
</main>
<script src="assets/js/app.js"></script>
<?php include 'footer.php'; ?>
