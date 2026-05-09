<?php
$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');

if (!$conn) {
    die("Connection failed");
}

echo "<h2>Checking tbl_products structure and data</h2>";

// Check columns in tbl_products
echo "<h3>Columns in tbl_products:</h3>";
$result = mysqli_query($conn, "SHOW COLUMNS FROM tbl_products");
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
}
echo "</ul>";

// Check products with their category info
echo "<h3>Products (showing first 20):</h3>";
$result = mysqli_query($conn, "SELECT product_id, product_name, category_id FROM tbl_products LIMIT 20");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Category ID</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr><td>" . $row['product_id'] . "</td><td>" . $row['product_name'] . "</td><td>" . ($row['category_id'] ?? 'NULL') . "</td></tr>";
}
echo "</table>";

// Check if any products have category_id = 14
echo "<h3>Products with category_id = 14:</h3>";
$result = mysqli_query($conn, "SELECT product_id, product_name FROM tbl_products WHERE category_id = 14");
$count = mysqli_num_rows($result);
echo "Found: " . $count . " products<br>";
if ($count > 0) {
    echo "<ul>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>" . $row['product_name'] . "</li>";
    }
    echo "</ul>";
}

// Check category 14
echo "<h3>Category with ID 14:</h3>";
$result = mysqli_query($conn, "SELECT categories_id, categories_name FROM tbl_categories WHERE categories_id = 14");
if ($row = mysqli_fetch_assoc($result)) {
    echo "Found: " . $row['categories_name'];
} else {
    echo "Category 14 not found!";
}

// Show count by category
echo "<h3>Products per category:</h3>";
$result = mysqli_query($conn, "SELECT category_id, COUNT(*) as cnt FROM tbl_products GROUP BY category_id ORDER BY category_id");
echo "<ul>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<li>category_id " . ($row['category_id'] ?? 'NULL') . ": " . $row['cnt'] . " products</li>";
}
echo "</ul>";
?>
