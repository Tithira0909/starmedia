<?php
require __DIR__.'/_auth.php';
require_once __DIR__.'/../config/db.php';
$id  = (int)($_GET['id'] ?? 0);
$to  = (int)($_GET['to'] ?? 0);
$mag = (int)($_GET['mag'] ?? 0);
if($id>0){
  pdo()->prepare("UPDATE reviews SET is_approved=? WHERE id=?")->execute([$to,$id]);
}
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\');
header("Location: {$base}/comments.php?mag={$mag}");
