<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

$title       = trim($_POST['title']??'');
$issue_label = trim($_POST['issue_label']??'');
$issue_date  = $_POST['issue_date'] ?? '';
$published   = isset($_POST['published']) ? 1 : 0;

if (!$title || !$issue_label || !$issue_date) { die('Missing fields'); }

$slug = slugify($issue_label . '-' . date('Ymd', strtotime($issue_date)));

// ——— validate + move PDF
if (empty($_FILES['pdf']['tmp_name'])) die('PDF missing');
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['pdf']['tmp_name']);
if ($mime !== 'application/pdf') die('Only PDF allowed');

$pdfBase = $slug . '-' . substr(sha1(uniqid('',true)),0,6) . '.pdf';
$destPdf = __DIR__ . '/../assets/magazines/' . $pdfBase;
if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $destPdf)) die('Failed to move PDF');

// ——— optional cover
$coverBase = null;
if (!empty($_FILES['cover']['tmp_name'])) {
  $mime2 = $finfo->file($_FILES['cover']['tmp_name']);
  if (strpos($mime2, 'image/') !== 0) die('Cover must be an image');
  $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION) ?: 'jpg';
  $coverBase = $slug . '-' . substr(sha1(uniqid('',true)),0,6) . '.' . strtolower($ext);
  $destCover = __DIR__ . '/../assets/covers/' . $coverBase;
  if (!move_uploaded_file($_FILES['cover']['tmp_name'], $destCover)) {
    $coverBase = null; // non-fatal
  }
}

$stmt = pdo()->prepare("
  INSERT INTO magazines (title, issue_label, issue_date, slug, pdf_filename, cover_filename, published)
  VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$title, $issue_label, $issue_date, $slug, $pdfBase, $coverBase, $published]);

header('Location: /admin/index.php');
