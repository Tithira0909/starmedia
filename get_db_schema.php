<?php
require_once 'config/db.php';
$pdo = pdo();
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
  echo "\nTable: $table\n";
  $cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
  print_r($cols);
}
