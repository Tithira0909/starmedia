<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

$siteBase  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$adminBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $youtube_video_url = trim((string)($_POST['youtube_video_url'] ?? ''));

    // Validate if it's a valid URL or empty
    if ($youtube_video_url !== '' && !filter_var($youtube_video_url, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Invalid URL format.');
    }

    $pdo = pdo();
    $sql = "INSERT INTO site_settings (key_name, value) VALUES ('youtube_video_url', :value)
            ON DUPLICATE KEY UPDATE value = VALUES(value)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':value' => $youtube_video_url]);

    $success = 'Settings updated successfully.';

  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}

// Fetch current value
try {
    $stmt = pdo()->prepare("SELECT value FROM site_settings WHERE key_name = 'youtube_video_url'");
    $stmt->execute();
    $currentValue = $stmt->fetchColumn() ?: '';
} catch (PDOException $e) {
    $currentValue = '';
    // Table might not exist yet, user needs to run migration
    if (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
       $error = "Table 'site_settings' missing. Please run create_settings_table.php.";
    }
}

?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings</title>
<link rel="stylesheet" href="<?= $siteBase ?>/css/style.css">
<style>
:root{--green:#16a34a;--green-600:#16a34a;--green-700:#15803d;--green-50:#ecfdf5;--bg:#f8fafc;--ink:#0f172a;--muted:#64748b;--card:#ffffff;--border:#e2e8f0;--shadow:0 18px 40px rgba(2,8,23,.08)}
.muted{color:var(--muted)} .shell{max-width:1100px;margin-inline:auto;padding:22px}
.hero-green{background:linear-gradient(135deg,var(--green-50),#eafff2);border-bottom:1px solid var(--border)}
.hero-inner{display:flex;justify-content:space-between;align-items:center;gap:16px}
.hero-title{margin:0 0 6px 0}.hero-sub{margin:0;color:var(--muted)}
.ui-card{background:#fff;border:1px solid var(--border);border-radius:16px;padding:18px;box-shadow:var(--shadow)}
.grid2{display:grid;grid-template-columns:1fr 1fr}.gap16{gap:16px}@media(max-width:880px){.grid2{grid-template-columns:1fr}}.span2{grid-column:1 / -1}
.f label{display:block;font-weight:800;margin-bottom:8px}
.f input[type="text"],.f input[type="url"],.f textarea{width:100%;border:1px solid var(--border);border-radius:12px;padding:12px;font:inherit;color:var(--ink);background:#fff;outline:none;transition:border .15s,box-shadow .15s}
.f input:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(22,163,74,.15)}
.alert{padding:12px 14px;border-radius:12px;margin-bottom:14px;font-weight:700}.alert.error{background:#fff7f7;border:1px solid #fecaca;color:#7f1d1d}.alert.success{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d}
.btn{border:none;border-radius:12px;padding:12px 16px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:8px;line-height:1;text-decoration:none;transition:transform .08s,filter .15s,box-shadow .2s}
.btn:active{transform:translateY(1px)}.btn.primary{background:var(--green);color:#fff;box-shadow:0 12px 28px rgba(22,163,74,.28)}.btn.primary:hover{filter:brightness(.97)}
.btn.ghost{background:#fff;border:1px solid var(--border);color:var(--ink)}.btn.ghost:hover{box-shadow:0 10px 24px rgba(2,8,23,.06)}
.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;padding-top:14px;border-top:1px solid var(--border)}
</style>

<section class="hero-green">
  <div class="shell">
    <div class="hero-inner">
      <div>
        <h1 class="hero-title">Settings</h1>
        <p class="hero-sub">Manage site-wide settings.</p>
      </div>
    </div>
  </div>
</section>

<section class="shell">
  <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form class="ui-card mag-form" method="post">
    <div class="grid2 gap16">
      <div class="f span2">
        <label for="youtube_video_url">YouTube Video URL</label>
        <input type="url" id="youtube_video_url" name="youtube_video_url" value="<?= htmlspecialchars($currentValue) ?>" placeholder="https://www.youtube.com/watch?v=..." >
        <div class="muted" style="margin-top: 5px; font-size: 0.9em;">Enter the full YouTube video URL (e.g., https://www.youtube.com/watch?v=dQw4w9WgXcQ). Leave empty to hide the video section.</div>
      </div>
    </div>

    <div class="form-actions">
      <a class="btn ghost" href="<?= $adminBase ?>/index.php">← Back</a>
      <button class="btn primary" type="submit">Save Settings</button>
    </div>
  </form>
</section>
