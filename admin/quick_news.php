<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

$siteBase  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$adminBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$sql = "
  SELECT
    id, image_file, news_text, created_at
  FROM quick_news
  ORDER BY created_at DESC
";
$rows = pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quick News</title>
<link rel="stylesheet" href="<?= $siteBase ?>/css/style.css">
<style>
  /* ===== List page (table) ===== */
.hero-actions{display:flex; gap:10px}
.btn.danger{background:#ef4444;color:#fff}
.btn.danger:hover{filter:brightness(.97)}
.btn.pill{border-radius:999px;padding:8px 12px}
.t-right{text-align:right}
.no-wrap{white-space:nowrap}

/* table wrapper for horizontal scroll on small screens */
.table-wrap{width:100%; overflow:auto}
.table-green{
  width:100%; border-collapse:separate; border-spacing:0;
}
.table-green th,
.table-green td{
  padding:12px 14px; border-bottom:1px solid var(--border);
  vertical-align:middle;
}
.table-green thead th{
  font-weight:900; color:var(--ink); background:#fff; position:sticky; top:0; z-index:1;
}
.table-green tbody tr:hover td{ background:#fafafa }
.note-cell{
  max-width:340px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;
}
</style>
<!-- Header / hero -->
<section class="hero-green">
  <div class="shell">
    <div class="hero-inner">
      <h1 class="hero-title">Quick News</h1>
      <div class="hero-actions">
        <a class="btn ghost" href="<?= $adminBase ?>/new_quick_news.php">+ New</a>
        <a class="btn ghost" href="<?= $adminBase ?>/index.php">Back to Dashboard</a>
        <a class="btn danger" href="<?= $adminBase ?>/login.php">Logout</a>
      </div>
    </div>
  </div>
</section>

<section class="shell">
  <div class="ui-card">
    <div class="table-wrap">
      <table class="table-green">
        <thead>
          <tr>
            <th>Image</th>
            <th>News Text</th>
            <th>Created At</th>
            <th class="t-right"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="Image"><img src="<?= $siteBase ?>/<?= h($r['image_file']) ?>" alt="" width="100"></td>
            <td data-label="News Text" class="note-cell"><?= h($r['news_text']) ?></td>
            <td data-label="Created At"><?= h($r['created_at']) ?></td>
            <td class="t-right">
              <form method="post" action="<?= $adminBase ?>/delete_quick_news.php"
                    onsubmit="return confirm('Delete this news item?')">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn danger pill" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
