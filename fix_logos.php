<?php
$content = file_get_contents('index.php');

// Change from 50% to 25% to reduce size by 50%
$content = str_replace('max-width: 50%; width: 50%;', 'max-width: 25%; width: 25%;', $content);

file_put_contents('index.php', $content);
echo "Reduced logo sizes in index.php";
