<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_POST['id']??0);
if ($id<=0) die('bad id');

$pdo = pdo();
$mag = $pdo->prepare("SELECT pdf_filename, cover_filename FROM magazines WHERE id=?");
$mag->execute([$id]);
$f = $mag->fetch();
if ($f) {
  @unlink(__DIR__ . '/../assets/magazines/' . $f['pdf_filename']);
  if (!empty($f['cover_filename'])) @unlink(__DIR__ . '/../assets/covers/' . $f['cover_filename']);
}
$pdo->prepare("DELETE FROM magazines WHERE id=?")->execute([$id]);

header('Location: /admin/index.php');
