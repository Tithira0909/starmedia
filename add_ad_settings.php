<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = pdo();
    $stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (key_name, value) VALUES ('left_ad_banner', ''), ('right_ad_banner', '')");
    $stmt->execute();
    echo "Added default ad banner settings.\n";
} catch (PDOException $e) {
    echo "Error inserting ad banner settings: " . $e->getMessage() . "\n";
}
