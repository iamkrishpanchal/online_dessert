<?php
include 'session.php';
include 'connection.php';

$vendor_id = $_SESSION['vendor_id'] ?? null;
$category_id = $_GET['category_id'] ?? null;

if (!$vendor_id || !$category_id) {
    echo '<div class="bg-red-100 p-4 rounded text-red-700">Invalid request.</div>';
    exit;
}

// Fetch products for this category and vendor
$query = "SELECT product_id, product_name, product_price, product_image, stock 
          FROM tbl_products 
          WHERE category_id = ? AND vendor_id = ? 
          ORDER BY product_id DESC";

$stmt = mysqli_prepare($conn, $query);
if (!$stmt) {
    echo '<div class="bg-red-100 p-4 rounded text-red-700">Database error: ' . mysqli_error($conn) . '</div>';
    exit;
}

mysqli_stmt_bind_param($stmt, 'ii', $category_id, $vendor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    echo '<div class="bg-yellow-100 p-4 rounded text-yellow-700">No products found in this category.</div>';
    mysqli_stmt_close($stmt);
    exit;
}

// Display products table
?>
<table class="table table-striped">
    <thead>
        <tr>
            <th class="whitespace-nowrap">Sr No.</th>
            <th class="whitespace-nowrap">Product Name</th>
            <th class="whitespace-nowrap">Price</th>
            <th class="whitespace-nowrap">Image</th>
            <th class="whitespace-nowrap">Stock</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $count = 1;
        while ($row = mysqli_fetch_assoc($result)) {
        ?>
            <tr>
                <td><?php echo $count++; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td>Rs. <?php echo htmlspecialchars($row['product_price']); ?></td>
                <td>
                    <?php if (!empty($row['product_image'])) { 
                        $imgPath = '';
                        $candidates = ['uploads/' . $row['product_image'], 'vendor/uploads/' . $row['product_image'], $row['product_image']];
                        foreach ($candidates as $cand) { if (file_exists(__DIR__ . '/' . $cand)) { $imgPath = $cand; break; } }
                        if (!$imgPath) $imgPath = $candidates[0];
                    ?>
                        <img src="<?php echo htmlspecialchars($imgPath); ?>" height="80" width="100" style="object-fit: cover;">
                    <?php } else { ?>
                        <span class="text-gray-400">No Image</span>
                    <?php } ?>
                </td>
                <td><?php echo htmlspecialchars($row['stock']); ?></td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>
<?php
mysqli_stmt_close($stmt);
?>
