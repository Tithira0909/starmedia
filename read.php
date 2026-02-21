<?php
// read.php — simple vertical scrolling PDF reader using PDF.js
declare(strict_types=1);

$file  = $_GET['file'] ?? '';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

if ($file === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $file)) { http_response_code(400); exit('Invalid file'); }

// Resolve path
$dirs = ['assets/magazines', 'assets/news_flash', 'assets/exclusive_magazines'];
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading: <?= h(basename($path)) ?></title>
    <style>
        body { margin: 0; padding: 0; background-color: #525659; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        #viewer { width: 100%; max-width: 900px; padding: 20px 0; }
        canvas { display: block; margin: 0 auto 20px auto; box-shadow: 0 4px 10px rgba(0,0,0,0.3); max-width: 96%; background: #fff; }
        #loading { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-family: sans-serif; font-size: 1.5rem; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
</head>
<body>
    <div id="loading">Loading PDF...</div>
    <div id="viewer"></div>

    <script>
        const url = <?= json_encode($pdfRelUrl) ?>;
        const viewer = document.getElementById('viewer');
        const loading = document.getElementById('loading');

        async function renderPDF() {
            try {
                const pdf = await pdfjsLib.getDocument(url).promise;
                loading.style.display = 'none';

                for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                    const page = await pdf.getPage(pageNum);
                    const scale = 1.5; // Good quality for most screens
                    const viewport = page.getViewport({ scale: scale });

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    // Responsive styling handled by CSS max-width: 96%

                    viewer.appendChild(canvas);

                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport
                    };
                    await page.render(renderContext).promise;
                }
            } catch (error) {
                console.error('Error rendering PDF:', error);
                loading.textContent = 'Error loading PDF.';
            }
        }

        renderPDF();
    </script>
</body>
</html>
