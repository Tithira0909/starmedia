<?php
declare(strict_types=1);
require_once __DIR__.'/config/db.php';

function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $magId  = (int)($_POST['magazine_id'] ?? 0);
  $name   = trim($_POST['name'] ?? '');
  $rating = (int)($_POST['rating'] ?? 0);
  $body   = trim($_POST['text'] ?? '');

  if($magId>0 && $name!=='' && $body!=='' && $rating>=1 && $rating<=5){
    $stmt = pdo()->prepare("INSERT INTO reviews (magazine_id,name,rating,body,is_approved)
                            VALUES (?,?,?,?,0)");
    $stmt->execute([$magId,$name,$rating,$body]);
  }
}
header('Location: '.($_POST['redirect'] ?? './').'#magazine');
