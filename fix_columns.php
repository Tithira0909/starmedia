<?php
require __DIR__ . '/config/db.php';
$pdo = pdo();

$sql = "
ALTER TABLE ozlanka_magazines ADD COLUMN issue_date DATE DEFAULT NULL;
ALTER TABLE ozlanka_magazines ADD COLUMN author_note TEXT DEFAULT NULL;

ALTER TABLE exclusive_magazines ADD COLUMN issue_date DATE DEFAULT NULL;
ALTER TABLE exclusive_magazines ADD COLUMN author_note TEXT DEFAULT NULL;
";
try {
  $pdo->exec($sql);
  echo "Columns added to match query\n";
} catch (Exception $e) {
  echo $e->getMessage() . "\n";
}
