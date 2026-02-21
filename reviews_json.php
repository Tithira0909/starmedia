<?php
require_once __DIR__.'/config/db.php';
header('Content-Type: application/json');

$magId = (int)($_GET['mag'] ?? 0);
if($magId<=0){ echo json_encode(['ok'=>false]); exit; }

$avgQ = pdo()->prepare("SELECT COUNT(*) cnt, COALESCE(AVG(rating),0) avg
                        FROM reviews WHERE magazine_id=? AND is_approved=1");
$avgQ->execute([$magId]);
$avg = $avgQ->fetch(PDO::FETCH_ASSOC);

$itemsQ = pdo()->prepare("SELECT name,rating,body,created_at
                          FROM reviews WHERE magazine_id=? AND is_approved=1
                          ORDER BY id DESC LIMIT 10");
$itemsQ->execute([$magId]);
$items = $itemsQ->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'ok'=>true,
  'avg'=>round((float)$avg['avg'],2),
  'count'=>(int)$avg['cnt'],
  'items'=>$items,
]);
