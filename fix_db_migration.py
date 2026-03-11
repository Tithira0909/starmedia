with open('config/db.php', 'r') as f:
    content = f.read()

replacement = """
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
    CREATE TABLE IF NOT EXISTS site_settings (
      key_name VARCHAR(100) PRIMARY KEY,
      value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    INSERT IGNORE INTO site_settings (key_name, value) VALUES ('left_ad_banner', '');
    INSERT IGNORE INTO site_settings (key_name, value) VALUES ('right_ad_banner', '');
  ");

  return $pdo;
}
"""

old_code = """
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
"""

new_content = content.replace(old_code, replacement)

with open('config/db.php', 'w') as f:
    f.write(new_content)

print("Fixed DB migration in config/db.php")
