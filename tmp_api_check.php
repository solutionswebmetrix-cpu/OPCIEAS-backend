<?php
$base = 'http://localhost:8000';
$ch = curl_init($base . '/api/products/list.php?limit=5&status=Published');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo 'HTTP=' . $httpCode . PHP_EOL;
echo $resp . PHP_EOL;
curl_close($ch);
