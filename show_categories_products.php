<?php
include 'user/connection.php';

echo "<h2>Category and Product Summary</h2>";

// Get all categories with their product count
$cat_query = "SELECT c.categories_id, c.categories_name, COUNT(p.product_id) as product_count 
              FROM tbl_categories c 
              LEFT JOIN tbl_products p ON c.categories_id = p.category_id 
              GROUP BY c.categories_id, c.categories_name 
              ORDER BY c.categories_id";

$cat_result = mysqli_query($conn, $cat_query);

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Category ID</th><th>Category Name</th><th>Product Count</th></tr>";

while ($c = mysqli_fetch_assoc($cat_result)) {
    echo "<tr>";
    echo "<td>" . $c['categories_id'] . "</td>";
    echo "<td>" . $c['categories_name'] . "</td>";
    echo "<td>" . $c['product_count'] . "</td>";
    echo "</tr>";
    
    // Show products in each category
    if ($c['product_count'] > 0) {
        $prod_query = "SELECT p.product_id, p.product_name, v.shop_name FROM tbl_products p 
                       LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id 
                       WHERE p.category_id = " . $c['categories_id'];
        $prod_result = mysqli_query($conn, $prod_query);
        
        while ($p = mysqli_fetch_assoc($prod_result)) {
            echo "<tr><td colspan='3'>└─ " . $p['product_name'] . " (" . $p['shop_name'] . ")</td></tr>";
        }
    }
}
echo "</table>";

mysqli_close($conn);
?>
