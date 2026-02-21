<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = pdo();
    $sql = "
    CREATE TABLE IF NOT EXISTS exclusive_magazines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(255) NOT NULL,
        title VARCHAR(255),
        issue_date DATE,
        author_note TEXT,
        pdf_file VARCHAR(255),
        banner_file VARCHAR(255),
        is_published TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        published_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);
    echo "Table 'exclusive_magazines' created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
