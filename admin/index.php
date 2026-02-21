
<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

$siteBase  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$adminBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$sql = "
  SELECT
    m.id, m.label, m.title, m.pdf_file, m.banner_file, m.author_note,
    m.is_published, m.published_at, m.created_at,
    COALESCE(c.review_count, 0)  AS review_count,
    COALESCE(c.pending_count, 0) AS pending_count
  FROM magazines m
  LEFT JOIN (
    SELECT magazine_id,
           COUNT(*) AS review_count,
           SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) AS pending_count
    FROM reader_comments
    GROUP BY magazine_id
  ) c ON c.magazine_id = m.id
  ORDER BY COALESCE(m.published_at, m.created_at) DESC, m.id DESC
";
$rows = pdo()->query($sql)->fetchAll(PDO::FETCH_ASSOC);


?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Magazines</title>
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

/* chips / badges */
.chip{
  display:inline-flex; align-items:center; gap:6px;
  padding:6px 10px; border-radius:999px; font-weight:800; font-size:12px;
  border:1px solid var(--border); background:#fff; color:var(--ink);
}
.chip-green{ border-color: color-mix(in oklab, var(--green) 35%, #fff 65%); color: var(--green-700); background: #f0fdf4 }
.chip-muted{ color:#334155; background:#f8fafc }

.badge-pending{
  display:inline-block; margin-left:8px; padding:2px 8px; font-size:12px; font-weight:800;
  background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; border-radius:999px
}

.note-cell{
  max-width:340px; overflow:hidden; white-space:nowrap; text-overflow:ellipsis;
}
</style>
<!-- Header / hero -->
<section class="hero-green">
  <div class="shell">
    <div class="hero-inner">
      <h1 class="hero-title">Magazines</h1>
      <div class="hero-actions">
        <a class="btn ghost" href="<?= $adminBase ?>/new.php">+ New Magazine</a>
        <a class="btn ghost" href="<?= $adminBase ?>/news_flash.php">News Flash</a>
        <a class="btn ghost" href="<?= $adminBase ?>/exclusive_magazines.php">Lanka Puwath</a>
        <a class="btn ghost" href="<?= $adminBase ?>/quick_news.php">Quick News</a>
        <a class="btn ghost" href="<?= $adminBase ?>/settings.php">Settings</a>
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
            <th>Issue</th>
            <th>Title</th>
            <th>PDF</th>
            <th>Banner</th>
            <th>Published</th>
            <th>Comments</th>
            <th>Author note</th>
            <th class="t-right"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="Issue"><?= h($r['label']) ?></td>
            <td data-label="Title"><?= $r['title'] !== null && $r['title'] !== '' ? h($r['title']) : '—' ?></td>
            <td data-label="PDF"><?= h(basename($r['pdf_file'] ?? '')) ?></td>
            <td data-label="Banner"><?= !empty($r['banner_file']) ? h(basename($r['banner_file'])) : '—' ?></td>

            <td data-label="Published" class="no-wrap">
              <?php if ((int)($r['published'] ?? 0) === 1): ?>
                <span class="chip chip-green">Published</span>
                <a class="btn pill ghost" href="<?= $adminBase ?>/toggle_publish.php?id=<?= (int)$r['id'] ?>&to=0">Unpublish</a>
              <?php else: ?>
                <span class="chip chip-muted">Unpublished</span>
                <a class="btn pill ghost" href="<?= $adminBase ?>/toggle_publish.php?id=<?= (int)$r['id'] ?>&to=1">Publish</a>
              <?php endif; ?>
            </td>

            <td data-label="Comments" class="no-wrap">
              <a class="btn pill ghost" href="<?= $adminBase ?>/comments.php?mag=<?= (int)$r['id'] ?>">
                <?= (int)$r['review_count'] ?> total
                <?php if ((int)$r['pending_count'] > 0): ?>
                  <span class="badge-pending"><?= (int)$r['pending_count'] ?> pending</span>
                <?php endif; ?>
              </a>
            </td>

            <td data-label="Author note" class="note-cell" title="<?= h($r['author_note'] ?? '') ?>">
              <?= ($r['author_note'] ?? '') !== '' ? h($r['author_note']) : '—' ?>
            </td>

            <td class="t-right">
              <form method="post" action="<?= $adminBase ?>/delete_mag.php"
                    onsubmit="return confirm('Delete this magazine?')">
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
<style>
  td.note{max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
</style>

