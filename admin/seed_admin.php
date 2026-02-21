<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

$pdo = pdo();

/* Make sure table exists */
$pdo->exec("
CREATE TABLE IF NOT EXISTS admin_users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(190) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  name           VARCHAR(120) NOT NULL DEFAULT 'Admin',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* RESET the admin user (delete if exists, then insert again) */
$email = 'noreply@touguide.com';
$pass  = 'tourguide#2025';

$pdo->prepare("DELETE FROM admin_users WHERE LOWER(email)=LOWER(?)")->execute([$email]);

$hash = password_hash($pass, PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO admin_users (email, password_hash, name) VALUES (?,?,?)")
    ->execute([strtolower($email), $hash, 'Admin']);

echo "Admin reset. Email: <b>$email</b> | Password: <b>$pass</b><br><br>";
echo '<a href="login.php">Go to login</a> | <a href="/tour/index.php">Home</a>';
