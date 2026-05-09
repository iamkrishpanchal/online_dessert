<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');

echo "=== CATEGORIES ===\n";
$cat = mysqli_query($conn, 'SELECT categories_id, categories_name FROM tbl_categories ORDER BY categories_id');
while($row = mysqli_fetch_assoc($cat)) {
    echo "ID {$row['categories_id']}: {$row['categories_name']}\n";
}

echo "\n=== PRODUCTS WITH CATEGORY_ID ===\n";
$prod = mysqli_query($conn, 'SELECT product_id, product_name, category_id FROM tbl_products ORDER BY category_id LIMIT 20');
while($row = mysqli_fetch_assoc($prod)) {
    echo "Product {$row['product_id']}: {$row['product_name']} (category_id={$row['category_id']})\n";
}

echo "\n=== COUNT BY CATEGORY_ID ===\n";
$count = mysqli_query($conn, 'SELECT category_id, COUNT(*) as cnt FROM tbl_products GROUP BY category_id ORDER BY category_id');
while($row = mysqli_fetch_assoc($count)) {
    echo "category_id {$row['category_id']}: {$row['cnt']} products\n";
}
?>
