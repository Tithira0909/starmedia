<?php
// viewer.php — secure, self-contained flipbook (desktop: flip spread, mobile: single w/ zoom)
declare(strict_types=1);

$file  = $_GET['file'] ?? '';
$embed = isset($_GET['embed']) && $_GET['embed'] === '1';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if ($file === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $file)) { http_response_code(400); exit('Invalid file'); }

// Search in both directories
$dirs = ['assets/magazines', 'assets/news_flash'];
$path = null;
$pdfRelUrl = null;

foreach ($dirs as $dir) {
    $currentPath = __DIR__ . '/' . $dir . '/' . $file;
    if (is_file($currentPath)) {
        $path = $currentPath;
        $pdfRelUrl = $dir . '/' . basename($file);
        break;
    }
}

if (!$path) { http_response_code(404); exit('File not found'); }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
<meta name="theme-color" content="#059669"/>
<title>Reading: <?= h(basename($path)) ?></title>

<!-- PageFlip (flip animation) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/css/min.css">
<script src="https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.min.js"></script>

<!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js" crossorigin="anonymous"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";</script>

<!-- Panzoom -->
<script src="https://unpkg.com/@panzoom/panzoom@4.6.0/dist/panzoom.min.js"></script>

<style>
  :root{--g:#10b981;--ring:#dcfce7;--ink:#0b1727}
  html,body{height:100%;margin:0}
  body{color:var(--ink);font:15px/1.55 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial;background:#fff;display:flex;flex-direction:column}
  .viewer-header{display:flex;gap:10px;align-items:center;padding:10px 12px;border-bottom:1px solid #e5e7eb;background:linear-gradient(180deg,#fff,#f7fffb);position:sticky;top:0;z-index:20}
  .viewer-header .title{font-weight:700}
  .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;cursor:pointer}
  .btn.primary{background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:0}
  main{display:flex;flex-direction:column;gap:10px;padding:12px;flex-grow:1}
  .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:8px;border:1px solid #e5e7eb;border-radius:12px;background:#fff}
  .spacer{flex:1}
  #pageInput{width:72px;padding:6px 8px;border:1px solid #d1d5db;border-radius:10px}
  #wrap{width:min(1200px,96vw);max-height:calc(100vh - 160px);margin:auto;border:1px solid var(--ring);border-radius:18px;background:#fff;overflow:hidden}
  #wrap.single-mode{aspect-ratio:210/297}
  #wrap:not(.single-mode){aspect-ratio:calc(2*210)/297}
  #flip{width:100%;height:100%}
  .stf__parent{background:linear-gradient(120deg,#ecfdf5,#ffffff)}
  .page-no{position:absolute;bottom:8px;right:12px;color:#94a3b8;font-size:.82rem;background:rgba(255,255,255,.75);padding:2px 6px;border-radius:8px}
  /* Embed mode: hide chrome (index.php provides its own) */
  .embed .viewer-header,.embed .toolbar{display:none!important}
  .embed main{padding:0!important;gap:0!important}
  .embed #wrap{border:0;border-radius:0;max-height:100vh}
  #loader{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,.9);z-index:9999;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:bold;color:var(--ink)}
  @media (min-width: 901px) {
    #wrap {
      max-height: calc(100vh - 180px);
    }
  }
</style>
</head>
<body class="<?= $embed ? 'embed' : '' ?>">

<div id="loader">Loading...</div>

<?php if (!$embed): ?>
<header class="viewer-header">
  <a class="btn" href="index.php">← Back</a>
  <div class="title">📖 <?= h(basename($path)) ?></div>
  <div class="spacer"></div>
  <button id="btnFs" class="btn primary">⤢ Fullscreen</button>
</header>
<?php endif; ?>

<main>
  <div class="toolbar" role="toolbar" aria-label="Flipbook controls">
    <button id="prevBtn" class="btn">⟵ Prev</button>
    <div>
      <label for="pageInput" class="sr" style="position:absolute;left:-9999px">Page</label>
      <input id="pageInput" type="number" min="1" value="1">
      <span id="pageCount">/ ?</span>
    </div>
    <button id="nextBtn" class="btn">Next ⟶</button>
    <div class="spacer"></div>
    <button id="zoomOut" class="btn">−</button>
    <span id="zoomLevel">100%</span>
    <button id="zoomIn" class="btn">+</button>
    <button id="fitBtn" class="btn primary">Fit</button>
    <button id="toggleSpread" class="btn">Toggle Spread</button>
  </div>

  <div id="wrap"><div id="flip" aria-label="Flipbook"></div></div>
</main>

<script>
  const pdfSrc = <?= json_encode($pdfRelUrl) ?>;
  let pdfDoc=null, pages=[], singleMode = matchMedia("(max-width:900px)").matches;

  // Exported globals for index.php control bar
  window.flip = null;
  window.panzoomers = [];

  function updateZoomLabel(){
    const current = window.panzoomers[window.flip.getCurrentPageIndex()];
    if (current) {
      document.getElementById('zoomLevel').textContent = Math.round(current.getScale()*100)+'%';
    }
  }
  window.setZoom = z => {
    const current = window.panzoomers[window.flip.getCurrentPageIndex()];
    if (current) {
      current.zoomToPoint(z, { clientX: current.elem.width / 2, clientY: current.elem.height / 2 });
    }
  };
  window.fitToWidth = () => {
    const current = window.panzoomers[window.flip.getCurrentPageIndex()];
    if (current) {
      current.zoom(1);
      current.pan(0, 0);
    }
  };

  // Render one PDF page to an <img> URL
  async function pageToImage(pdf, num, scale=2){
    const page = await pdf.getPage(num);
    const vp = page.getViewport({ scale });
    const cvs = document.createElement('canvas');
    const ctx = cvs.getContext('2d');
    cvs.width = vp.width; cvs.height = vp.height;
    await page.render({ canvasContext: ctx, viewport: vp }).promise;
    return cvs.toDataURL('image/jpeg', 0.92);
  }

  async function buildFlip(){
    // destroy old
    if (window.flip) { try{ window.flip.destroy(); }catch(e){} window.flip=null; }
    document.getElementById('flip').innerHTML = '';
    document.getElementById('wrap').classList.toggle('single-mode', singleMode);

    window.flip = new St.PageFlip(document.getElementById('flip'), {
      width: 500, height: 707, // A4 aspect ratio, will be stretched by `size`
      size: "stretch",
      minWidth: 300, maxWidth: 2200, minHeight: 300, maxHeight: 2400,
      usePortrait: singleMode,          // ← single page on mobile
      showCover: false,
      mobileScrollSupport: true,
      maxShadowOpacity: .18,
      flippingTime: 450,
      clickEventForward: true
    });

    const items = pages.map((src, i) => {
      const p = document.createElement('div');
      p.className = 'page';
      p.style.width='100%';
      p.style.height='100%';
      p.style.position='relative';
      const img = new Image(); img.src = src; img.alt = 'Page ' + (i+1);
      img.style.width='100%'; img.style.height='100%'; img.style.objectFit='contain';
      p.appendChild(img);
      const tag = document.createElement('div'); tag.className='page-no'; tag.textContent = (i+1);
      p.appendChild(tag);
      return p;
    });

    window.flip.loadFromHTML(items);

    window.panzoomers = [];
    const page_elements = document.querySelectorAll('.page');
    page_elements.forEach(item => {
      const img = item.querySelector('img');
      const pz = Panzoom(img, {
        maxScale: 5,
        minScale: 1,
        contain: 'outside'
      });
      window.panzoomers.push(pz);
      item.addEventListener('wheel', pz.zoomWithWheel);
    });
    window.flip.on("flip", syncUI);
    syncUI();
    setTimeout(()=>window.fitToWidth(), 80);
  }

  function syncUI(){
    const idx = window.flip.getCurrentPageIndex(); // 0-based
    document.getElementById('pageInput').value = idx + 1;
    document.getElementById('pageCount').textContent = "/ " + pages.length;
    updateZoomLabel();
  }

  // Buttons
  document.getElementById('prevBtn').addEventListener('click', ()=> window.flip?.flipPrev());
  document.getElementById('nextBtn').addEventListener('click', ()=> window.flip?.flipNext());
  document.getElementById('zoomIn').addEventListener('click', ()=> {
    const current = window.panzoomers[window.flip.getCurrentPageIndex()];
    if (current) current.zoomIn();
  });
  document.getElementById('zoomOut').addEventListener('click',()=> {
    const current = window.panzoomers[window.flip.getCurrentPageIndex()];
    if (current) current.zoomOut();
  });
  document.getElementById('fitBtn').addEventListener('click', window.fitToWidth);
  document.getElementById('toggleSpread').addEventListener('click', ()=>{ singleMode=!singleMode; buildFlip(); });
  document.getElementById('pageInput').addEventListener('change', e=>{
    const n = Math.max(1, Math.min(parseInt(e.target.value||'1',10), pages.length));
    window.flip?.flip(n-1);
  });
  document.getElementById('btnFs')?.addEventListener('click', ()=>{
    const r=document.documentElement;
    if(!document.fullscreenElement){ r.requestFullscreen?.(); } else { document.exitFullscreen?.(); }
  });
  document.addEventListener('keydown', e=>{
    if(e.key==='ArrowRight') window.flip?.flipNext();
    else if(e.key==='ArrowLeft') window.flip?.flipPrev();
    else if(e.key==='+'||e.key==='=') { const current = window.panzoomers[window.flip.getCurrentPageIndex()]; if (current) current.zoomIn(); }
    else if(e.key==='-'||e.key==='_') { const current = window.panzoomers[window.flip.getCurrentPageIndex()]; if (current) current.zoomOut(); }
  });
  window.addEventListener('resize', ()=>{
    const now = matchMedia("(max-width:900px)").matches;
    if (now !== singleMode){ singleMode = now; buildFlip(); }
  });

  // Load the PDF → rasterize pages → build flip
  (async function init(){
    const loader = document.getElementById('loader');
    try{
      pdfDoc = await pdfjsLib.getDocument(pdfSrc).promise;
      pages = [];
      for (let i=1;i<=pdfDoc.numPages;i++){
        pages.push(await pageToImage(pdfDoc, i, 2)); // scale 2 for crispness
      }
      document.getElementById('pageCount').textContent = "/ " + pages.length;
      await buildFlip();
      loader.style.display = 'none';
    }catch(e){
      console.error(e);
      document.getElementById('flip').textContent = 'Failed to load PDF.';
      loader.style.display = 'none';
    }
  })();

  // Bridge: external controls from index.php
  window.addEventListener('message', (ev)=>{
    const cmd = ev.data && ev.data.flipCmd; if(!cmd) return;
    if (cmd==='next') window.flip?.flipNext();
    else if (cmd==='prev') window.flip?.flipPrev();
    else if (cmd==='zoomIn') { const current = window.panzoomers[window.flip.getCurrentPageIndex()]; if (current) current.zoomIn(); }
    else if (cmd==='zoomOut') { const current = window.panzoomers[window.flip.getCurrentPageIndex()]; if (current) current.zoomOut(); }
    else if (cmd==='fit') window.fitToWidth();
    else if (cmd==='rebuild') buildFlip();
    else if (cmd==='goto') {
      const page = ev.data.page;
      if (page) window.flip?.flip(page - 1);
    }
  });

  function syncUI(){
    const idx = window.flip.getCurrentPageIndex(); // 0-based
    document.getElementById('pageInput').value = idx + 1;
    document.getElementById('pageCount').textContent = "/ " + pages.length;
    updateZoomLabel();
    window.parent.postMessage({
      type: 'pagechange',
      page: idx + 1,
      total: pages.length
    }, '*');
  }
</script>

</body>
</html>
