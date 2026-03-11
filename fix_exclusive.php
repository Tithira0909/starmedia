<?php
$content = file_get_contents('index.php');

$search = '<!-- Exclusive Magazines -->';
$replace = '<!-- Exclusive Magazines -->
<div style="text-align: center; margin-bottom: 20px;">
  <img src="assets/logo-2.png" alt="Lanka Puwath Logo" style="max-width: 50%; width: 50%; height: auto;">
</div>
';
$content = str_replace($search, $replace, $content);

file_put_contents('index.php', $content);
echo "Added Lanka Puwath logo to index.php";
