<?php
require 'config/config.php';
echo 'products=' . $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() . PHP_EOL;
echo 'categories=' . $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() . PHP_EOL;
echo 'images=' . $pdo->query('SELECT COUNT(*) FROM product_images')->fetchColumn() . PHP_EOL;
