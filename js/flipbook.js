// js/flipbook.js — single/two-page PDF flip viewer (PDF.js), with external control hooks
(function () {
  const $ = (s, d = document) => d.querySelector(s);

  // UI elements that exist in viewer.php
  const book        = $('#book');
  const prevBtn     = $('#prevBtn');
  const nextBtn     = $('#nextBtn');
  const pageInput   = $('#pageInput');
  const pageCountEl = $('#pageCount');
  const zoomInBtn   = $('#zoomIn');
  const zoomOutBtn  = $('#zoomOut');
  const zoomLevelEl = $('#zoomLevel');
  const toggleSpread= $('#toggleSpread');
  const fitBtn      = $('#fitBtn');

  // Data from viewer.php
  const PDF_URL = window.__PDF_URL__;

  // State
  let pdfDoc = null;
  let totalPages = 0;
  let currentPage = 1;
  let scale = 1;                 // render scale (also exported as window.zoom)
  let baseW = 0, baseH = 0;
  let rendering = false;

  // Spread mode: 2 for desktop (two-page), 1 for mobile (single page)
  function isSingleMode() {
    return window.matchMedia('(max-width: 900px)').matches;
  }
  let spread = isSingleMode() ? 1 : 2;
  let fitMode = 'auto';          // 'auto' | 'manual'

  function say(msg) { if (book) book.textContent = msg; }
  function updateZoomLabel() { if (zoomLevelEl) zoomLevelEl.textContent = Math.round(scale * 100) + '%'; }

  function ensureOddLeft() {
    if (spread === 2 && currentPage % 2 === 0) currentPage = Math.max(1, currentPage - 1);
  }

  function clampPage(p) {
    if (spread === 2) {
      const lastLeft = (totalPages % 2 === 0) ? totalPages - 1 : totalPages;
      return Math.max(1, Math.min(p, lastLeft));
    }
    return Math.max(1, Math.min(p, totalPages));
  }

  function go(delta) {
    let p = clampPage(currentPage + delta);
    if (spread === 2 && p % 2 === 0) p -= 1;
    currentPage = p;
    if (fitMode === 'auto') computeFit();
    render();
  }

  function computeFit() {
    if (!baseW || !baseH || !book) return;
    const availW = Math.max(1, book.clientWidth);
    const availH = Math.max(1, book.clientHeight);
    const across = (spread === 2 ? 2 : 1);
    const perW = availW / across;
    const sW = perW / baseW, sH = availH / baseH;
    scale = Math.max(0.1, Math.min(sW, sH));
    window.zoom = scale;                 // export
    updateZoomLabel();
  }

  async function pageToCanvas(page) {
    const viewport = page.getViewport({ scale });
    const dpr = Math.max(1, window.devicePixelRatio || 1);
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d', { alpha: false });
    canvas.width = Math.floor(viewport.width * dpr);
    canvas.height = Math.floor(viewport.height * dpr);
    canvas.style.width = Math.floor(viewport.width) + 'px';
    canvas.style.height = Math.floor(viewport.height) + 'px';
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    await page.render({ canvasContext: ctx, viewport }).promise;
    return canvas;
  }

  async function render() {
    if (!pdfDoc || rendering) return;
    rendering = true;
    book.innerHTML = '';

    if (spread === 1) {
      const p = await pdfDoc.getPage(currentPage);
      const el = document.createElement('div');
      el.className = 'page single';
      el.appendChild(await pageToCanvas(p));
      book.appendChild(el);
      pageInput.value = currentPage;
    } else {
      const L = currentPage, R = Math.min(currentPage + 1, totalPages);

      const leftWrap = document.createElement('div');
      leftWrap.className = 'page left';
      leftWrap.appendChild(await pageToCanvas(await pdfDoc.getPage(L)));

      const rightWrap = document.createElement('div');
      rightWrap.className = 'page right';
      if (R !== L) rightWrap.appendChild(await pageToCanvas(await pdfDoc.getPage(R)));

      book.appendChild(leftWrap);
      book.appendChild(rightWrap);

      pageInput.value = currentPage;
    }

    rendering = false;
  }

  async function init() {
    if (!window.pdfjsLib) { say('PDF.js failed to load.'); return; }

    // HEAD check can fail on some hosts; ignore errors
    try {
      const head = await fetch(PDF_URL, { method: 'HEAD' });
      if (!head.ok) { say(`Cannot fetch PDF (HTTP ${head.status}).`); return; }
    } catch (_) {}

    try {
      const loadingTask = pdfjsLib.getDocument({ url: PDF_URL });
      pdfDoc = await loadingTask.promise;
      totalPages = pdfDoc.numPages;
      pageCountEl.textContent = '/ ' + totalPages;

      const p1 = await pdfDoc.getPage(1);
      const vp1 = p1.getViewport({ scale: 1 });
      baseW = vp1.width; baseH = vp1.height;

      ensureOddLeft();
      computeFit();
      render();
    } catch (e) {
      console.error('[flipbook] open failed', e);
      say('Failed to open PDF.');
    }
  }

  /* ---- Controls ---- */
  prevBtn?.addEventListener('click', () => go(-spread));
  nextBtn?.addEventListener('click', () => go(+spread));

  zoomInBtn?.addEventListener('click', () => {
    fitMode = 'manual'; fitBtn && (fitBtn.textContent = 'Fit (off)');
    scale = Math.min(scale + 0.1, 3); window.zoom = scale; updateZoomLabel(); render();
  });
  zoomOutBtn?.addEventListener('click', () => {
    fitMode = 'manual'; fitBtn && (fitBtn.textContent = 'Fit (off)');
    scale = Math.max(scale - 0.1, 0.3); window.zoom = scale; updateZoomLabel(); render();
  });

  toggleSpread?.addEventListener('click', () => {
    spread = (spread === 2 ? 1 : 2);
    ensureOddLeft();
    if (fitMode === 'auto') computeFit();
    render();
  });

  fitBtn?.addEventListener('click', () => {
    fitMode = (fitMode === 'auto' ? 'manual' : 'auto');
    fitBtn.textContent = (fitMode === 'auto') ? 'Fit (on)' : 'Fit (off)';
    if (fitMode === 'auto') computeFit();
    render();
  });

  pageInput?.addEventListener('change', () => {
    let p = parseInt(pageInput.value, 10) || 1;
    p = clampPage(p);
    if (spread === 2 && p % 2 === 0) p -= 1;
    currentPage = p;
    render();
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft') go(-spread);
    if (e.key === 'ArrowRight') go(+spread);
    if (e.key === '+' || e.key === '=') { scale = Math.min(scale + 0.1, 3); window.zoom = scale; updateZoomLabel(); render(); }
    if (e.key === '-' || e.key === '_') { scale = Math.max(scale - 0.1, 0.3); window.zoom = scale; updateZoomLabel(); render(); }
  });

  window.addEventListener('resize', () => {
    const singleNow = isSingleMode();
    const nextSpread = singleNow ? 1 : 2;
    if (nextSpread !== spread) {
      spread = nextSpread; ensureOddLeft();
    }
    if (fitMode === 'auto') { computeFit(); render(); }
  });

  // Export hooks for index.php postMessage controls
  window.setZoom = function (z) {
    scale = Math.max(0.3, Math.min(3, z));
    window.zoom = scale;
    updateZoomLabel();
    render();
  };
  window.fitToWidth = function () {
    fitMode = 'auto';
    if (fitBtn) fitBtn.textContent = 'Fit (on)';
    computeFit(); render();
  };

  // Kick off
  if (document.readyState === 'complete' || document.readyState === 'interactive') setTimeout(init, 0);
  else document.addEventListener('DOMContentLoaded', init);
})();
