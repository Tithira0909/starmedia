<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

$siteBase  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$adminBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$error = '';
$success = '';

// Helper function to handle image uploads
function upload_ad_banner($fileInputName, $currentValue) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return $currentValue;
    }

    $file = $_FILES[$fileInputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Upload error code: {$file['error']} for $fileInputName");
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedTypes, true)) {
        throw new RuntimeException("Invalid file type for $fileInputName. Only JPG, PNG, GIF, WebP allowed.");
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
    ];
    $ext = $extensions[$mime] ?? 'png'; // Fallback just in case, though handled by in_array

    $filename = $fileInputName . '_' . time() . '.' . $ext;
    $uploadDir = __DIR__ . '/../assets/banners/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $dest = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("Failed to move uploaded file for $fileInputName.");
    }

    // Delete old banner if it exists and is different from default
    if ($currentValue && file_exists(__DIR__ . '/../' . $currentValue)) {
        unlink(__DIR__ . '/../' . $currentValue);
    }

    return 'assets/banners/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $youtube_video_url = trim((string)($_POST['youtube_video_url'] ?? ''));

    // Validate if it's a valid URL or empty
    if ($youtube_video_url !== '' && !filter_var($youtube_video_url, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Invalid URL format for YouTube Video.');
    }

    $pdo = pdo();

    // Fetch current ad banners to pass to upload function
    $stmt = $pdo->prepare("SELECT value FROM site_settings WHERE key_name = 'left_ad_banner'");
    $stmt->execute();
    $currentLeftBanner = $stmt->fetchColumn() ?: '';

    $stmt = $pdo->prepare("SELECT value FROM site_settings WHERE key_name = 'right_ad_banner'");
    $stmt->execute();
    $currentRightBanner = $stmt->fetchColumn() ?: '';

    $left_ad_banner = upload_ad_banner('left_ad_banner', $currentLeftBanner);
    $right_ad_banner = upload_ad_banner('right_ad_banner', $currentRightBanner);


    $pdo->beginTransaction();
    $sql = "INSERT INTO site_settings (key_name, value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE value = VALUES(value)";
    $stmt = $pdo->prepare($sql);

    $stmt->execute([':key' => 'youtube_video_url', ':value' => $youtube_video_url]);
    $stmt->execute([':key' => 'left_ad_banner', ':value' => $left_ad_banner]);
    $stmt->execute([':key' => 'right_ad_banner', ':value' => $right_ad_banner]);

    $pdo->commit();

    $success = 'Settings updated successfully.';

  } catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error = $e->getMessage();
  }
}

// Fetch current values
$settings = [
    'youtube_video_url' => '',
    'left_ad_banner' => '',
    'right_ad_banner' => ''
];

try {
    $stmt = pdo()->prepare("SELECT key_name, value FROM site_settings");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if ($results) {
        $settings = array_merge($settings, $results);
    }
} catch (PDOException $e) {
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
body { background: var(--bg); color: var(--ink); font-family: system-ui, -apple-system, sans-serif; }
.muted{color:var(--muted)} .shell{max-width:800px;margin-inline:auto;padding:22px}
.hero-green{background:linear-gradient(135deg,var(--green-50),#eafff2);border-bottom:1px solid var(--border)}
.hero-inner{display:flex;justify-content:space-between;align-items:center;gap:16px; max-width: 800px; margin: 0 auto; padding: 22px;}
.hero-title{margin:0 0 6px 0}.hero-sub{margin:0;color:var(--muted)}
.ui-card{background:#fff;border:1px solid var(--border);border-radius:16px;padding:24px;box-shadow:var(--shadow); margin-bottom: 24px;}
.f { margin-bottom: 20px; }
.f label{display:block;font-weight:700;margin-bottom:8px; color: #334155;}
.f input[type="text"],.f input[type="url"],.f textarea, .f input[type="file"]{width:100%;border:1px solid var(--border);border-radius:8px;padding:12px;font:inherit;color:var(--ink);background:#fff;outline:none;transition:border .15s,box-shadow .15s; box-sizing: border-box;}
.f input:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(22,163,74,.15)}
.f .help-text { font-size: 0.875rem; color: var(--muted); margin-top: 6px; }
.alert{padding:12px 14px;border-radius:8px;margin-bottom:20px;font-weight:600}.alert.error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}.alert.success{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
.btn{border:none;border-radius:8px;padding:12px 20px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;line-height:1;text-decoration:none;transition:transform .1s, background-color .2s}
.btn:active{transform:translateY(1px)}.btn.primary{background:var(--green);color:#fff;}.btn.primary:hover{background:var(--green-700)}
.btn.ghost{background:#fff;border:1px solid var(--border);color:var(--ink)}.btn.ghost:hover{background: #f1f5f9;}
.form-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)}
.preview-img { max-width: 200px; max-height: 200px; margin-top: 10px; border-radius: 8px; border: 1px solid var(--border); object-fit: cover; }
h3.section-title { margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border); color: #0f172a; }
</style>

<section class="hero-green">
  <div class="hero-inner">
    <div>
      <h1 class="hero-title">Site Settings</h1>
      <p class="hero-sub">Manage global configuration and ad placements.</p>
    </div>
  </div>
</section>

<section class="shell">
  <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">

    <div class="ui-card">
        <h3 class="section-title">Media Settings</h3>
        <div class="f">
            <label for="youtube_video_url">YouTube Video URL</label>
            <input type="url" id="youtube_video_url" name="youtube_video_url" value="<?= htmlspecialchars($settings['youtube_video_url']) ?>" placeholder="https://www.youtube.com/watch?v=..." >
            <div class="help-text">Enter the full YouTube video URL. Leave empty to hide the video section on the homepage.</div>
        </div>
    </div>

    <div class="ui-card">
        <h3 class="section-title">Ad Banners</h3>
        <p class="help-text" style="margin-bottom: 20px;">Upload banners to be displayed on the left and right sides of the main content. Recommended width: 160px - 300px. Recommended height: 600px. Allowed formats: JPG, PNG, GIF, WebP.</p>

        <div class="f">
            <label for="left_ad_banner">Left Ad Banner</label>
            <input type="file" id="left_ad_banner" name="left_ad_banner" accept="image/jpeg, image/png, image/gif, image/webp">
            <?php if ($settings['left_ad_banner']): ?>
                <div style="margin-top: 10px;">
                    <span class="help-text">Current:</span><br>
                    <img src="<?= $siteBase . '/' . htmlspecialchars($settings['left_ad_banner']) ?>" alt="Left Ad" class="preview-img">
                </div>
            <?php endif; ?>
        </div>

        <div class="f" style="margin-top: 24px; padding-top: 24px; border-top: 1px dashed var(--border);">
            <label for="right_ad_banner">Right Ad Banner</label>
            <input type="file" id="right_ad_banner" name="right_ad_banner" accept="image/jpeg, image/png, image/gif, image/webp">
            <?php if ($settings['right_ad_banner']): ?>
                <div style="margin-top: 10px;">
                    <span class="help-text">Current:</span><br>
                    <img src="<?= $siteBase . '/' . htmlspecialchars($settings['right_ad_banner']) ?>" alt="Right Ad" class="preview-img">
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ui-card form-actions">
      <a class="btn ghost" href="<?= $adminBase ?>/index.php">Cancel</a>
      <button class="btn primary" type="submit">Save Settings</button>
    </div>
  </form>
</section>
