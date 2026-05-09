<?php
// Debug category products issue
include 'user/connection.php';

$category_id = 14; // Cakes

echo "<h2>Debug Category Products - ID: $category_id</h2>";

// 1. Check if category exists
echo "<h3>1. Category Check:</h3>";
$cat_query = "SELECT * FROM tbl_categories WHERE categories_id = $category_id";
$cat_result = mysqli_query($conn, $cat_query);
if ($cat_result && mysqli_num_rows($cat_result) > 0) {
    $cat = mysqli_fetch_assoc($cat_result);
    echo "<pre>"; print_r($cat); echo "</pre>";
} else {
    echo "Category not found!";
}

// 2. List all tables
echo "<h3>2. Available Tables:</h3>";
$tables_result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($tables_result)) {
    $tables[] = $row[0];
}
echo "<pre>"; print_r($tables); echo "</pre>";

// 3. Check products table structure
$prod_table = null;
foreach (['tbl_products', 'tbl_product', 'products', 'product'] as $t) {
    if (in_array($t, $tables)) {
        $prod_table = $t;
        break;
    }
}

if ($prod_table) {
    echo "<h3>3. Products Table: <strong>$prod_table</strong></h3>";
    
    // Get columns
    $cols_result = mysqli_query($conn, "SHOW COLUMNS FROM $prod_table");
    echo "<p>Columns:</p>";
    $columns = [];
    while ($col = mysqli_fetch_assoc($cols_result)) {
        $columns[] = $col['Field'];
        echo "- " . $col['Field'] . "<br>";
    }
    
    // 4. Count all products
    echo "<h3>4. Total Products Count:</h3>";
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM $prod_table");
    $count_row = mysqli_fetch_assoc($count_result);
    echo "Total: " . $count_row['cnt'] . "<br>";
    
    // 5. Check for category-related columns
    echo "<h3>5. Category Column Detection:</h3>";
    $category_cols = ['category_id', 'categories_id', 'cat_id', 'category', 'categories_name'];
    $found_col = null;
    foreach ($category_cols as $col) {
        if (in_array($col, $columns)) {
            echo "<strong>Found: $col</strong><br>";
            $found_col = $col;
        }
    }
    
    if (!$found_col) {
        echo "No category column found!<br>";
    }
    
    // 6. Check products for this category
    if ($found_col) {
        echo "<h3>6. Products in Category $category_id (using column: $found_col):</h3>";
        $prod_query = "SELECT * FROM $prod_table WHERE $found_col = $category_id LIMIT 10";
        $prod_result = mysqli_query($conn, $prod_query);
        
        if ($prod_result) {
            $count = mysqli_num_rows($prod_result);
            echo "Found: $count products<br>";
            
            if ($count > 0) {
                echo "<pre>";
                while ($p = mysqli_fetch_assoc($prod_result)) {
                    print_r($p);
                    echo "---<br>";
                }
                echo "</pre>";
            }
        } else {
            echo "Query error: " . mysqli_error($conn) . "<br>";
        }
    }
} else {
    echo "No products table found!";
}

mysqli_close($conn);
?>
