<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = pdo();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            key_name VARCHAR(255) PRIMARY KEY,
            value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table 'site_settings' created successfully (or already exists).\n";

    // Insert default youtube link if not exists
    $stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (key_name, value) VALUES ('youtube_video_url', '')");
    $stmt->execute();
    echo "Default settings inserted.\n";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
