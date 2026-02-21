<?php
require __DIR__.'/_auth.php';
require_once __DIR__.'/../config/db.php';

$id = (int)($_POST['id'] ?? 0);
if($id>0){
  $stmt = pdo()->prepare("SELECT pdf_file,banner_file FROM news_flash WHERE id=?");
  $stmt->execute([$id]);
  if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    foreach(['pdf_file','banner_file'] as $k){
      if(!empty($row[$k])){
        $path = realpath(__DIR__ . '/../' . $row[$k]);
        if($path && str_starts_with($path, realpath(__DIR__.'/../')) && is_file($path)){
          @unlink($path);
        }
      }
    }
  }
  pdo()->prepare("DELETE FROM news_flash WHERE id=?")->execute([$id]);
}
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\');
header("Location: {$base}/news_flash.php");
