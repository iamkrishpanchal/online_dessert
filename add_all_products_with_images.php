<?php
include 'user/connection.php';

echo "<h2>Adding Products With Images to All Categories</h2>";

// Create images directory if it doesn't exist
$img_dir = __DIR__ . '/user/uploads/products';
if (!is_dir($img_dir)) {
    mkdir($img_dir, 0755, true);
    echo "<p>Created directory: $img_dir</p>";
}

// Function to generate a simple placeholder image
function generatePlaceholderImage($filepath, $name) {
    $width = 300;
    $height = 300;
    $image = imagecreatetruecolor($width, $height);
    
    // Random pastel colors
    $colors = [
        imagecolorallocate($image, 255, 179, 184), // pink
        imagecolorallocate($image, 255, 223, 186), // peach
        imagecolorallocate($image, 255, 250, 205), // lemon chiffon
        imagecolorallocate($image, 198, 239, 206), // mint
        imagecolorallocate($image, 207, 226, 243), // light blue
        imagecolorallocate($image, 230, 190, 255)  // lavender
    ];
    
    $bgColor = $colors[array_rand($colors)];
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    
    // Add text
    $textColor = imagecolorallocate($image, 50, 50, 50);
    $fontFile = __DIR__ . '/user/css/fonts/OpenSans-Bold.ttf';
    
    // Check if font file exists, if not use default
    if (file_exists($fontFile)) {
        imagettftext($image, 16, 0, 30, 150, $textColor, $fontFile, wordwrap($name, 15, "\n", true));
    } else {
        // Fallback to simple text
        $lines = explode("\n", wordwrap($name, 20, "\n"));
        $y = 140;
        foreach ($lines as $line) {
            imagestring($image, 3, 50, $y, substr($line, 0, 20), $textColor);
            $y += 20;
        }
    }
    
    imagejpeg($image, $filepath, 85);
    imagedestroy($image);
}

// Category data with products
$categories_data = [
    11 => [
        'name' => 'Cookies',
        'products' => [
            ['name' => 'Chocolate Chip Cookies', 'price' => 180, 'stock' => 30],
            ['name' => 'Oatmeal Raisin Cookies', 'price' => 160, 'stock' => 25],
            ['name' => 'Sugar Cookies', 'price' => 150, 'stock' => 35],
            ['name' => 'Peanut Butter Cookies', 'price' => 200, 'stock' => 20]
        ]
    ],
    12 => [
        'name' => 'Pastries',
        'products' => [
            ['name' => 'Croissant', 'price' => 120, 'stock' => 40],
            ['name' => 'Danish Pastry', 'price' => 140, 'stock' => 25],
            ['name' => 'Puff Pastry', 'price' => 110, 'stock' => 30],
            ['name' => 'Apple Turnover', 'price' => 130, 'stock' => 20]
        ]
    ],
    13 => [
        'name' => 'waffles',
        'products' => [
            ['name' => 'Belgian Waffle', 'price' => 250, 'stock' => 20],
            ['name' => 'Chocolate Waffle', 'price' => 300, 'stock' => 15],
            ['name' => 'Strawberry Waffle', 'price' => 280, 'stock' => 18],
            ['name' => 'Nutella Waffle', 'price' => 320, 'stock' => 12]
        ]
    ],
    15 => [
        'name' => 'Pancake',
        'products' => [
            ['name' => 'Buttermilk Pancakes', 'price' => 220, 'stock' => 25],
            ['name' => 'Blueberry Pancakes', 'price' => 250, 'stock' => 20],
            ['name' => 'Chocolate Pancakes', 'price' => 270, 'stock' => 15],
            ['name' => 'Banana Pancakes', 'price' => 240, 'stock' => 22]
        ]
    ],
    16 => [
        'name' => 'Cookies',
        'products' => [
            ['name' => 'Macadamia Nut Cookies', 'price' => 220, 'stock' => 18],
            ['name' => 'White Chocolate Cookies', 'price' => 210, 'stock' => 22],
            ['name' => 'Almond Cookies', 'price' => 190, 'stock' => 25],
            ['name' => 'Gingerbread Cookies', 'price' => 175, 'stock' => 30]
        ]
    ],
    17 => [
        'name' => 'Cookies',
        'products' => [
            ['name' => 'Double Chocolate Cookies', 'price' => 230, 'stock' => 20],
            ['name' => 'Caramel Cookies', 'price' => 210, 'stock' => 24],
            ['name' => 'Coconut Cookies', 'price' => 185, 'stock' => 28],
            ['name' => 'Hazelnut Cookies', 'price' => 215, 'stock' => 21]
        ]
    ],
    18 => [
        'name' => 'Pastries',
        'products' => [
            ['name' => 'Mille-feuille', 'price' => 180, 'stock' => 15],
            ['name' => 'Eclair', 'price' => 160, 'stock' => 20],
            ['name' => 'Tart', 'price' => 150, 'stock' => 25],
            ['name' => 'Custard Pastry', 'price' => 140, 'stock' => 30]
        ]
    ]
];

