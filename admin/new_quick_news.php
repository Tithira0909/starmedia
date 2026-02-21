<?php
require __DIR__ . '/_auth.php';
require_once __DIR__ . '/../config/db.php';

if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$siteBase  = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$adminBase = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$error = '';

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
}
function unique_name(string $original): string {
  $ext  = strtolower(pathinfo($original, PATHINFO_EXTENSION));
  $base = bin2hex(random_bytes(8));
  return $base . ($ext ? '.' . $ext : '');
}
function save_upload(string $field, string $destDir, array $allowExt, int $maxBytes): ?string {
  if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK)         throw new RuntimeException('Upload error for '.$field);
  if ($f['size']  >  $maxBytes)              throw new RuntimeException('File too large for '.$field);
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowExt, true))      throw new RuntimeException('Invalid file type for '.$field);

  ensure_dir($destDir);
  $name = unique_name($f['name']);
  $abs  = rtrim($destDir,'/\\') . DIRECTORY_SEPARATOR . $name;
  if (!move_uploaded_file($f['tmp_name'], $abs)) throw new RuntimeException('Failed to save '.$field.' to disk');

  $projectRoot = str_replace('\\', '/', realpath(__DIR__.'/..'));
  $absolutePath = str_replace('\\', '/', realpath($abs));
  $projectRootWithSlash = rtrim($projectRoot, '/') . '/';
  $webBase = str_replace($projectRootWithSlash, '', $absolutePath);
  return $webBase;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $news_text = trim((string)($_POST['news_text'] ?? ''));

    if ($news_text === '') throw new RuntimeException('News text is required.');

    $imageRel = save_upload(
      'image',
      realpath(__DIR__ . '/../assets/quick_news') ?: __DIR__ . '/../assets/quick_news',
      ['png','jpg','jpeg','webp','gif','svg'],
      10 * 1024 * 1024
    );
    if (!$imageRel) throw new RuntimeException('Please choose an image file.');

    $pdo = pdo();
    $sql = "INSERT INTO quick_news (news_text, image_file, created_at) VALUES (:news_text, :image_file, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':news_text' => $news_text,
      ':image_file' => $imageRel,
    ]);

    header('Location: ' . $adminBase . '/quick_news.php');
    exit;
  } catch (Throwable $e) {
    $error = $e->getMessage();
  }
}
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root{--green:#16a34a;--green-600:#16a34a;--green-700:#15803d;--green-50:#ecfdf5;--bg:#f8fafc;--ink:#0f172a;--muted:#64748b;--card:#ffffff;--border:#e2e8f0;--shadow:0 18px 40px rgba(2,8,23,.08)}
.muted{color:var(--muted)} .shell{max-width:1100px;margin-inline:auto;padding:22px}
.hero-green{background:linear-gradient(135deg,var(--green-50),#eafff2);border-bottom:1px solid var(--border)}
.hero-inner{display:flex;justify-content:space-between;align-items:center;gap:16px}
.hero-title{margin:0 0 6px 0}.hero-sub{margin:0;color:var(--muted)}
.ui-card{background:#fff;border:1px solid var(--border);border-radius:16px;padding:18px;box-shadow:var(--shadow)}
.grid2{display:grid;grid-template-columns:1fr 1fr}.gap16{gap:16px}@media(max-width:880px){.grid2{grid-template-columns:1fr}}.span2{grid-column:1 / -1}
.f label{display:block;font-weight:800;margin-bottom:8px}
.f input[type="text"],.f input[type="date"],.f textarea{width:100%;border:1px solid var(--border);border-radius:12px;padding:12px;font:inherit;color:var(--ink);background:#fff;outline:none;transition:border .15s,box-shadow .15s}
.f textarea{resize:vertical}.f input:focus,.f textarea:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(22,163,74,.15)}
.dropzone{position:relative;display:flex;align-items:center;gap:12px;border:1.5px dashed #cbd5e1;border-radius:14px;background:#f8fafc;padding:14px 16px;min-height:104px;transition:border-color .2s,background .2s}
.dropzone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer}
.dropzone .dz-icon{display:grid;place-items:center;background:#fff;border:1px solid var(--border);border-radius:12px;width:64px;height:64px;color:var(--green-700);box-shadow:0 10px 24px rgba(2,8,23,.06)}
.dropzone .dz-body{font-weight:700}.dropzone .dz-sub{font-weight:600}.dropzone.is-over{background:#eefdf4;border-color:var(--green)}
.img-preview{width:100%;margin-top:10px;border-radius:12px;object-fit:cover;aspect-ratio:16/9;box-shadow:0 10px 24px rgba(2,8,23,.08)}
.file-chip{display:inline-flex;align-items:center;gap:8px;margin-top:10px;background:#fff;border:1px solid var(--border);border-radius:999px;padding:8px 12px;box-shadow:0 10px 24px rgba(2,8,23,.06);font-weight:700}
.file-dot{width:8px;height:8px;border-radius:50%;background:var(--green)}.file-name{max-width:42ch;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.file-size{color:var(--muted);font-weight:800}
.alert{padding:12px 14px;border-radius:12px;margin-bottom:14px;font-weight:700}.alert.error{background:#fff7f7;border:1px solid #fecaca;color:#7f1d1d}
.btn{border:none;border-radius:12px;padding:12px 16px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:8px;line-height:1;text-decoration:none;transition:transform .08s,filter .15s,box-shadow .2s}
.btn:active{transform:translateY(1px)}.btn.primary{background:var(--green);color:#fff;box-shadow:0 12px 28px rgba(22,163,74,.28)}.btn.primary:hover{filter:brightness(.97)}
.btn.ghost{background:#fff;border:1px solid var(--border);color:var(--ink)}.btn.ghost:hover{box-shadow:0 10px 24px rgba(2,8,23,.06)}
.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px;padding-top:14px;border-top:1px solid var(--border)}
</style>

<section class="hero-green">
  <div class="shell">
    <div class="hero-inner">
      <div>
        <h1 class="hero-title">Create a New Quick News</h1>
        <p class="hero-sub">Upload an image and add a short text snippet.</p>
      </div>
    </div>
  </div>
</section>

<section class="shell">
  <?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>

  <form class="ui-card mag-form" method="post" enctype="multipart/form-data">
    <div class="grid2 gap16">
      <div class="f span2">
        <label for="news_text">News Text</label>
        <textarea id="news_text" name="news_text" rows="3" placeholder="A short news snippet" required></textarea>
      </div>

      <div class="f span2">
        <label>Image <span class="muted">(required)</span></label>
        <div class="dropzone" data-for="image">
          <input id="image" name="image" type="file" accept="image/*" required>
          <div class="dz-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M4 5h16v14H4zM4 16l4-4 3 3 5-5 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="dz-body">
            <strong>Click to add</strong> or drop an image
            <div class="dz-sub muted">Recommended size: 800x600</div>
          </div>
        </div>
        <img id="imagePreview" class="img-preview" alt="" hidden>
        <div id="imageChip" class="file-chip" hidden></div>
      </div>
    </div>

    <div class="form-actions">
      <a class="btn ghost" href="<?= $adminBase ?>/quick_news.php">← Back</a>
      <button class="btn primary" type="submit">Save Quick News</button>
    </div>
  </form>
</section>

<script>
(function () {
  const $ = (s, r=document) => r.querySelector(s);
  const fmt = b => {const u=['B','KB','MB','GB'];let i=0;while(b>1024&&i<u.length-1){b/=1024;i++;}return b.toFixed(i?1:0)+' '+u[i];};

  function bindZone(input, chipEl, previewImg){
    const zone = input.closest('.dropzone');
    const showChip = f => { chipEl.innerHTML = `<span class="file-dot"></span><span class="file-name">${f.name}</span><span class="file-size">${fmt(f.size)}</span>`; chipEl.hidden = false; };
    input.addEventListener('change', () => {
      const f = input.files && input.files[0];
      if (!f) { chipEl.hidden = true; if (previewImg) previewImg.hidden = true; return; }
      showChip(f);
      if (previewImg && f.type.startsWith('image/')) { previewImg.src = URL.createObjectURL(f); previewImg.hidden = false; }
    });
    ['dragenter','dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('is-over'); }));
    ['dragleave','drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('is-over'); }));
    zone.addEventListener('drop', e => { if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; input.dispatchEvent(new Event('change', {bubbles:true})); } });
  }
  bindZone(document.getElementById('image'), document.getElementById('imageChip'), document.getElementById('imagePreview'));
})();
</script>
