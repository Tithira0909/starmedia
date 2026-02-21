<?php
require __DIR__.'/_auth.php';
require_once __DIR__.'/../config/db.php';

$id = (int)($_POST['id'] ?? 0);
if($id>0){
  $stmt = pdo()->prepare("SELECT image_file FROM quick_news WHERE id=?");
  $stmt->execute([$id]);
  if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    if(!empty($row['image_file'])){
      $path = realpath(__DIR__ . '/../' . $row['image_file']);
      if($path && str_starts_with($path, realpath(__DIR__.'/../')) && is_file($path)){
        @unlink($path);
      }
    }
  }
  pdo()->prepare("DELETE FROM quick_news WHERE id=?")->execute([$id]);
}
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\');
header("Location: {$base}/quick_news.php");
