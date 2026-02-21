<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/db.php';

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $pass  = (string)($_POST['password'] ?? '');

  if ($email === '' || $pass === '') {
    $error = 'Enter email & password';
  } else {
    $pdo = pdo();

    // Case-insensitive lookup. Also trims spaces.
    $stmt = $pdo->prepare("
      SELECT id, email, password_hash
      FROM admin_users
      WHERE LOWER(email) = LOWER(?)
      LIMIT 1
    ");
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if ($u && password_verify($pass, $u['password_hash'])) {
      session_regenerate_id(true);
      $_SESSION['admin_id'] = (int)$u['id'];

      $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // /tour/admin
      header('Location: ' . $base . '/index.php');
      exit;
    }

    $error = 'Invalid email or password';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login · Lanka Tour Guide</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  :root {
    --green: #16a34a;
    --green-dark: #15803d;
    --bg-grad: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
    --text: #0f172a;
    --muted: #64748b;
    --border: #e2e8f0;
  }
  * { box-sizing: border-box; }
  body {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: var(--bg-grad);
    color: var(--text);
    display: grid;
    place-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
  }
  .login-wrap {
    width: 100%;
    max-width: 420px;
    animation: fadeIn 0.6s ease-out;
  }
  .brand-area {
    text-align: center;
    margin-bottom: 24px;
  }
  .brand-logo {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    margin-bottom: 16px;
    box-shadow: 0 10px 25px rgba(22, 163, 74, 0.2);
  }
  .brand-title {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.3;
    color: var(--green-dark);
    margin: 0;
  }
  .brand-subtitle {
    font-size: 1rem;
    color: var(--muted);
    margin-top: 6px;
    font-weight: 600;
  }
  .card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
    padding: 32px;
  }
  .form-group {
    margin-bottom: 16px;
  }
  .form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.9rem;
    color: var(--text);
  }
  .form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--border);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.2s;
    outline: none;
  }
  .form-control:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
  }
  .btn-submit {
    width: 100%;
    padding: 14px;
    border: 0;
    border-radius: 12px;
    background: var(--green);
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 8px;
  }
  .btn-submit:hover {
    background: var(--green-dark);
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(22, 163, 74, 0.2);
  }
  .btn-submit:active {
    transform: translateY(1px);
  }
  .error-msg {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 0.9rem;
    font-weight: 600;
    text-align: center;
  }
  .footer-link {
    text-align: center;
    margin-top: 24px;
    font-size: 0.9rem;
    color: var(--muted);
  }
  .footer-link a {
    color: var(--green);
    text-decoration: none;
    font-weight: 600;
  }
  .footer-link a:hover {
    text-decoration: underline;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  @media (max-width: 480px) {
    .card {
      padding: 24px;
    }
    .brand-title {
      font-size: 1.25rem;
    }
  }
</style>
</head>
<body>
  <div class="login-wrap">
    <div class="brand-area">
      <img src="../assets/img/logo.png" alt="Logo" class="brand-logo">
      <h1 class="brand-title">Lanka Tour Guide</h1>
      <div class="brand-subtitle">ලංකා පුවත් Admin Portal</div>
    </div>

    <div class="card">
      <?php if ($error): ?>
        <div class="error-msg">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: text-bottom; margin-right: 6px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" autocomplete="off">
        <div class="form-group">
          <label for="email">Email Address</label>
          <input id="email" name="email" type="email" class="form-control" placeholder="admin@example.com" value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-submit">Sign In</button>
      </form>
    </div>

    <div class="footer-link">
      <a href="../index.php">← Back to Website</a>
    </div>
  </div>
</body>
</html>
