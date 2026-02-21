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
  return $pdo;
}

// tiny helpers
function slugify(string $s): string {
  $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
  $s = preg_replace('~[^a-zA-Z0-9]+~', '-', $s);
  return strtolower(trim($s, '-')) ?: substr(sha1(mt_rand()), 0, 8);
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
