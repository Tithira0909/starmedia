<?php
require_once __DIR__ . '/config.php';

function pdo(): PDO {
  static $pdo;
  if ($pdo) return $pdo;
  $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
  $pdo = new PDO($dsn, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);

  // Automate DB Migrations
  $pdo->exec("
    CREATE TABLE IF NOT EXISTS ozlanka_magazines (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255),
      pdf_file VARCHAR(255),
      banner_file VARCHAR(255),
      label VARCHAR(255),
      published_at DATETIME,
      created_at DATETIME,
      is_published TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS site_settings (
      key_name VARCHAR(100) PRIMARY KEY,
      value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    INSERT IGNORE INTO site_settings (key_name, value) VALUES ('left_ad_banner', '');
    INSERT IGNORE INTO site_settings (key_name, value) VALUES ('right_ad_banner', '');
  ");

  return $pdo;
}

// tiny helpers
function slugify(string $s): string {
  $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
  $s = preg_replace('~[^a-zA-Z0-9]+~', '-', $s);
  return strtolower(trim($s, '-')) ?: substr(sha1(mt_rand()), 0, 8);
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
