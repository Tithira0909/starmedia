<?php 
// index.php — public site, DB-driven issues → standard PDF viewer
declare(strict_types=1);

ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

/* Helper to resolve PDF path */
function resolvePdfPath($filename) {
    if (!$filename) return '';
    $dirs = ['assets/magazines', 'assets/news_flash', 'assets/exclusive_magazines', 'assets/ozlanka_magazines'];
    foreach ($dirs as $dir) {
        $path = $dir . '/' . $filename;
        if (file_exists(__DIR__ . '/' . $path)) {
            return $path;
        }
    }
    // Check if it's already a path
    if (file_exists(__DIR__ . '/' . $filename)) {
        return $filename;
    }
    return '';
}

/* 1) Fetch magazines from DB (latest first) */
$rows = pdo()->query("
  SELECT id, label, pdf_file, published_at, author_note, banner_file
  FROM magazines
  ORDER BY sort_order ASC, COALESCE(published_at, '1970-01-01') DESC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* 2) Normalize for template */
$issues = [];
foreach ($rows as $r) {
  $file   = basename((string)($r['pdf_file'] ?? ''));
  $banner = (string)($r['banner_file'] ?? '');
  $cover  = $banner ?: 'assets/covers/logo.png';
  $url    = resolvePdfPath($file);

  $issues[] = [
    'id'          => (int)($r['id'] ?? 0),
    'label'       => (string)($r['label'] ?? ''),
    'pdf'         => (string)($r['pdf_file'] ?? ''),
    'file'        => $file,
    'url'         => $url,
    'cover'       => $cover,
    'when'        => (string)($r['published_at'] ?? ''),
    'author_note' => (string)($r['author_note'] ?? ''),
  ];
}

