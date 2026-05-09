<?php
/**
 * Vendor Lock Helper Functions
 * Manages the "one shop at a time" feature
 * Users can only buy from one shop until their order is completed
 */

/**
 * Check if user has an incomplete order from a specific vendor
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $vendor_id Vendor ID to check
 * @return array|null Incomplete order details or null if none
 */
function getIncompleteOrderFromVendor($conn, $user_id, $vendor_id) {
    $stmt = mysqli_prepare($conn, 
        "SELECT order_id, order_number, order_status, vendor_id 
         FROM tbl_orders 
         WHERE user_id = ? AND vendor_id = ? 
         AND order_status NOT IN ('delivered', 'cancelled') 
         LIMIT 1"
    );
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $vendor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $order = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $order;
    }
    return null;
}

/**
 * Get ALL incomplete orders for a user (any vendor)
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return array Array of incomplete orders
 */
function getUserIncompleteOrders($conn, $user_id) {
    $stmt = mysqli_prepare($conn,
        "SELECT o.order_id, o.order_number, o.vendor_id, o.order_status, 
                v.shop_name
         FROM tbl_orders o
         LEFT JOIN tbl_vendors v ON o.vendor_id = v.vendor_id
         WHERE o.user_id = ? 
         AND o.order_status NOT IN ('delivered', 'cancelled')
         ORDER BY o.created_at DESC"
    );
    
    $orders = [];
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($order = mysqli_fetch_assoc($result)) {
            $orders[] = $order;
        }
        mysqli_stmt_close($stmt);
    }
    return $orders;
}

/**
 * Get the vendor ID locked for the user (from incomplete order or cart)
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param array $cart Current cart items (optional)
 * @return int|null Vendor ID that is locked, or null if no lock
 */
function getLockedVendorId($conn, $user_id, $cart = null) {
    // First check for incomplete orders
    $incompleteOrders = getUserIncompleteOrders($conn, $user_id);
    if (!empty($incompleteOrders)) {
        // Return the vendor ID of the first incomplete order
        return (int)$incompleteOrders[0]['vendor_id'];
    }
    
    // If no incomplete orders, check current cart
    if (is_array($cart) && !empty($cart)) {
        // Get vendor_id from first item in cart
        foreach ($cart as $item) {
            if (!empty($item['vendor_id'])) {
                return (int)$item['vendor_id'];
            }
        }
    }
    
    return null;
}

/**
 * Check if user can add product from a specific vendor
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @param int $vendor_id Vendor ID to check
 * @param array $cart Current cart items (optional)
 * @return array Result with 'allowed' bool and 'message' string
 */
function canAddProductFromVendor($conn, $user_id, $vendor_id, $cart = null) {
    $result = ['allowed' => true, 'message' => '', 'locked_vendor_id' => null, 'locked_shop_name' => null];
    
    // Get incomplete orders
    $incompleteOrders = getUserIncompleteOrders($conn, $user_id);
    
    if (!empty($incompleteOrders)) {
        // User has incomplete orders
        $lockedVendorId = (int)$incompleteOrders[0]['vendor_id'];
        $result['locked_vendor_id'] = $lockedVendorId;
        $result['locked_shop_name'] = $incompleteOrders[0]['shop_name'];
        
        if ($lockedVendorId !== (int)$vendor_id) {
            // Trying to add from different vendor
            $result['allowed'] = false;
            $result['message'] = "You have an incomplete order from '{$incompleteOrders[0]['shop_name']}' (Order: {$incompleteOrders[0]['order_number']}). Please complete that order before placing orders from other shops.";
            return $result;
        }
    } else if (is_array($cart) && !empty($cart)) {
        // No incomplete orders, but check cart
        foreach ($cart as $item) {
            if (!empty($item['vendor_id'])) {
                $cartVendorId = (int)$item['vendor_id'];
                if ($cartVendorId !== (int)$vendor_id) {
                    // Get shop name for cart vendor
                    $shopNameStmt = mysqli_prepare($conn, 
                        "SELECT shop_name FROM tbl_vendors WHERE vendor_id = ? LIMIT 1"
                    );
                    $shopName = "another shop";
                    if ($shopNameStmt) {
                        mysqli_stmt_bind_param($shopNameStmt, 'i', $cartVendorId);
                        mysqli_stmt_execute($shopNameStmt);
                        $shopResult = mysqli_stmt_get_result($shopNameStmt);
                        if ($shopRow = mysqli_fetch_assoc($shopResult)) {
                            $shopName = $shopRow['shop_name'];
                        }
                        mysqli_stmt_close($shopNameStmt);
                    }
                    
                    $result['allowed'] = false;
                    $result['locked_vendor_id'] = $cartVendorId;
                    $result['locked_shop_name'] = $shopName;
                    $result['message'] = "You have items from '{$shopName}' in your cart. You can only buy from one shop per order. Please checkout first or clear your cart to switch shops.";
                    return $result;
                }
                // Same vendor in cart, allow it
                break;
            }
        }
    }
    
    return $result;
}

/**
 * Get a message for display when user is locked to a vendor
 * @param int $vendor_id Locked vendor ID
 * @param string $shop_name Shop name
 * @param string $order_status Current order status
 * @return string Display message
 */
function getVendorLockMessage($vendor_id, $shop_name, $order_status = null) {
    $message = "🔒 <strong>Shop Lock Active</strong><br>";
    $message .= "You can only add products from <strong>'{$shop_name}'</strong> right now.";
    
    if ($order_status) {
        $statusText = ucfirst(str_replace('_', ' ', $order_status));
        $message .= "<br><small>Your order status: $statusText</small>";
    }
    
    $message .= "<br><small>Complete your order before switching shops.</small>";
    
    return $message;
}

?>
