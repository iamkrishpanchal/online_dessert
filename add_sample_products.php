<?php
// quick helper: insert a few dummy products into each category so you can test the
// "click category => products show" behaviour.  Run this once then remove/ignore it.

include 'user/connection.php';

echo "<h1>Seeding sample products</h1>\n";

// fetch all active categories
$cats = [];
$cres = mysqli_query($conn, "SELECT categories_id, categories_name FROM tbl_categories WHERE categories_status = 1 ORDER BY categories_id");
if ($cres) {
    while ($c = mysqli_fetch_assoc($cres)) {
        $cats[] = $c;
    }
}

if (empty($cats)) {
    echo "<p style='color:red'>No categories found. Make sure tbl_categories has data.</p>\n";
    exit;
}

// make some generic product rows per category
foreach ($cats as $cat) {
    $cid = intval($cat['categories_id']);
    $namePrefix = $cat['categories_name'];

    // how many products already exist for this category?
    $countRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tbl_products WHERE category_id = $cid");
    $cnt = $countRes ? intval(mysqli_fetch_assoc($countRes)['cnt']) : 0;

    if ($cnt >= 3) {
        echo "<p>Category {$cid} ({$namePrefix}) already has $cnt products, skipping.</p>\n";
        continue;
    }

    for ($i = 1; $i <= 3; $i++) {
        $pname = "$namePrefix Sample $i";
        $price = rand(100, 999);
        $img = 'images/default-product.png';
        $vendor_id = 0; // zero means no vendor; viewCategory will still show them

        $stmt = mysqli_prepare($conn, "INSERT INTO tbl_products (product_name, category_id, vendor_id, product_price, product_stock, product_status, product_image, created_at, updated_at) VALUES (?, ?, ?, ?, 10, 1, ?, NOW(), NOW())");
        mysqli_stmt_bind_param($stmt, 'siids', $pname, $cid, $vendor_id, $price, $img);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        echo "Added product '$pname' to category {$cid}.<br>\n";
    }
}

mysqli_close($conn);

echo "<p>Done. <a href='user/index.php'>Back to home</a></p>\n";
