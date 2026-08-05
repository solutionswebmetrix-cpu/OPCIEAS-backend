<?php
$ch = curl_init('http://127.0.0.1:8000/api/products/list.php?limit=2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
echo $resp;
curl_close($ch);
