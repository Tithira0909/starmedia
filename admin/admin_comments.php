<?php
require __DIR__.'/_auth.php';
require_once __DIR__.'/../config/db.php';

$magId = (int)($_GET['mag'] ?? 0);
if($magId<=0){ header('Location: index.php'); exit; }

$mag = pdo()->prepare("SELECT * FROM magazines WHERE id=?");
$mag->execute([$magId]);
$mag = $mag->fetch(PDO::FETCH_ASSOC);
if(!$mag){ header('Location: index.php'); exit; }

$siteBase  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])),'/\\');
$adminBase = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\');

$rows = pdo()->prepare("SELECT * FROM reviews WHERE magazine_id=? ORDER BY id DESC");
$rows->execute([$magId]);
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html><meta charset="utf-8">
<title>Comments · <?=h($mag['label'])?></title>
<link rel="stylesheet" href="<?= $siteBase ?>/css/style.css">
<div class="container" style="padding:22px">
  <div style="display:flex;justify-content:space-between;align-items:center;">
    <h2>Comments · <?=h($mag['label'])?></h2>
    <a class="btn ghost" href="<?= $adminBase ?>/index.php">← Back</a>
  </div>
  <table style="width:100%;border-collapse:collapse">
    <thead><tr>
      <th align="left">Name</th>
      <th align="left">Rating</th>
      <th align="left">Comment</th>
      <th align="left">Status</th>
      <th></th>
    </tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr style="border-top:1px solid #e2e8f0">
        <td><?=h($r['name'])?></td>
        <td><?= (int)$r['rating'] ?>/5</td>
        <td><?=h($r['body'])?></td>
        <td><?= $r['is_approved']?'Approved':'Pending' ?></td>
        <td align="right" style="white-space:nowrap">
          <?php if(!$r['is_approved']): ?>
            <a class="btn ghost" href="<?= $adminBase ?>/toggle_comment.php?id=<?=$r['id']?>&to=1&mag=<?=$magId?>">Approve</a>
          <?php else: ?>
            <a class="btn ghost" href="<?= $adminBase ?>/toggle_comment.php?id=<?=$r['id']?>&to=0&mag=<?=$magId?>">Unapprove</a>
          <?php endif; ?>
          <a class="btn" style="background:#ef4444" href="<?= $adminBase ?>/delete_comment.php?id=<?=$r['id']?>&mag=<?=$magId?>" onclick="return confirm('Delete this comment?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
