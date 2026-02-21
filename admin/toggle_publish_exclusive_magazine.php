<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
$to = (int)($_GET['to'] ?? 0);

if ($id > 0) {
  pdo()->prepare("UPDATE exclusive_magazines SET is_published = ? WHERE id = ?")
       ->execute([$to, $id]);
}

header('Location: exclusive_magazines.php');
