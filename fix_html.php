<?php
$phpCode = file_get_contents('index.php');

// We need to propagate the same HTML structure changes to index.html (mock data version).
// For the mock HTML, let's just make the changes manually or via script.
$htmlCode = file_get_contents('index.html');

// 1. Add OzLanka Logo and section before issues section
$ozlankaSection = <<<'HTML'

<!-- OzLanka Magazine + Sidebar -->
<div style="text-align: center; margin-bottom: 20px;">
  <img src="assets/logo-ozlanka.png" alt="OzLanka Logo" style="max-width: 25%; width: 25%; height: auto;">
</div>
<section id="ozlanka-magazines" class="section">
  <div class="container two-col">
    <!-- Sidebar -->
    <aside class="sidecard">
      <div id="ozlanka-releases" class="side-block releases-block hide-on-mobile">
        <div class="side-title">OzLanka</div>
        <div class="releases" role="list">
          <a class="release-card" href="#" role="listitem" data-pdf="read.php?file=test.pdf" title="Open flipbook">
            <span class="release-thumb"><img src="assets/logo-ozlanka.png" alt="OzLanka"></span>
            <span class="release-title">Issue 1</span>
          </a>
        </div>
      </div>
    </aside>

    <!-- Main Flipbook Area -->
    <main class="mag-wrap" id="ozlanka-mag-wrap">
      <iframe id="ozlankaFrame" title="OzLanka Viewer" src="read.php?file=test.pdf" allowfullscreen></iframe>
    </main>
  </div>
</section>

<div class="container show-on-mobile" style="margin-bottom: 2rem;">
  <div id="ozlanka-releases-mobile" class="side-block releases-block ui-card">
    <div class="side-title" style="margin-top:0;">OzLanka Issues</div>
    <div class="releases" role="list">
      <a class="release-card" href="#" role="listitem" data-pdf="read.php?file=test.pdf" title="Open flipbook">
        <span class="release-thumb"><img src="assets/logo-ozlanka.png" alt="OzLanka"></span>
        <span class="release-title">Issue 1</span>
      </a>
    </div>
  </div>
</div>

<div style="text-align: center; margin-bottom: 20px;">
  <img src="assets/logo1.png" alt="TourGuide Logo" style="max-width: 25%; width: 25%; height: auto;">
</div>
HTML;

$htmlCode = str_replace('<section id="issues" class="section">', $ozlankaSection . '<section id="issues" class="section">', $htmlCode);

// 2. Add Lanka Puwath Logo before exclusive-magazines
$lankaPuwathLogo = <<<'HTML'
<div style="text-align: center; margin-bottom: 20px;">
  <img src="assets/logo-2.png" alt="Lanka Puwath Logo" style="max-width: 25%; width: 25%; height: auto;">
</div>
HTML;

$htmlCode = str_replace('<!-- Exclusive Magazines -->', "<!-- Exclusive Magazines -->\n" . $lankaPuwathLogo, $htmlCode);

// 3. Update initViewer calls in JS
$initViewerJS = <<<'JS'
initViewer('ozlankaFrame', 'ozlanka-releases', 'ozlanka-releases-mobile', 'read.php?file=test.pdf', '#ozlanka-magazines .mag-wrap');
    initViewer('magFrame', 'releases', 'releases-mobile', 'read.php?file=test.pdf', '#issues .mag-wrap');
JS;
$htmlCode = str_replace("initViewer('magFrame', 'releases', 'releases-mobile', 'read.php?file=test.pdf', '#issues .mag-wrap');", $initViewerJS, $htmlCode);


// 4. Update the 50% max-width logo to 25% (hero logo)
$htmlCode = str_replace('max-width: 50%; width: 50%;', 'max-width: 25%; width: 25%;', $htmlCode);


file_put_contents('index.html', $htmlCode);
echo "Updated index.html";