/* 1b) Fetch newsflashes from DB */
$newsflash_rows = [];
try {
    $newsflash_rows = pdo()->query("
      SELECT id, label, pdf_file, published_at, author_note, banner_file
      FROM news_flash
      ORDER BY sort_order ASC, COALESCE(published_at, '1970-01-01') DESC, id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ignore
}

/* 2b) Normalize for template */
$newsflashes = [];
foreach ($newsflash_rows as $r) {
  $file   = basename((string)($r['pdf_file'] ?? ''));
  $banner = (string)($r['banner_file'] ?? '');
  $cover  = $banner ?: 'assets/covers/logo.png';
  $url    = resolvePdfPath($file);

  $newsflashes[] = [
    'id'          => (int)($r['id'] ?? 0),
    'label'       => (string)($r['label'] ?? ''),
    'pdf'         => (string)($r['pdf_file'] ?? ''),
    'file'        => $file,
    'url'         => $url,
    'cover'       => $cover,
    'when'        => (string)($r['published_at'] ?? ''),
    'author_note' => (string)($r['author_note'] ?? ''),
  ];
}

/* 1d) Fetch OzLanka magazines from DB */
$ozlanka_magazines = [];
try {
  $stmt = pdo()->prepare("
      SELECT id, title, pdf_file, banner_file, label, is_published, published_at
      FROM ozlanka_magazines
      WHERE is_published = 1
      ORDER BY sort_order ASC, published_at DESC, id DESC
  ");
  $stmt->execute();
  foreach ($stmt as $row) {
    $pdfPath = trim($row['pdf_file']);
    $file    = basename($pdfPath);
    $thumb   = trim($row['banner_file']);
    $url     = resolvePdfPath($file);

    $ozlanka_magazines[] = [
      'id'    => $row['id'],
      'label' => $row['label'] ?: 'Unlabeled',
      'title' => $row['title'] ?: 'Untitled',
      'pdf'   => $pdfPath,
      'file'  => $file,
      'url'   => $url,
      'cover' => $thumb,
    ];
  }
} catch (PDOException $e) {}
/* 1c) Fetch exclusive magazines from DB */
$exclusive_rows = [];
try {
    $exclusive_rows = pdo()->query("
      SELECT id, label, pdf_file, published_at, author_note, banner_file
      FROM exclusive_magazines
      ORDER BY sort_order ASC, COALESCE(published_at, '1970-01-01') DESC, id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ignore
}

/* 2c) Normalize for template */
$exclusives = [];
foreach ($exclusive_rows as $r) {
  $file   = basename((string)($r['pdf_file'] ?? ''));
  $banner = (string)($r['banner_file'] ?? '');
  $cover  = $banner ?: 'assets/covers/logo.png';
  $url    = resolvePdfPath($file);

  $exclusives[] = [
    'id'          => (int)($r['id'] ?? 0),
    'label'       => (string)($r['label'] ?? ''),
    'pdf'         => (string)($r['pdf_file'] ?? ''),
    'file'        => $file,
    'url'         => $url,
    'cover'       => $cover,
    'when'        => (string)($r['published_at'] ?? ''),
    'author_note' => (string)($r['author_note'] ?? ''),
  ];
}

/* 3) Current issue for viewer (latest) */
$currentUrl    = $issues[0]['url']  ?? '';
$currentFile   = $issues[0]['file'] ?? '';

/* Fetch all quick news */
$quickNews = [];
try {
    $quickNews = pdo()->query("SELECT image_file, news_text FROM quick_news ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ignore
}

/* 4) Current author note (for initial render) */
$authorNote = $issues[0]['author_note'] ?? '';

/* 5) For asset paths in header */
$siteBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

/* Fetch YouTube URL */
$youtubeUrl = '';
try {
    $stmt = pdo()->prepare("SELECT value FROM site_settings WHERE key_name = 'youtube_video_url'");
    $stmt->execute();
    $youtubeUrl = $stmt->fetchColumn() ?: '';
} catch (PDOException $e) {
    // ignore
}

// Convert to embed URL if necessary
$youtubeEmbed = '';
if ($youtubeUrl) {
    if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i', $youtubeUrl, $match)) {
        $youtubeEmbed = 'https://www.youtube.com/embed/' . $match[1];
    }
}

$heroImg = 'assets/img/guide.png';
$heroImgExists = is_file(__DIR__ . '/' . $heroImg);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>StarMedia</title>
<link rel="icon" href="assets/logo-2.png" type="image/png">
<meta property="og:image" content="assets/img/guide.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
<style>
:root{
  --g-900:#0f172a; --g-800:#1e3a8a; --g-700:#1d4ed8; --g-600:#2563eb; --g-500:#3b82f6; --g-400:#60a5fa; --g-300:#93c5fd; --g-50:#eff6ff;
  --ink:#0b1727; --dim:#475569; --muted:#f1f5f9; --ring:#dbeafe; --bg:#fff;
  --r-xl:22px; --r-lg:18px; --r-md:14px;
  --sh-1:0 4px 12px rgba(0,0,0,.05); --sh-2:0 10px 24px rgba(0,0,0,.08);
}
*{box-sizing:border-box}
html,body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.6 "Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;overflow-x:hidden}
a{color:var(--g-700);text-decoration:none} a:hover{color:var(--g-800)}
.container{max-width:1200px;margin:0 auto;padding:0 20px}

/* Header */
.site-header{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.8);backdrop-filter:saturate(180%) blur(10px);border-bottom:1px solid var(--muted)}
.nav{display:flex;align-items:center;justify-content:space-between;height:70px}
.brand{display:flex;align-items:center;gap:10px}
.logo-img{width:38px;height:38px;border-radius:10px}
.brand-title{font-weight:900;font-size:1.2rem;}
.menu{display:flex;align-items:center;gap:10px}
.nav-link{padding:10px 12px;border-radius:12px;font-weight:700;}
.nav-link.active,.nav-link:hover{background:var(--muted)}
.btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid #e5e7eb;border-radius:14px;padding:10px 14px;background:#fff;cursor:pointer;font-weight:700;transition:.2s ease box-shadow,.2s ease transform}
.btn.primary{background:var(--g-600);color:#fff;border:0;box-shadow:var(--sh-1)}
.btn.primary:hover{transform:translateY(-1px);box-shadow:var(--sh-2);background:var(--g-700);}
.nav-toggle{display:none;background:transparent;border:0}
.nav-drawer{position:fixed;top:0;right:-100%;width:100%;height:100dvh;background:#fff;transition:right .25s;z-index:60;display:flex;flex-direction:column;padding:14px;justify-content:center;align-items:center;}
.nav-open .nav-drawer{right:0}
body.nav-open{overflow:hidden;}
.drawer-head{display:flex;align-items:center;justify-content:space-between;position:absolute;top:14px;left:14px;right:14px;}
.drawer-link{line-height:2.5;font-size:1.5rem;font-weight:bold;}
.nav-scrim{position:fixed;inset:0;background:rgba(0,0,0,.2);opacity:0;pointer-events:none;transition:.25s;z-index:55}
.nav-open .nav-scrim{opacity:1;pointer-events:auto}
@media (max-width:980px){ .menu{display:none} .nav-toggle{display:block} }
@media (min-width:981px){ .nav-drawer{display:none} }

/* Hero */
.hero{padding:22px 0 10px}
.hero-only-card{background:#fff;border:1px solid #e5e7eb;border-radius:var(--r-xl);overflow:hidden;box-shadow:var(--sh-2)}
.hero-only-card img{width:100%;display:block}

/* Layout */
.section{padding:56px 0}
.two-col{display:grid;grid-template-columns:340px 1fr;gap:22px;align-items:start}
@media (max-width:980px){ .two-col{grid-template-columns:1fr} }

/* Sidebar */
.sidecard{position:sticky;top:96px;display:flex;flex-direction:column;gap:16px}
@media (max-width:980px){ .sidecard{position:static} }
.side-block{background:linear-gradient(180deg,#ffffff,#f7fffb);border:1px solid var(--ring);border-radius:var(--r-xl);box-shadow:var(--sh-1);padding:18px}
.side-block:hover{box-shadow:var(--sh-2)}
.side-title{font-weight:900;margin-bottom:8px;color:var(--g-800)}
.side-text{color:var(--dim)}

/* Releases */
.side-block.releases-block{position:relative;overflow:hidden}
.releases{display:grid;gap:10px;max-height:320px;overflow:auto;padding-right:6px;scroll-behavior:smooth}
.releases:before,.releases:after{content:"";position:sticky;left:0;right:0;height:16px;z-index:2;pointer-events:none}
.releases:before{top:0;background:linear-gradient(#fff,transparent)}
.releases:after{bottom:0;background:linear-gradient(transparent,#fff)}
.release-card{display:grid;grid-template-columns:56px 1fr;gap:12px;align-items:center;background:var(--bg);border:1px solid var(--muted);border-radius:16px;padding:10px;box-shadow:var(--sh-1);transition:.18s ease transform,.18s ease box-shadow}
.release-card:hover{transform:translateY(-2px);box-shadow:var(--sh-2);border-color:var(--g-300);}
.release-card.disabled{opacity:.5;pointer-events:none}
.release-card.active{outline:2px solid var(--g-300);border-color:var(--g-300);}
.release-thumb{width:56px;height:56px;border-radius:12px;background:#e2e8f0 center/cover no-repeat;box-shadow:inset 0 0 0 3px #a7f3d0}
.release-thumb img{width:100%;height:100%;object-fit:cover;border-radius:12px;display:block}
.release-title{font-weight:800;color:var(--g-800)}
.releases::-webkit-scrollbar{width:10px}.releases::-webkit-scrollbar-track{background:#ecfdf5;border-radius:10px}.releases::-webkit-scrollbar-thumb{background:#86efac;border-radius:10px;border:2px solid #ecfdf5}

.show-on-mobile{display:none}
/* Mobile: make releases a horizontal scroller */
@media (max-width:980px){
  .hide-on-mobile{display:none}
  .show-on-mobile{display:block}
  #releases-mobile .releases{display:grid;grid-auto-flow:row;grid-auto-columns:100%;overflow-x:hidden;overflow-y:auto;max-height:320px;padding:4px 6px 12px}
}

/* Modal styles */
.releases-modal {
    display: none;
    position: fixed;
    z-index: 100;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.releases-modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 80%;
    max-width: 500px;
    border-radius: var(--r-lg);
    position: relative;
}

.close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    font-size: 20px;
    line-height: 28px;
    text-align: center;
    cursor: pointer;
    z-index: 10;
}

.close-btn:hover,
.close-btn:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

/* Flipbook pane + controls */
.mag-wrap{display:flex;flex-direction:column;gap:10px;position:sticky;top:96px}
.mag-pane{position:relative;min-height:520px;background:linear-gradient(120deg,#ecfdf5,#ffffff);border:1px solid var(--ring);border-radius:var(--r-xl);box-shadow:var(--sh-2);overflow:hidden}
.viewer-overlay{position:absolute;inset:0;background:rgba(0,0,0,0.5);color:white;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:bold;cursor:pointer;z-index:10;transition:opacity 0.3s ease-in-out;}
.viewer-overlay.hidden{opacity:0;pointer-events:none;}
.mag-frame{width:100%;height:78vh;border:0;display:block}
@media (max-width:980px){ .mag-frame{height:70vh} }

/* Controls hidden since we use native PDF viewer */
.mag-controls{display:none;}

/* Footer */
.footer-dark{background:blue;color:#f8fafc;border-top:1px solid rgba(94,216,135,.08)}
.foot{padding:18px 16px}
.foot-split{display:flex;align-items:center;justify-content:space-between;gap:16px}
.credit a{color:#7efbd3;text-decoration:none}.credit a:hover{text-decoration:underline}
@media (max-width:640px){ .foot-split{flex-direction:column;text-align:center} }

.muted{color:var(--dim)}

/* Preloader */
#preloader {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: #fff;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.5s ease-in-out;
}
#preloader.hidden {
  opacity: 0;
  pointer-events: none;
}
.loader {
  border: 8px solid var(--muted);
  border-top: 8px solid var(--g-500);
  border-radius: 50%;
  width: 60px;
  height: 60px;
  animation: spin 1s linear infinite;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Welcome Section */
.welcome-section {
  padding: 40px 0;
  text-align: center;
  background: linear-gradient(135deg, var(--g-50), #ffffff);
}
.welcome-message {
  font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  font-size: 1.5rem; /* Reduced font size */
  font-weight: 600;
  color: var(--g-800);
  opacity: 0;
  animation: fadeIn 1.5s ease-in-out forwards;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
@media (max-width: 768px) {
  .welcome-message {
    font-size: 1.2rem;
  }
}

/* Focused Viewer State */
.focused-view-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 90;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s ease;
}

body.viewer-focused .focused-view-overlay {
  opacity: 1;
  pointer-events: auto;
}

.mag-wrap.is-focused {
  position: relative;
  z-index: 91;
}

.exit-focus-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 20; /* Above the iframe */
  background: rgba(0,0,0,0.5);
  color: white;
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  font-size: 24px;
  line-height: 36px;
  text-align: center;
  cursor: pointer;
  display: none; /* Hidden by default */
}

.mag-wrap.is-focused .exit-focus-btn {
  display: block;
}
</style>

    <meta property="og:title" content="TourGuide">
    <meta property="og:description" content="Embracing a Sustainable Future.">
    <meta property="og:image" content="https://lankatourguide.lk/assets/img/guide.png">
    <meta property="og:url" content="https://www.lankatourguide.lk">
    <meta property="og:type" content="website">
    

</head>
<body>

<div class="focused-view-overlay"></div>

<div id="preloader" style="display: none;">
  <div class="loader"></div>
</div>

<!-- Header -->
<header class="site-header nav-glass" data-nav>
  <div class="container nav">
    <a class="brand" href="#home">
      <img class="logo-img" src="<?= $siteBase ?>/assets/logo-2.png" alt="TourGuide logo" style="width: auto; max-width: 150px; border-radius: 0;">
    </a>
    <nav class="menu" aria-label="Main">
      <a class="nav-link" href="#home">Home</a>
      
    </nav>
    <button class="nav-toggle" id="navToggle" aria-label="Open menu">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
  </div>

  <!-- Drawer -->
  <div class="nav-drawer" id="navDrawer" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="drawer-head">
      <div class="brand"><img class="logo-img" src="<?= $siteBase ?>/assets/logo-2.png" alt="" style="width: auto; height: 36px; border-radius: 0;"></div>
      <button class="drawer-close" id="navClose" aria-label="Close"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
    </div>
    <a class="drawer-link" href="#home">Home</a>
    <a class="drawer-link" href="#latest-updates">Latest Updates</a>
    <a class="drawer-link" href="#issues">Magazine</a>
    <a class="drawer-link" href="#newsflash">News Flash</a>
    <a class="drawer-link" href="#exclusive-magazines">Lanka Puwath</a>
    <a class="drawer-link" href="#about">About</a>
    <?php if ($currentUrl): ?><a class="btn primary drawer-cta" href="<?= h($currentUrl) ?>">Read latest</a><?php endif; ?>
  </div>
  <div class="nav-scrim" id="navScrim" aria-hidden="true"></div>
</header>

<!-- Hero -->
<section id="home" class="hero">
  <div class="container" style="text-align: center;">
    <img src="assets/logo1.png" alt="Star Media Logo" style="max-width: 80%; width: 80%; height: auto;">
  </div>
</section>

<?php if (!empty($youtubeEmbed)): ?>
<section class="section" style="padding-bottom: 0;">
  <div class="container">
     <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 22px; box-shadow: var(--sh-2);">
        <iframe src="<?= h($youtubeEmbed) ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border:0;" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
     </div>
  </div>
</section>
<?php endif; ?>

<!-- Welcome Message -->
<section id="welcome" class="section welcome-section">
  <div class="container">
    <h1 class="welcome-message">Star Media: Leading News with News Lanka, Exploring Frontiers with Tour Guide, and Connecting Communities with Oz Lanka.</h1>
  </div>
</section>

<!-- Latest Updates Section -->
<?php if (!empty($quickNews)): ?>
<section id="latest-updates" class="section">
  <div class="container">
    <h2 class="side-title" style="text-align:center;font-size:1.8rem;margin-bottom:24px;">Latest Updates</h2>
    <div class="swiper-container horizontal-carousel">
      <div class="swiper-wrapper">
        <?php foreach ($quickNews as $news): ?>
          <div class="swiper-slide">
            <div class="quick-news-card" style="background-image: url('<?= h($news['image_file']) ?>');">
              <div class="quick-news-overlay"></div>
              <a href="#newsflash" class="quick-news-link" aria-label="View news flash"></a>
              <div class="quick-news-text">
                <p style="text-align: justify;"><?= h($news['news_text']) ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <!-- Add Pagination -->
      <div class="swiper-pagination"></div>
      <!-- Add Navigation -->
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
    </div>
  </div>
</section>
<?php endif; ?>

<style>
.quick-news-card {
  position: relative;
  width: 100%;
  min-height: 250px;
  background-size: cover;
  background-position: center;
  border-radius: var(--r-xl);
  padding: 40px;
  box-shadow: var(--sh-2);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  color: #fff;
}
.quick-news-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  border-radius: var(--r-xl);
  z-index: 1;
}
.quick-news-link {
  position: absolute;
  inset: 0;
  z-index: 2;
}
.quick-news-text {
  position: relative;
  z-index: 3;
  width: 100%;
  max-width: 800px;
  font-size: 1.1rem;
  font-weight: 500;
  text-shadow: 0 1px 3px rgba(0,0,0,0.8);
  pointer-events: none;
}
@media (max-width: 768px) {
  .quick-news-card {
    padding: 24px;
    min-height: 200px;
  }
  .quick-news-text {
    font-size: 1rem;
    text-align: center;
  }
  .quick-news-text p {
    text-align: center !important;
  }
}
.horizontal-carousel {
  overflow: hidden;
  border: 1px solid var(--muted);
  border-radius: var(--r-xl);
  box-shadow: var(--sh-1);
  padding: 10px;
}
.swiper-slide {
  display: flex;
  justify-content: center;
  align-items: center;
}
.horizontal-carousel {
  position: relative;
}
.swiper-button-next, .swiper-button-prev {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 24px;
  height: 24px;
  background-color: rgba(255, 255, 255, 0.7);
  border-radius: 50%;
  color: var(--g-800);
  --swiper-navigation-size: 12px;
}
.swiper-button-next {
  right: 16px;
}
.swiper-button-prev {
  left: 16px;
}
</style>

<!-- News Flash -->
<section id="newsflash" class="section">
  <div class="container">
    <h2 class="side-title" style="text-align:center;font-size:1.8rem;margin-bottom:24px;">News Flash</h2>
    <?php if (empty($newsflashes)): ?>
      <div class="muted" style="text-align:center;">No news flashes yet.</div>
    <?php else: ?>
      <div class="news-grid" id="newsFlashGrid">
        <?php foreach ($newsflashes as $i => $it):
          $thumb = $it['cover'] ?: 'assets/covers/logo.png';
          // Hide items beyond index 5 (0-5 = 6 items)
          $hiddenStyle = $i >= 6 ? 'display:none;' : '';
          $hiddenClass = $i >= 6 ? 'hidden-card' : '';
        ?>
          <div class="news-card-wrapper <?= $hiddenClass ?>" style="<?= $hiddenStyle ?>">
            <a class="release-card news-card-item"
               href="#"
               data-pdf="read.php?file=<?= rawurlencode($it['file']) ?>"
               onclick="openFullscreenViewer(event, 'read.php?file=<?= rawurlencode($it['file']) ?>'); return false;">
              <span class="release-thumb"><img src="<?= h($thumb) ?>" alt="<?= h($it['label']) ?>"></span>
              <span class="release-title"><?= h($it['label']) ?></span>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (count($newsflashes) > 6): ?>
        <div style="text-align:center;margin-top:20px;">
          <button id="loadMoreNews" class="btn primary">Load More</button>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<style>
.news-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}
@media (max-width: 600px) {
  .news-grid {
    grid-template-columns: 1fr;
  }
}
.news-card-item {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 0;
  overflow: hidden;
  background: var(--bg);
  border: 1px solid var(--muted);
  border-radius: 16px;
  box-shadow: var(--sh-1);
  transition: .18s ease transform, .18s ease box-shadow;
  text-decoration: none;
  color: inherit;
  aspect-ratio: 4/3; /* Adjust aspect ratio as needed */
  position: relative;
}
.news-card-item:hover {
  transform: translateY(-2px);
  box-shadow: var(--sh-2);
  border-color: var(--g-300);
}
.news-card-item .release-thumb {
    width: 100%;
    height: 100%;
    border-radius: 0;
    box-shadow: none;
    position: absolute;
    top: 0;
    left: 0;
}
.news-card-item .release-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 0;
    display: block;
}
.news-card-item .release-title {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: #fff;
    padding: 20px 10px 10px;
    font-size: 1.1rem;
    font-weight: 800;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    z-index: 2;
}
</style>

<!-- Magazine + Sidebar -->

<!-- OzLanka Magazine + Sidebar -->
<div style="text-align: center; margin-bottom: 20px;">
  <img src="assets/logo-ozlanka.png" alt="OzLanka Logo" style="max-width: 55%; width: 55%; height: auto;">
</div>
<section id="ozlanka-magazines" class="section">
  <div class="container two-col">
    <!-- Sidebar -->
    <aside class="sidecard">
      <div id="ozlanka-releases" class="side-block releases-block hide-on-mobile">
        <div class="side-title">OzLanka</div>
        <?php if (empty($ozlanka_magazines)): ?>
          <div class="muted">No OzLanka magazines yet.</div>
        <?php else: ?>
          <div class="releases" role="list">
            <?php foreach ($ozlanka_magazines as $it):
              $exists = $it['url'] !== '';
              $thumb  = $it['cover'] ?: 'assets/covers/logo.png';
            ?>
              <a class="release-card <?= $exists ? '' : 'disabled' ?>"
                 href="#" role="listitem"
                 data-pdf="read.php?file=<?= rawurlencode($it['file']) ?>"
                 title="<?= $exists ? 'Open flipbook' : 'Missing: ' . h($it['pdf']) ?>">
                <span class="release-thumb"><img src="<?= h($thumb) ?>" alt="<?= h($it['label']) ?> banner"></span>
                <span class="release-title"><?= h($it['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Main Flipbook Area -->
    <main class="mag-wrap" id="ozlanka-mag-wrap">
      <div class="mag-pane">
        <div class="viewer-overlay" data-viewer="ozlankaFrame">Tap to read</div>
        <button class="exit-focus-btn">&times;</button>
        <?php if (!empty($ozlanka_magazines)): ?>
          <iframe class="mag-frame" id="ozlankaFrame"
                  title="OzLanka Viewer"
                  src="read.php?file=<?= rawurlencode($ozlanka_magazines[0]['file']) ?>"
                  allowfullscreen></iframe>
        <?php else: ?>
          <div class="mag-empty" style="display:grid;place-items:center;height:100%;padding:20px">
            <div class="muted">No OzLanka Magazines Published</div>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</section>

<!-- Mobile exclusive layout block... wait, we should do the same for OzLanka -->
<?php if (!empty($ozlanka_magazines)): ?>
  <div class="container show-on-mobile" style="margin-bottom: 2rem;">
    <div id="ozlanka-releases-mobile" class="side-block releases-block ui-card">
      <div class="side-title" style="margin-top:0;">OzLanka Issues</div>
      <div class="releases" role="list">
        <?php foreach ($ozlanka_magazines as $it):
          $exists = $it['url'] !== '';
          $thumb  = $it['cover'] ?: 'assets/covers/logo.png';
        ?>
          <a class="release-card <?= $exists ? '' : 'disabled' ?>"
             href="#" role="listitem"
             data-pdf="read.php?file=<?= rawurlencode($it['file']) ?>"
             title="<?= $exists ? 'Open flipbook' : 'Missing: ' . h($it['pdf']) ?>">
            <span class="release-thumb"><img src="<?= h($thumb) ?>" alt="<?= h($it['label']) ?> banner"></span>
            <span class="release-title"><?= h($it['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>
<div style="text-align: center; margin-bottom: 20px;">
  <img src="assets/img/guide.png" alt="TourGuide Logo" style="max-width: 50%; width: 50%; height: auto;">
</div>
<section id="issues" class="section">
  <div class="container two-col">
    <!-- Sidebar -->
    <aside class="sidecard">
      <div id="author" class="side-block">
        <div class="side-title">From Author</div>
        <p class="side-text" id="authorNote"><?= $authorNote ? nl2br(h($authorNote)) : '—' ?></p>
      </div>

      <div id="releases" class="side-block releases-block hide-on-mobile">
        <div class="side-title">Releases</div>
        <?php if (empty($issues)): ?>
          <div class="muted">No magazines yet.</div>
        <?php else: ?>
          <div class="releases" role="list" aria-label="Available issues">
            <?php foreach ($issues as $it):
              $exists   = $it['url'] !== '';
              $href     = $exists ? $it['url'] : '#';
              $isCurrent= $exists && ($it['url'] === $currentUrl);
              $thumb    = $it['cover'] ?: 'assets/covers/logo.png';
            ?>
              <a class="release-card <?= $exists ? '' : 'disabled' ?>"
                 href="#" role="listitem"
                 data-pdf="read.php?file=<?= rawurlencode($it['file']) ?>"
                 title="<?= $exists ? 'Open flipbook' : 'Missing: ' . h($it['pdf']) ?>">
                <span class="release-thumb"><img src="<?= h($thumb) ?>" alt="<?= h($it['label']) ?> banner"></span>
                <span class="release-title"><?= h($it['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Viewer + Controls -->
    <div class="mag-wrap">
      <div class="mag-pane">
        <div class="viewer-overlay" data-viewer="magFrame" data-file="read.php?file=<?= rawurlencode($currentFile) ?>">Tap to read</div>
        <button class="exit-focus-btn">&times;</button>
        <?php if ($currentUrl): ?>
          <iframe class="mag-frame" id="magFrame" src="read.php?file=<?= rawurlencode($currentFile) ?>" title="Magazine Flipbook" allowfullscreen></iframe>
        <?php else: ?>
          <div class="mag-empty" style="display:grid;place-items:center;height:100%;padding:20px">
            <div class="muted">Upload a PDF in the admin to preview it here.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Mobile-only button for releases -->
    <div class="show-on-mobile" style="text-align: center; margin-top: 10px;">
        <button id="show-releases-btn" class="btn primary">View Previous Releases</button>
    </div>

    <!-- Releases (for mobile, now in a modal) -->
    <div id="releases-modal" class="releases-modal">
        <div class="releases-modal-content">
            <span class="close-btn">&times;</span>
            <div id="releases-mobile" class="side-block releases-block">
                <div class="side-title">Releases</div>
                <?php if (empty($issues)): ?>
                    <div class="muted">No magazines yet.</div>
      <?php else: ?>
        <div class="releases" role="list" aria-label="Available issues">
          <?php foreach ($issues as $it):
            $exists   = $it['url'] !== '';
            $href     = $exists ? $it['url'] : '#';
            $thumb    = $it['cover'] ?: 'assets/covers/logo.png';
          ?>
            <a class="release-card <?= $exists ? '' : 'disabled' ?>"
               href="#" role="listitem"
               data-pdf="read.php?file=<?= rawurlencode($it['file']) ?>"
               title="<?= $exists ? 'Open flipbook' : 'Missing: ' . h($it['pdf']) ?>">
              <span class="release-thumb"><img src="<?= h($thumb) ?>" alt="<?= h($it['label']) ?> banner"></span>
              <span class="release-title"><?= h($it['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</div>
</section>


<!-- Exclusive Hero -->
<?php
$exclusiveHeroImg = 'assets/img/exclusive_hero.jpg';
$exclusiveHeroImgExists = is_file(__DIR__ . '/' . $exclusiveHeroImg);
?>
<section id="exclusive-hero" class="hero">
  <div class="container">
    <?php if ($exclusiveHeroImgExists): ?>
      <div class="hero-only-card" style="text-align: center;">
        <img src="<?= h($exclusiveHeroImg) ?>" alt="Exclusive hero image" style="max-width: 50%; width: 50%; height: auto; display: block; margin: 0 auto;">
      </div>
    <?php else: ?>
      <div class="hero-only-card" style="display:grid;place-items:center;min-height:240px;background:linear-gradient(120deg,#ecfdf5,#ffffff)">
        <div class="muted">Add <code>assets/img/exclusive_hero.png</code> to show a hero image.</div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Exclusive Magazines -->
<section id="exclusive-magazines" class="section">
  <div class="container two-col">
    <!-- Sidebar -->
    <aside class="sidecard">
      <div id="exclusive-releases" class="side-block releases-block hide-on-mobile">
        <div class="side-title">Lanka Puwath</div>
        <?php if (empty($exclusives)): ?>
          <div class="muted">No Lanka Puwath issues yet.</div>
        <?php else: ?>
          <div class="releases" role="list" aria-label="Available exclusives">
            <?php foreach ($exclusives as $it):
              $exists   = $it['url'] !== '';
              $href     = $exists ? $it['url'] : '#';
              $thumb    = $it['cover'] ?: 'assets/covers/logo.png';
            ?>
              <a class="release-card <?= $exists ? '' : 'disabled' ?>"
                 href="#" role="listitem"
                 data-pdf="read.php?file=<?= rawurlencode($it['file']) ?>"
                 title="<?= $exists ? 'Open flipbook' : 'Missing: ' . h($it['pdf']) ?>">
                <span class="release-thumb"><img src="<?= h($thumb) ?>" alt="<?= h($it['label']) ?> banner"></span>
                <span class="release-title"><?= h($it['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Viewer + Controls -->
    <div class="mag-wrap">
      <div class="mag-pane">
        <div class="viewer-overlay" data-viewer="exclusiveFrame" data-file="read.php?file=<?= rawurlencode($exclusives[0]['file']) ?>">Tap to read</div>
        <button class="exit-focus-btn">&times;</button>
        <?php if (!empty($exclusives)): ?>
          <iframe class="mag-frame" id="exclusiveFrame" src="read.php?file=<?= rawurlencode($exclusives[0]['file']) ?>" title="Exclusive Flipbook" allowfullscreen></iframe>
        <?php else: ?>
          <div class="mag-empty" style="display:grid;place-items:center;height:100%;padding:20px">
            <div class="muted">Latest Lanka Puwath Magazine will be Released Soon....</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div id="exclusive-releases-mobile" class="side-block releases-block show-on-mobile">
      <div class="side-title">Lanka Puwath</div>
      <?php if (empty($exclusives)): ?>
        <div class="muted">No Lanka Puwath issues yet.</div>
      <?php else: ?>
        <div class="releases" role="list" aria-label="Available exclusives">
          <?php foreach ($exclusives as $it):
            $exists   = $it['url'] !== '';
            $href     = $exists ? $it['url'] : '#';
            $thumb    = $it['cover'] ?: 'assets/covers/logo.png';
          ?>
            <a class="release-card <?= $exists ? '' : 'disabled' ?>"
               href="#" role="listitem"
               data-pdf="read.php?file=<?= rawurlencode($it['url']) ?>"
               title="<?= $exists ? 'Open flipbook' : 'Missing: ' . h($it['pdf']) ?>">
              <span class="release-thumb"><img src="<?= h($thumb) ?>" alt="<?= h($it['label']) ?> banner"></span>
              <span class="release-title"><?= h($it['label']) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>



<!-- Footer -->
<footer class="site-footer footer-dark" id="about">
  <div class="container foot foot-split">
    <div class="copy">© <?= date('Y') ?> <strong>TourGuide</strong>. All rights reserved.</div>
    <div class="credit">Designed & Developed by <a href="https://www.zeatralabs.com" target="_blank" rel="noopener">ZeatraLabs.com</a></div>
  </div>
</footer>

<!-- Fullscreen Viewer Container -->
<div id="fullscreenViewer" class="fullscreen-viewer hidden">
  <button class="close-viewer-btn">&times;</button>
  <iframe id="fullscreenFrame" class="fullscreen-frame" src="" allowfullscreen></iframe>
</div>

<style>
.fullscreen-viewer {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #000;
  display: flex;
  flex-direction: column;
}
.fullscreen-viewer.hidden {
  display: none;
}
.fullscreen-frame {
  flex: 1;
  width: 100%;
  border: 0;
}
.close-viewer-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  background: rgba(255,255,255,0.8);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  font-size: 24px;
  cursor: pointer;
  z-index: 10000;
  display: grid;
  place-items: center;
}
</style>

<!-- JS: drawer, releases swap, author note, controls + page jump -->
<script>
// Drawer UI
(function(){
  const header=document.querySelector('[data-nav]'), btn=document.getElementById('navToggle'),
        close=document.getElementById('navClose'), scrim=document.getElementById('navScrim');
  function open(){ document.body.classList.add('nav-open'); btn?.setAttribute('aria-expanded','true'); }
  function shut(){ document.body.classList.remove('nav-open'); btn?.setAttribute('aria-expanded','false'); }
  btn?.addEventListener('click',open); close?.addEventListener('click',shut); scrim?.addEventListener('click',shut);
  window.addEventListener('scroll',()=>{ const y=window.scrollY||0; header?.classList.toggle('scrolled',y>4); header?.classList.toggle('shrink',y>24); },{passive:true});
})();

// Fullscreen Viewer Logic
window.openFullscreenViewer = function(e, file) {
  if (e) e.preventDefault();
  const viewer = document.getElementById('fullscreenViewer');
  const frame = document.getElementById('fullscreenFrame');
  if (viewer && frame) {
    viewer.classList.remove('hidden');
    frame.src = file; // Direct PDF link
    document.body.style.overflow = 'hidden';
  }
};

document.querySelector('.close-viewer-btn')?.addEventListener('click', () => {
  const viewer = document.getElementById('fullscreenViewer');
  const frame = document.getElementById('fullscreenFrame');
  if (viewer) {
    viewer.classList.add('hidden');
    frame.src = '';
    document.body.style.overflow = '';
  }
});

// Load More Logic
const loadMoreBtn = document.getElementById('loadMoreNews');
if (loadMoreBtn) {
  loadMoreBtn.addEventListener('click', function() {
    const hidden = document.querySelectorAll('.news-card-wrapper.hidden-card');
    let count = 0;
    for (let el of hidden) {
      el.style.display = 'block';
      el.classList.remove('hidden-card');
      count++;
      if (count >= 6) break;
    }
    if (document.querySelectorAll('.news-card-wrapper.hidden-card').length === 0) {
      loadMoreBtn.style.display = 'none';
    }
  });
}

// NOTE MAP (file -> author_note)
window.__NOTE_MAP__ = <?= json_encode(array_column($issues, 'author_note', 'file'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

// escape helper
function escapeHtml(s){
  return (s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// set note
function setAuthorNoteFor(file){
  const el = document.getElementById('authorNote');
  if (!el) return;
  const raw = (window.__NOTE_MAP__ && window.__NOTE_MAP__[file]) || '';
  el.innerHTML = raw ? escapeHtml(raw).replace(/\n/g,'<br>') : '—';
}

// Viewer initializer
function initViewer(frameId, releasesId, releasesMobileId, initialFile, paneSelector) {
    const frame = document.getElementById(frameId);
    const pane = document.querySelector(paneSelector);
    if (!frame) return;

    // Set initial data-file for overlay
    if (pane) {
        const overlay = pane.querySelector('.viewer-overlay');
        if (overlay) overlay.dataset.file = initialFile;
    }

    function markCurrent(fileBase) {
        document.querySelectorAll(`#${releasesId} .release-card, #${releasesMobileId} .release-card`).forEach(card => {
            const same = (card.dataset.pdf || '') === fileBase; // actually comparing file base for now, can be URL
            card.classList.toggle('is-current', same);
            card.classList.remove('active');
        });
    }

    function handleReleaseClick(e) {
        const a = e.target.closest('.release-card:not(.disabled)');
        if (!a) return;

        e.preventDefault();

        // This is a relative path like read.php?file=foo.pdf
        const file = a.dataset.pdf || '';
        frame.src = file;

        // Update overlay data-file
        if (pane) {
            const overlay = pane.querySelector('.viewer-overlay');
            if (overlay) overlay.dataset.file = file;
        }

        markCurrent(file);
        a.classList.add('active');
        a.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

        if (pane) {
            const y = pane.getBoundingClientRect().top + window.scrollY - 80;
            window.scrollTo({ top: y, behavior: 'smooth' });
        }
    }

    // markCurrent(initialFile); // initialFile is now URL?

    document.getElementById(releasesId)?.addEventListener('click', handleReleaseClick);
    document.getElementById(releasesMobileId)?.addEventListener('click', handleReleaseClick);
}

// Initialize Magazine Viewer
initViewer('magFrame', 'releases', 'releases-mobile', 'read.php?file=<?= rawurlencode($currentFile) ?>', '#issues .mag-wrap');

// Initialize Exclusive Viewer
<?php if (!empty($ozlanka_magazines)): ?>
  initViewer('ozlankaFrame', 'ozlanka-releases', 'ozlanka-releases-mobile', 'read.php?file=<?= rawurlencode($ozlanka_magazines[0]['file']) ?>', '#ozlanka-magazines .mag-wrap');
<?php endif; ?>
<?php if (!empty($exclusives)): ?>
  initViewer('exclusiveFrame', 'exclusive-releases', 'exclusive-releases-mobile', 'read.php?file=<?= rawurlencode($exclusives[0]['file']) ?>', '#exclusive-magazines .mag-wrap');
<?php endif; ?>

// Overlay logic
document.querySelectorAll('.viewer-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        // We open the fullscreen viewer for ALL overlays
        const file = overlay.dataset.file;
        if (file && window.openFullscreenViewer) {
            window.openFullscreenViewer(e, file);
        }
    });
});

// Exit focus logic
document.querySelectorAll('.exit-focus-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const magWrap = btn.closest('.mag-wrap');
        const overlay = magWrap.querySelector('.viewer-overlay');

        document.body.classList.remove('viewer-focused');
        magWrap.classList.remove('is-focused');
        overlay.classList.remove('hidden');
    });
});

// Hide preloader
function hidePreloader() {
    const preloader = document.getElementById('preloader');
    if (preloader) preloader.classList.add('hidden');
}
window.addEventListener('load', hidePreloader);
document.addEventListener('DOMContentLoaded', hidePreloader);
setTimeout(hidePreloader, 3000); // fallback

// Modal logic
var modal = document.getElementById("releases-modal");
var btn = document.getElementById("show-releases-btn");
var span = document.getElementsByClassName("close-btn")[0];

if (btn) {
  btn.onclick = function() {
    modal.style.display = "block";
  }
}

if (span) {
  span.onclick = function() {
    modal.style.display = "none";
  }
}

window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
  }
}
</script>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
  var swiper = new Swiper('.horizontal-carousel', {
    direction: 'horizontal',
    slidesPerView: 1,
    spaceBetween: 10,
    loop: true,
    autoHeight: true,
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
  });
</script>
</body>
</html>
