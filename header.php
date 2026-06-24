<?php $siteName = 'Global News & Portfolio Directory'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle ?? $siteName) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'Curated Turkish and English newspaper headlines with short summaries and sponsor links to portfolio companies.') ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonical ?? '') ?>">
  <link rel="stylesheet" href="assets/css/style.css">
  <?php if (!empty($extraHead)) echo $extraHead; ?>
  <script type="application/ld+json">
  {
    "@context":"https://schema.org",
    "@type":"WebSite",
    "name":"<?= htmlspecialchars($siteName) ?>",
    "description":"Curated news headlines and portfolio company directory.",
    "potentialAction":{"@type":"SearchAction","target":"/all-news.php?q={search_term_string}","query-input":"required name=search_term_string"}
  }
  </script>
</head>
<body>
<header class="topbar">
  <div class="wrap">
    <a class="logo" href="index.php">News<span>Directory</span></a>
    <nav class="nav" aria-label="Main navigation">
      <a href="index.php">Home</a>
      <a href="all-news.php">All News</a>
      <a href="sponsors.php">Portfolio Companies</a>
      <a href="about.php">About</a>
    </nav>
  </div>
</header>
