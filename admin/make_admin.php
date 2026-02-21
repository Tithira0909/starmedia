<?php
require_once __DIR__ . '/../config/db.php';

$email = 'claude@lankatourguide.lk';               // change
$pass  = 'tourguide#9954';           // change

$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = pdo()->prepare("INSERT INTO admin_users (email, password_hash) VALUES (?, ?)");
$stmt->execute([$email, $hash]);
echo "Admin created: $email\n";
