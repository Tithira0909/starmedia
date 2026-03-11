<?php
require __DIR__ . '/config/db.php';
$pdo = pdo();

$tables = ['magazines', 'news_flash', 'exclusive_magazines', 'ozlanka_magazines'];

foreach ($tables as $table) {
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `sort_order` INT NOT NULL DEFAULT 0");
        echo "Added sort_order to $table\n";
    } catch (PDOException $e) {
        // Ignore "Duplicate column name" error
        if ($e->getCode() === '42S21') {
            echo "sort_order already exists on $table\n";
        } else {
            echo "Error adding sort_order to $table: " . $e->getMessage() . "\n";
        }
    }
}
echo "Done.\n";
