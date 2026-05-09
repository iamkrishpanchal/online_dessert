<?php
/**
 * Add Test Vendor & Products
 * Run this once to add sample vendor "creamy crust" with products
 */

include __DIR__ . '/admin/connection.php';

if (!isset($conn) || !$conn) {
    die('Database connection failed.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Test Vendor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b6d4fe;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin-top: 20px;
        }
        .button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📦 Add Test Vendor Data</h1>

        <?php
        $success_count = 0;
        $error_count = 0;
        
        // Check if categories exist, if not create them
        $categories_to_add = [
            ['name' => 'Cakes', 'desc' => 'Fresh baked cakes'],
            ['name' => 'Pastries', 'desc' => 'Delicious pastries'],
            ['name' => 'Desserts', 'desc' => 'Sweet desserts'],
            ['name' => 'Beverages', 'desc' => 'Drinks and beverages']
        ];
        
        // Insert categories if not exists
        foreach ($categories_to_add as $cat) {
            $query = "INSERT IGNORE INTO tbl_categories (category_name, description, is_active) 
                      VALUES ('" . mysqli_real_escape_string($conn, $cat['name']) . "', 
                              '" . mysqli_real_escape_string($conn, $cat['desc']) . "', 1)";
            if (mysqli_query($conn, $query)) {
                $success_count++;
            }
        }
        
        // Get category IDs
        $cat_query = "SELECT category_id, category_name FROM tbl_categories WHERE category_name IN ('Cakes', 'Pastries', 'Desserts', 'Beverages')";
        $cat_result = mysqli_query($conn, $cat_query);
        $categories = [];
        while ($row = mysqli_fetch_assoc($cat_result)) {
            $categories[$row['category_name']] = $row['category_id'];
        }
        
        // Add test vendor "creamy crust"
        $vendor_name = 'Creamy Crust';
        $shop_name = 'creamy crust';
        $email = 'creamycrust@example.com';
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $phone = '9876543210';
        $address = '123 Main Street, Downtown';
        $city = 'New York';
        $pincode = '10001';
        
        // detect vendor_discount_percent column before constructing insert
$discCol = '';
$chk = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'vendor_discount_percent'");
if ($chk && mysqli_num_rows($chk) > 0) {
    $discCol = 'vendor_discount_percent';
}
if ($discCol) {
    $vendor_query = "INSERT INTO tbl_vendors 
                        (vendor_name, shop_name, email, password, phone, address, city, pincode, 
                         description, {$discCol}, is_online, is_active, verification_status) 
                        VALUES 
                        ('" . mysqli_real_escape_string($conn, $vendor_name) . "',
                         '" . mysqli_real_escape_string($conn, $shop_name) . "',
                         '" . mysqli_real_escape_string($conn, $email) . "',
                         '" . mysqli_real_escape_string($conn, $password) . "',
                         '" . mysqli_real_escape_string($conn, $phone) . "',
                         '" . mysqli_real_escape_string($conn, $address) . "',
                         '" . mysqli_real_escape_string($conn, $city) . "',
                         '" . mysqli_real_escape_string($conn, $pincode) . "',
                         'Premium layered cakes and pastries',
                         0.00, 1, 1, 'approved')";
} else {
    $vendor_query = "INSERT INTO tbl_vendors 
                        (vendor_name, shop_name, email, password, phone, address, city, pincode, 
                         description, is_online, is_active, verification_status) 
                        VALUES 
                        ('" . mysqli_real_escape_string($conn, $vendor_name) . "',
                         '" . mysqli_real_escape_string($conn, $shop_name) . "',
                         '" . mysqli_real_escape_string($conn, $email) . "',
                         '" . mysqli_real_escape_string($conn, $password) . "',
                         '" . mysqli_real_escape_string($conn, $phone) . "',
                         '" . mysqli_real_escape_string($conn, $address) . "',
                         '" . mysqli_real_escape_string($conn, $city) . "',
                         '" . mysqli_real_escape_string($conn, $pincode) . "',
                         'Premium layered cakes and pastries',
                         1, 1, 'approved')";
}
        
        $vendor_result = mysqli_query($conn, $vendor_query);
        if ($vendor_result) {
            $vendor_id = mysqli_insert_id($conn);
            echo '<div class="status success">✓ Vendor "creamy crust" added successfully! (ID: ' . $vendor_id . ')</div>';
        } else {
            $vendor_id = null;
            // Check if vendor already exists
            $check = mysqli_query($conn, "SELECT vendor_id FROM tbl_vendors WHERE shop_name = 'creamy crust' LIMIT 1");
            if (mysqli_num_rows($check) > 0) {
                $row = mysqli_fetch_assoc($check);
                $vendor_id = $row['vendor_id'];
                echo '<div class="status info">ℹ Vendor "creamy crust" already exists (ID: ' . $vendor_id . ')</div>';
            } else {
                echo '<div class="status error">✗ Error adding vendor: ' . mysqli_error($conn) . '</div>';
                $error_count++;
            }
        }
        
        // Add products for vendor
        if ($vendor_id) {
            $products = [
                [
                    'name' => 'Chocolate Cake',
                    'price' => 450,
                    'category' => 'Cakes',
                    'description' => 'Rich and moist chocolate cake with ganache topping'
                ],
                [
                    'name' => 'Vanilla Cheesecake',
                    'price' => 550,
                    'category' => 'Cakes',
                    'description' => 'Creamy vanilla cheesecake with berry topping'
                ],
                [
                    'name' => 'Strawberry Pastry',
                    'price' => 250,
                    'category' => 'Pastries',
                    'description' => 'Fresh strawberry pastry with cream filling'
                ],
                [
                    'name' => 'Chocolate Croissant',
                    'price' => 200,
                    'category' => 'Pastries',
                    'description' => 'Crispy croissant with chocolate layers'
                ],
                [
                    'name' => 'Brownie Fudge',
                    'price' => 180,
                    'category' => 'Desserts',
                    'description' => 'Thick and gooey brownie fudge'
                ],
                [
                    'name' => 'Ice Cream Sundae',
                    'price' => 220,
                    'category' => 'Desserts',
                    'description' => 'Vanilla ice cream with chocolate sauce and nuts'
                ],
                [
                    'name' => 'Iced Coffee',
                    'price' => 150,
                    'category' => 'Beverages',
                    'description' => 'Chilled coffee with ice and cream'
                ],
                [
                    'name' => 'Hot Chocolate',
                    'price' => 120,
                    'category' => 'Beverages',
                    'description' => 'Warm and rich hot chocolate'
                ]
            ];
            
            echo '<div class="status info">Adding products for vendor...</div>';
            
            foreach ($products as $product) {
                $cat_id = isset($categories[$product['category']]) ? $categories[$product['category']] : 1;
                
                $product_query = "INSERT INTO tbl_products 
                                 (vendor_id, product_name, description, category_id, price, quantity_available, is_active) 
                                 VALUES 
                                 (" . $vendor_id . ",
                                  '" . mysqli_real_escape_string($conn, $product['name']) . "',
                                  '" . mysqli_real_escape_string($conn, $product['description']) . "',
                                  " . $cat_id . ",
                                  " . $product['price'] . ",
                                  50, 1)";
                
                if (mysqli_query($conn, $product_query)) {
                    $success_count++;
                } else {
                    echo '<div class="status error">✗ Error adding product ' . htmlspecialchars($product['name']) . ': ' . mysqli_error($conn) . '</div>';
                    $error_count++;
                }
            }
        }
        
        // Display added products
        if ($vendor_id) {
            $products_result = mysqli_query($conn, "SELECT product_id, product_name, price, category_id FROM tbl_products WHERE vendor_id = " . $vendor_id);
            
            if (mysqli_num_rows($products_result) > 0) {
                echo '<div class="status success">✓ Added ' . mysqli_num_rows($products_result) . ' products successfully!</div>';
                echo '<h2>Products in "creamy crust":</h2>';
                echo '<table>';
                echo '<thead><tr><th>Product Name</th><th>Price</th><th>Category</th></tr></thead>';
                echo '<tbody>';
                
                while ($product = mysqli_fetch_assoc($products_result)) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($product['product_name']) . '</td>';
                    echo '<td>₹' . $product['price'] . '</td>';
                    echo '<td>Category ID: ' . $product['category_id'] . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            }
        }
        
        echo '<div class="status success">✓ Total: ' . $success_count . ' items added</div>';
        if ($error_count > 0) {
            echo '<div class="status error">✗ Errors: ' . $error_count . '</div>';
        }
        ?>
        
        <h2 style="margin-top: 30px;">Next Steps:</h2>
        <ol style="line-height: 2;">
            <li>Click the button below to test the search</li>
            <li>Open the menu page with the vendor search</li>
            <li>Search for "creamy crust" to see all products</li>
        </ol>
        
        <a href="user-temp/menu.php?vendor=creamy+crust" class="button">→ Test Search (creamy crust)</a>
        <a href="user-temp/index.html" class="button">← Back to Home</a>
    </div>
</body>
</html>

<?php
mysqli_close($conn);
?>
