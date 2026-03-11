<?php
$content = file_get_contents('index.php');

$php_code_to_add = <<<'PHP'
/* 1d) Fetch OzLanka magazines from DB */
$ozlanka_magazines = [];
try {
  $stmt = $pdo->prepare("
      SELECT id, title, pdf_file, banner_file, label, is_published, published_at
      FROM ozlanka_magazines
      WHERE is_published = 1
      ORDER BY published_at DESC, id DESC
  ");
  $stmt->execute();
  foreach ($stmt as $row) {
    $pdfPath = trim($row['pdf_file']);
    $thumb   = trim($row['banner_file']);

    $ozlanka_magazines[] = [
      'id'    => $row['id'],
      'label' => $row['label'] ?: 'Unlabeled',
      'title' => $row['title'] ?: 'Untitled',
      'file'  => $pdfPath,
      'cover' => $thumb,
    ];
  }
} catch (PDOException $e) {}

PHP;

$html_code_to_add = <<<'HTML'

<!-- OzLanka Magazine + Sidebar -->
<div style="text-align: center; margin-bottom: 20px;">
  <img src="assets/logo-ozlanka.png" alt="OzLanka Logo" style="max-width: 50%; width: 50%; height: auto;">
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
              $thumb = $it['cover'] ?: 'assets/covers/logo.png';
            ?>
              <a class="release-card"
                 href="#" role="listitem"
                 data-pdf="read.php?file=<?= rawurlencode($it['file']) ?>"
                 title="Open flipbook">
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
      <?php if (!empty($ozlanka_magazines)): ?>
        <iframe id="ozlankaFrame"
                title="OzLanka Viewer"
                src="read.php?file=<?= rawurlencode($ozlanka_magazines[0]['file']) ?>"
                allowfullscreen></iframe>
      <?php else: ?>
        <div style="padding: 40px; text-align: center; color: var(--dim); border: 2px dashed var(--ring); border-radius: var(--r-xl);">
          <i class="fas fa-book-open" style="font-size: 3rem; margin-bottom: 16px; color: var(--g-300);"></i>
          <h3>No OzLanka Magazines Published</h3>
          <p>Check back later for exciting new content.</p>
        </div>
      <?php endif; ?>
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
          $thumb = $it['cover'] ?: 'assets/covers/logo.png';
        ?>
          <a class="release-card"
             href="#" role="listitem"
             data-pdf="read.php?file=<?= rawurlencode($it['file']) ?>"
             title="Open flipbook">
            <span class="release-thumb"><img src="<?= h($thumb) ?>" alt="<?= h($it['label']) ?> banner"></span>
            <span class="release-title"><?= h($it['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

HTML;

// Insert PHP code
$content = str_replace("/* 1c) Fetch exclusive magazines from DB */", $php_code_to_add . "/* 1c) Fetch exclusive magazines from DB */", $content);

// Insert HTML code before the regular magazine section, which is `<section id="issues" class="section">`
// Wait, the regular magazine is `id="issues"`. We also need to add the TourGuide logo before `id="issues"`.
$original_issues_html = '<section id="issues" class="section">';
$tourguide_logo_html = '<div style="text-align: center; margin-bottom: 20px;">
  <img src="assets/logo1.png" alt="TourGuide Logo" style="max-width: 50%; width: 50%; height: auto;">
</div>
';

$content = str_replace($original_issues_html, $html_code_to_add . $tourguide_logo_html . $original_issues_html, $content);

// Insert initViewer call for OzLanka
$initViewerStr = "<?php if (!empty(\$ozlanka_magazines)): ?>
  initViewer('ozlankaFrame', 'ozlanka-releases', 'ozlanka-releases-mobile', 'read.php?file=<?= rawurlencode(\$ozlanka_magazines[0]['file']) ?>', '#ozlanka-magazines .mag-wrap');
<?php endif; ?>";

$content = str_replace("initViewer('exclusiveFrame',", $initViewerStr . "\n  initViewer('exclusiveFrame',", $content);

file_put_contents('index.php', $content);
echo "Added OzLanka to index.php";
