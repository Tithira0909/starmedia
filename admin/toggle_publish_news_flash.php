<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$to = (int)($_GET['to'] ?? 0);   // 1 publish, 0 unpublish

$stmt = pdo()->prepare(
  "UPDATE news_flash
     SET is_published = ?,
         published_at = CASE WHEN ? = 1 THEN NOW() ELSE NULL END
   WHERE id = ?"
);
$stmt->execute([$to, $to, $id]);

header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/news_flash.php');
exit;
