<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    // Include without closing tags causing issues
    $root = __DIR__ . '/../../';
    require_once $root . 'session.php';
    
    if (!isset($_SESSION['vendor_id'])) {
        throw new Exception('Not authorized - vendor_id not set in session');
    }

    // Database connection
    $conn = @mysqli_connect("localhost", "root", "", "online_dessert");
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get POST data
    $action = $_POST['action'] ?? null;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $new_quantity = isset($_POST['new_quantity']) ? intval($_POST['new_quantity']) : 0;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $vendor_id = intval($_SESSION['vendor_id']);

    if ($action !== 'update_stock') {
        throw new Exception('Invalid action: ' . $action);
    }

    if ($product_id <= 0) {
        throw new Exception('Invalid product ID: ' . $product_id);
    }

    if ($new_quantity < 0) {
        throw new Exception('Invalid quantity: ' . $new_quantity);
    }

    // Escape notes
    $notes = mysqli_real_escape_string($conn, $notes);

    // Verify product exists and belongs to vendor
    $check_sql = "SELECT stock FROM tbl_products WHERE product_id = " . $product_id . " AND vendor_id = " . $vendor_id;
    $check_result = mysqli_query($conn, $check_sql);
    
    if (!$check_result) {
        throw new Exception('Query error: ' . mysqli_error($conn));
    }

    if (mysqli_num_rows($check_result) === 0) {
        throw new Exception('Product not found or you do not have permission');
    }

    $row = mysqli_fetch_assoc($check_result);
    $previous_quantity = intval($row['stock']);
    $quantity_added = $new_quantity - $previous_quantity;

    // Try to record history (optional - continue if it fails)
    $history_sql = "INSERT INTO tbl_stock_management 
                    (product_id, previous_quantity, quantity_added, new_quantity, stock_date, notes, created_by)
                    VALUES (" . $product_id . ", " . $previous_quantity . ", " . $quantity_added . ", " . $new_quantity . ", NOW(), '" . $notes . "', " . $vendor_id . ")";
    @mysqli_query($conn, $history_sql);

    // Update product stock
    $update_sql = "UPDATE tbl_products SET stock = " . $new_quantity . " WHERE product_id = " . $product_id . " AND vendor_id = " . $vendor_id;
    
    if (!mysqli_query($conn, $update_sql)) {
        throw new Exception('Update failed: ' . mysqli_error($conn));
    }

    mysqli_close($conn);

    // Clear output buffer and send clean JSON
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Stock updated successfully',
        'data' => [
            'previous_quantity' => $previous_quantity,
            'new_quantity' => $new_quantity,
            'quantity_added' => $quantity_added
        ]
    ]);
    exit;

} catch (Exception $e) {
    @mysqli_close($conn);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}

