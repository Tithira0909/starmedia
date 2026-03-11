<?php
require __DIR__ . '/config/db.php';
$pdo = pdo();

$sql = "
CREATE TABLE IF NOT EXISTS reader_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    magazine_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";
$pdo->exec($sql);
echo "Created reader_comments\n";
