<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    // Optional: delete the file from disk too?
    // For now, just delete the DB row.
    pdo()->prepare("DELETE FROM exclusive_magazines WHERE id = ?")->execute([$id]);
  }
}

header('Location: exclusive_magazines.php');
