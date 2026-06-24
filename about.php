<?php $pageTitle='About | News Directory'; $pageDescription='About this curated news and portfolio company directory.'; include 'header.php'; ?>
<section class="hero"><div class="wrap"><h1>About This Site</h1><p>A simple deployable news directory designed for RSS headlines, publisher attribution, and portfolio-company visibility.</p></div></section>
<main class="wrap" style="padding-top:22px;padding-bottom:34px">
  <article class="feature-card" style="padding:22px">
    <h2>Editorial and SEO approach</h2>
    <p>This site shows headlines and short summaries from configured RSS feeds, then sends readers to the original publisher for the full article. Portfolio links should be genuine sponsor/directory listings, not manipulative backlink blocks.</p>
    <p>To customize the site, edit <code>data/sources.json</code> and <code>data/sponsors.json</code>. Replace the site name, domain, and metadata in <code>header.php</code>.</p>
  </article>
</main>
<?php include 'footer.php'; ?>
