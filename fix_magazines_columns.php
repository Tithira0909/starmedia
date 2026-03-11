<?php
require __DIR__ . '/config/db.php';
$pdo = pdo();

$sql = "
ALTER TABLE magazines ADD COLUMN is_published TINYINT(1) DEFAULT 1;
ALTER TABLE magazines ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
";
try {
  $pdo->exec($sql);
  echo "Added missing columns\n";
} catch (Exception $e) {
  echo $e->getMessage() . "\n";
}
