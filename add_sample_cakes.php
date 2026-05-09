<?php
include 'user/connection.php';

echo "<h2>Adding Sample Products to Category 14 (Cakes)</h2>";

// Get vendor details
$vendors = [];
$v_query = "SELECT vendor_id, shop_name FROM tbl_vendors LIMIT 3";
$v_result = mysqli_query($conn, $v_query);
while ($v = mysqli_fetch_assoc($v_result)) {
    $vendors[] = $v;
}

if (empty($vendors)) {
    echo "<p style='color: red;'>Error: No vendors found!</p>";
    exit;
}

// Sample cake products
$cakes = [
    [
        'name' => 'Classic Vanilla Cake',
        'price' => 450.00,
        'stock' => 25,
        'image' => 'cake_vanilla.jpg'
    ],
    [
        'name' => 'Strawberry Cheesecake',
        'price' => 650.00,
        'stock' => 15,
        'image' => 'cake_strawberry.jpg'
    ],
    [
        'name' => 'Black Forest Cake',
        'price' => 750.00,
        'stock' => 20,
        'image' => 'cake_blackforest.jpg'
    ],
    [
        'name' => 'Carrot Cake',
        'price' => 500.00,
        'stock' => 18,
        'image' => 'cake_carrot.jpg'
    ]
];

// Insert products
$added = 0;
foreach ($cakes as $cake) {
    // Randomly assign to different vendors
    $vendor = $vendors[array_rand($vendors)];
    
    $insert_query = "INSERT INTO tbl_products (product_name, category_id, vendor_id, product_price, product_stock, product_status, product_image, created_at, updated_at) 
                     VALUES (?, 14, ?, ?, ?, 1, ?, NOW(), NOW())";
    
    $stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($stmt, 'sidis', $cake['name'], $vendor['vendor_id'], $cake['price'], $cake['stock'], $cake['image']);
    
    if (mysqli_stmt_execute($stmt)) {
        $added++;
        echo "✓ Added: " . $cake['name'] . " (Vendor: " . $vendor['shop_name'] . ")<br>";
    } else {
        echo "✗ Error adding " . $cake['name'] . ": " . mysqli_error($conn) . "<br>";
    }
    mysqli_stmt_close($stmt);
}

echo "<h3>Added $added products to Category 14</h3>";

// Show updated category
echo "<h3>Category 14 Now Contains:</h3>";
$check_query = "SELECT p.product_id, p.product_name, p.product_price, v.shop_name FROM tbl_products p 
                LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id 
                WHERE p.category_id = 14";
$check_result = mysqli_query($conn, $check_query);

$count = 0;
while ($p = mysqli_fetch_assoc($check_result)) {
    $count++;
    echo "$count. " . $p['product_name'] . " - Rs. " . $p['product_price'] . " (" . $p['shop_name'] . ")<br>";
}

echo "<p><a href='http://localhost/Sem-6Project/user/viewCategory.php?id=14'>Click here to view the category page</a></p>";

mysqli_close($conn);
?>