// Get all vendors
$vendors = [];
$v_query = "SELECT vendor_id, shop_name FROM tbl_vendors";
$v_result = mysqli_query($conn, $v_query);
while ($v = mysqli_fetch_assoc($v_result)) {
    $vendors[] = $v;
}

if (empty($vendors)) {
    echo "<p style='color: red;'>Error: No vendors found!</p>";
    exit;
}

echo "<h3>Processing Categories:</h3>";

$total_added = 0;
foreach ($categories_data as $cat_id => $cat_data) {
    // Check if category already has products
    $check_query = "SELECT COUNT(*) as cnt FROM tbl_products WHERE category_id = $cat_id";
    $check_result = mysqli_query($conn, $check_query);
    $check_row = mysqli_fetch_assoc($check_result);
    
    if ($check_row['cnt'] > 0) {
        echo "<p><strong>Category $cat_id ({$cat_data['name']}):</strong> Already has " . $check_row['cnt'] . " products - skipping</p>";
        continue;
    }
    
    echo "<p><strong>Category $cat_id ({$cat_data['name']}):</strong> Adding products...</p>";
    
    foreach ($cat_data['products'] as $idx => $product) {
        // Generate image
        $img_filename = 'cat_' . $cat_id . '_prod_' . ($idx + 1) . '.jpg';
        $img_filepath = $img_dir . '/' . $img_filename;
        
        generatePlaceholderImage($img_filepath, $product['name']);
        
        // Randomly assign to different vendors
        $vendor = $vendors[array_rand($vendors)];
        
        $insert_query = "INSERT INTO tbl_products (product_name, category_id, vendor_id, product_price, product_stock, product_status, product_image, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        $img_path = 'user/uploads/products/' . $img_filename;
        mysqli_stmt_bind_param($stmt, 'siidis', $product['name'], $cat_id, $vendor['vendor_id'], $product['price'], $product['stock'], $img_path);
        
        if (mysqli_stmt_execute($stmt)) {
            $total_added++;
            echo "  ✓ {$product['name']} (Rs. {$product['price']}) - {$vendor['shop_name']}<br>";
        } else {
            echo "  ✗ Error: " . mysqli_error($conn) . "<br>";
        }
        mysqli_stmt_close($stmt);
    }
}

echo "<h2 style='color: green;'>Total Products Added: $total_added</h2>";

// Show final summary
echo "<h3>Final Category Summary:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Category ID</th><th>Category Name</th><th>Product Count</th><th>Products</th></tr>";

$cat_query = "SELECT c.categories_id, c.categories_name, COUNT(p.product_id) as product_count 
              FROM tbl_categories c 
              LEFT JOIN tbl_products p ON c.categories_id = p.category_id 
              GROUP BY c.categories_id, c.categories_name 
              ORDER BY c.categories_id";

$cat_result = mysqli_query($conn, $cat_query);

while ($c = mysqli_fetch_assoc($cat_result)) {
    echo "<tr>";
    echo "<td>" . $c['categories_id'] . "</td>";
    echo "<td>" . $c['categories_name'] . "</td>";
    echo "<td style='text-align: center; font-weight: bold;'>" . $c['product_count'] . "</td>";
    echo "<td>";
    
    if ($c['product_count'] > 0) {
        $prod_query = "SELECT p.product_name, p.product_price, v.shop_name FROM tbl_products p 
                       LEFT JOIN tbl_vendors v ON p.vendor_id = v.vendor_id 
                       WHERE p.category_id = " . $c['categories_id'];
        $prod_result = mysqli_query($conn, $prod_query);
        
        while ($p = mysqli_fetch_assoc($prod_result)) {
            echo "• {$p['product_name']} (Rs. {$p['product_price']}) - {$p['shop_name']}<br>";
        }
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";

mysqli_close($conn);
?>
