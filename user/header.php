<?php
// if (session_status() == PHP_SESSION_NONE) {
//   session_start();
// }
include 'connection.php';

$categories = array();
if($conn) {
  // Fetch distinct category names to avoid duplicate entries (many vendors may create same category name)
  $category_query = "SELECT MIN(categories_id) AS categories_id, categories_name, MAX(categories_image) AS categories_image FROM tbl_categories WHERE categories_status = 1 GROUP BY categories_name ORDER BY categories_name ASC";
  $result = mysqli_query($conn, $category_query);
  if($result) {
    while($row = mysqli_fetch_assoc($result)) {
      $categories[] = $row;
    }
  }
}

// Fetch active (approved and online) vendors for Shop by Departments dropdown
$vendors = array();
if($conn) {
  // Determine available vendor status flags:
  $colIsOnline = @mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_vendors' AND COLUMN_NAME = 'is_online'");
  $colIsActive = @mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_vendors' AND COLUMN_NAME = 'is_active'");
  $colStatus = @mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_vendors' AND COLUMN_NAME = 'status'");

  $vendor_where = "WHERE 1=1";

  if ($colIsOnline && mysqli_num_rows($colIsOnline) > 0) {
    $vendor_where .= " AND v.is_online = 1";
  }

  if ($colIsActive && mysqli_num_rows($colIsActive) > 0) {
    $vendor_where .= " AND v.is_active = 1";
  }

  if ($colStatus && mysqli_num_rows($colStatus) > 0) {
    $vendor_where .= " AND (v.status = 'active' OR v.status = '1')";
  }

  // If no online/status column exists, this still returns all vendors (fallback behavior)
  $vendor_query = "SELECT v.vendor_id, v.shop_name, v.vendor_name, COUNT(p.product_id) AS product_count
                   FROM tbl_vendors v
                   LEFT JOIN tbl_products p ON v.vendor_id = p.vendor_id
                   " . $vendor_where . "
                   GROUP BY v.vendor_id, v.shop_name, v.vendor_name
                   ORDER BY v.shop_name ASC";

  $result = mysqli_query($conn, $vendor_query);
  if($result) {
    while($row = mysqli_fetch_assoc($result)) {
      $vendors[] = $row;
    }
  }
}

// Load logged-in customer details (for display in header dropdown)
$loggedInUser = null;
if (!empty($_SESSION['user_id']) && $conn) {
    $uid = intval($_SESSION['user_id']);
    $userRes = mysqli_query($conn, "SELECT user_name, email, phone, address FROM tbl_users WHERE user_id = $uid LIMIT 1");
    if ($userRes) {
        $loggedInUser = mysqli_fetch_assoc($userRes);
    }
}

// Fetch all products grouped by category for the header dropdown
$products_by_category = array();
if ($conn) {
  $prod_sql = "SELECT p.product_id, p.product_name, p.product_price, p.category_id, COALESCE(c.categories_name, 'Uncategorized') AS categories_name
               FROM tbl_products p
               LEFT JOIN tbl_categories c ON p.category_id = c.categories_id
               ORDER BY categories_name, p.product_name";
  $prod_res = mysqli_query($conn, $prod_sql);
  if ($prod_res) {
    while ($prow = mysqli_fetch_assoc($prod_res)) {
      $catName = $prow['categories_name'] ?: 'Uncategorized';
      if (!isset($products_by_category[$catName])) $products_by_category[$catName] = array();
      $products_by_category[$catName][] = $prow;
    }
  }
}
?>

<!-- SVG sprite definitions used throughout site -->
<svg xmlns="http://www.w3.org/2000/svg" style="display:none;">
  <defs>
    <symbol xmlns="http://www.w3.org/2000/svg" id="link" viewBox="0 0 24 24">
      <path fill="currentColor" d="M12 19a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm0-4a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm-5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm7-12h-1V2a1 1 0 0 0-2 0v1H8V2a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Zm1 17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9h16Zm0-11H4V6a1 1 0 0 1 1-1h1v1a1 1 0 0 0 2 0V5h8v1a1 1 0 0 0 2 0V5h1a1 1 0 0 1 1 1ZM7 15a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm0 4a1 1 0 1 0-1-1a1 1 0 0 0 1 1Z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="arrow-right" viewBox="0 0 24 24">
      <path fill="currentColor" d="M17.92 11.62a1 1 0 0 0-.21-.33l-5-5a1 1 0 0 0-1.42 1.42l3.3 3.29H7a1 1 0 0 0 0 2h7.59l-3.3 3.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l5-5a1 1 0 0 0 .21-.33a1 1 0 0 0 0-.76Z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="categories" viewBox="0 0 24 24">
      <path fill="currentColor" d="M19 5.5h-6.28l-.32-1a3 3 0 0 0-2.84-2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-10a3 3 0 0 0-3-3Zm1 13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1h4.56a1 1 0 0 1 .95.68l.54 1.64a1 1 0 0 0 .95.68h7a1 1 0 0 1 1 1Z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="calendar" viewBox="0 0 24 24">
      <path fill="currentColor" d="M19 4h-2V3a1 1 0 0 0-2 0v1H9V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3Zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7h16Zm0-9H4V7a1 1 0 0 1 1-1h2v1a1 1 0 0 0 2 0V6h6v1a1 1 0 0 0 2 0V6h2a1 1 0 0 1 1 1Z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="heart" viewBox="0 0 24 24">
      <path fill="currentColor" d="M20.16 4.61A6.27 6.27 0 0 0 12 4a6.27 6.27 0 0 0-8.16 9.48l7.45 7.45a1 1 0 0 0 1.42 0l7.45-7.45a6.27 6.27 0 0 0 0-8.87Zm-1.41 7.46L12 18.81l-6.75-6.74a4.28 4.28 0 0 1 3-7.3a4.25 4.25 0 0 1 3 1.25a1 1 0 0 0 1.42 0a4.27 4.27 0 0 1 6 6.05Z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="heart-solid" viewBox="0 0 24 24">
      <path fill="currentColor" d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="plus" viewBox="0 0 24 24">
      <path fill="currentColor" d="M19 11h-6V5a1 1 0 0 0-2 0v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2Z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="minus" viewBox="0 0 24 24">
      <path fill="currentColor" d="M19 11H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2Z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="cart" viewBox="0 0 24 24">
      <path fill="currentColor" d="M8.5 19a1.5 1.5 0 1 0 1.5 1.5A1.5 1.5 0 0 0 8.5 19ZM19 16H7a1 1 0 0 1 0-2h8.491a3.013 3.013 0 0 0 2.885-2.176l1.585-5.55A1 1 0 0 0 19 5H6.74a3.007 3.007 0 0 0-2.82-2H3a1 1 0 0 0 0 2h.921a1.005 1.005 0 0 1 .962.725l.155.545v.005l1.641 5.742A3 3 0 0 0 7 18h12a1 1 0 0 0 0-2Zm-1.326-9l-1.22 4.274a1.005 1.005 0 0 1-.963.726H8.754l-.255-.892L7.326 7ZM16.5 19a1.5 1.5 0 1 0 1.5 1.5a1.5 1.5 0 0 0-1.5-1.5Z"/>
    </symbol>
    <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
      <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z"/>
    </symbol>
    <!-- you can add other symbols here if needed -->
  </defs>
</svg>
<header class="site-header">
      <style>
        /* header styling */
        .site-header { width:100%; background:#fff; }

        /* logo appearance */
        .header-logo {
          max-height: 150px; /* a little bigger in user header */
          transition: transform 0.25s ease;
        }
        .main-logo:hover .header-logo {
          transform: scale(1.05);
        }

        /* search section redesign */
        .search-bar {
          background: linear-gradient(135deg, #fff7f2 0%, #fff1fc 100%);
          box-shadow: 0 8px 24px rgba(255, 109, 157, 0.18);
          padding: 0.6rem;
          border-radius: 16px;
        }

        .search-bar .form-select {
          font-size: 1.1rem;
          font-weight: 600;
          color: #253858;
          border-radius: 12px;
          border: none;
          background: rgba(255, 255, 255, 0.95) url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23605282' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") no-repeat right 0.75rem center / 1.2em 1.2em;
          box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.08);
          padding: 0.72rem 2.2rem 0.72rem 0.75rem;
        }

        .search-bar .form-control {
          font-size: 1.1rem;
          font-weight: 600;
          color: #253858;
          border-radius: 12px;
          border: none;
          background: rgba(255, 255, 255, 0.95);
          box-shadow: inset 0 1px 4px rgba(0, 0, 0, 0.08);
          padding: 0.72rem 0.75rem;
        }

        .search-bar .form-select:focus,
        .search-bar .form-control:focus {
          outline: 2px solid #605282;
          outline-offset: 2px;
          border-color: #5d4e77;
          box-shadow: 0 0 0 0.15rem rgba(255, 113, 175, 0.25);
        }

        /* nav menu fonts */
        .main-menu .nav-link {
          font-size: 1.08rem;
          letter-spacing: 0.03em;
          padding-top: 0.5rem;
          padding-bottom: 0.5rem;
          color: #2a2f6d;
          transition: color 0.2s ease;
          white-space: nowrap;
        }
        .main-menu .nav-link:hover {
          color: #d8409d;
        }

        /* Shop by Store Dropdown Styling */
        .filter-categories {
          background: linear-gradient(135deg, #fff5f8 0%, #f5f0ff 100%) !important;
          background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="none" stroke="%235d4e77" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M5 8l5 5 5-5"/></svg>') !important;
          background-repeat: no-repeat !important;
          background-position: right 8px center !important;
          background-size: 18px !important;
          border: 2px solid rgba(216, 64, 157, 0.2) !important;
          border-radius: 12px !important;
          padding: 10px 38px 10px 14px !important;
          font-weight: 600 !important;
          color: #5d4e77 !important;
          font-size: 0.95rem !important;
          box-shadow: 0 4px 12px rgba(216, 64, 157, 0.12) !important;
          transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
          cursor: pointer !important;
          appearance: none !important;
          -webkit-appearance: none !important;
          -moz-appearance: none !important;
        }

        .filter-categories:hover {
          background: linear-gradient(135deg, #ffe8f1 0%, #f0d9ff 100%) !important;
          background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="none" stroke="%235d4e77" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M5 8l5 5 5-5"/></svg>') !important;
          background-repeat: no-repeat !important;
          background-position: right 8px center !important;
          background-size: 18px !important;
          border-color: rgba(216, 64, 157, 0.4) !important;
          box-shadow: 0 8px 24px rgba(216, 64, 157, 0.25) !important;
          transform: translateY(-2px) !important;
        }

        .filter-categories:focus {
          background: linear-gradient(135deg, #ffe8f1 0%, #f0d9ff 100%) !important;
          background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="none" stroke="%235d4e77" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M5 8l5 5 5-5"/></svg>') !important;
          background-repeat: no-repeat !important;
          background-position: right 8px center !important;
          background-size: 18px !important;
          border-color: #191a57 !important;
          box-shadow: 0 0 0 3px rgba(216, 64, 157, 0.2) !important;
          outline: none !important;
        }

        .filter-categories option {
          background: #fff !important;
          color: #333 !important;
          padding: 12px 14px !important;
          border-radius: 6px !important;
          border-bottom: 1px solid #f0f0f0 !important;
          font-size: 14px !important;
          font-weight: 500 !important;
          line-height: 1.6 !important;
        }

        .filter-categories option:first-child {
          background: linear-gradient(90deg, #2563eb, #1e40af) !important;
          color: #fff !important;
          font-weight: 700 !important;
          padding: 14px 14px !important;
        }

        .filter-categories option:hover {
          background: linear-gradient(135deg, #dbeafe 0%, #ede9fe 100%) !important;
          color: #1f2937 !important;
        }

        /* Header Icons Styling */
        .header-icon-btn {
          background: transparent;
          border: none;
          box-shadow: none;
          transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
          color: #d1bdc9;
        }
        .header-icon-btn:hover {
          background: transparent;
          box-shadow: none;
          transform: translateY(-2px) scale(1.12);
          border-color: transparent;
        }
        .header-icon-btn:active {
          transform: translateY(0) scale(1.02);
        }

        .header-icon-btn svg {
          transition: transform 0.3s ease, filter 0.3s ease;
          filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }
        .header-icon-btn:hover svg {
          transform: scale(1.2);
          filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
        }

        /* Profile Icon - Blue-Purple theme */
        .header-icon-btn.profile-icon {
          color: #5d4e77;
        }
        .header-icon-btn.profile-icon:hover {
          background: transparent;
          box-shadow: none;
          color: #7a6b97;
        }

        /* Notification Icon - Pink-Red theme */
        .header-icon-btn.notif-icon {
          color: #e63946;
        }
        .header-icon-btn.notif-icon:hover {
          background: transparent;
          box-shadow: none;
          color: #f44747;
        }

        /* Notification Dropdown Styling */
        #notif-list {
          background: linear-gradient(135deg, #fff9fa 0%, #f5f0ff 100%);
          border: 1px solid rgba(230, 57, 70, 0.2);
          border-radius: 12px;
          box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }

        #notif-list .dropdown-header {
          background: linear-gradient(90deg, #e63946, #f44747);
          color: #fff;
          font-weight: 700;
          border-radius: 12px 12px 0 0;
          padding: 12px 16px;
          border: none;
        }

        #notif-list .dropdown-divider {
          margin: 0;
          border-color: rgba(230, 57, 70, 0.15);
        }

        #notif-list .dropdown-item {
          padding: 14px 16px;
          border-bottom: 1px solid rgba(230, 57, 70, 0.1);
          transition: all 0.2s ease;
          color: #333;
          line-height: 1.4;
          position: relative;
        }



        #notif-list .dropdown-item:last-of-type {
          border-bottom: none;
          border-radius: 0 0 12px 12px;
        }

        #notif-list .dropdown-item:hover {
          background-color: rgba(230, 57, 70, 0.08);
          transform: translateX(4px);
          box-shadow: inset 4px 0 0 #e63946;
        }

        #notif-list .dropdown-item.fw-bold {
          background-color: rgba(230, 57, 70, 0.15);
          font-weight: 600;
        }

        #notif-list .dropdown-item small.text-muted {
          color: #999 !important;
          font-size: 11px;
          display: block;
          margin-bottom: 4px;
        }

        #notif-list .dropdown-item > div:nth-child(2) {
          font-weight: 600;
          color: #1a1a2e;
          margin-bottom: 4px;
          font-size: 14px;
        }

        #notif-list .dropdown-item > div:nth-child(3) {
          color: #555;
          font-size: 12px;
          line-height: 1.3;
        }

        #notif-list .text-center {
          padding: 20px !important;
          color: #999 !important;
        }

        #clear-all-notif-btn {
          width: 100%;
          padding: 10px 16px !important;
          background: linear-gradient(135deg, #e63946, #f44747);
          color: white;
          border: none;
          border-radius: 0 0 12px 12px;
          font-weight: 600;
          font-size: 13px;
          cursor: pointer;
          transition: all 0.3s ease;
          margin-top: 8px;
        }

        #clear-all-notif-btn:hover {
          background: linear-gradient(135deg, #d6333c, #e63946);
          box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
          transform: translateY(-2px);
        }

        #clear-all-notif-btn:active {
          transform: translateY(0);
        }

        /* Wishlist Icon - Rose-Pink theme */
        .header-icon-btn.wishlist-icon {
          color: #d8409d;
        }
        .header-icon-btn.wishlist-icon:hover {
          background: transparent;
          box-shadow: none;
          color: #e85fa8;
        }

        /* Cart Button Styling */
        .cart button {
          padding: 12px 20px !important;
          border-radius: 12px !important;
          background: linear-gradient(135deg, #fff0e6 0%, #ffe8d6 100%) !important;
          border: 2px solid rgba(255, 109, 157, 0.2) !important;
          transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
          box-shadow: 0 4px 12px rgba(255, 109, 157, 0.15) !important;
          cursor: pointer !important;
        }
        .cart button:hover {
          background: linear-gradient(135deg, #ffd9b3 0%, #ffcca0 100%) !important;
          box-shadow: 0 8px 24px rgba(255, 109, 157, 0.3) !important;
          transform: translateY(-3px) scale(1.05) !important;
          border-color: rgba(255, 109, 157, 0.4) !important;
        }
        .cart button:active {
          transform: translateY(-1px) scale(1.02) !important;
        }

        .cart .dropdown-toggle::after {
          display: none;
        }

        .cart-label {
          font-size: 0.95rem;
          font-weight: 600;
          color: #7a6b97;
          letter-spacing: 0.3px;
          transition: color 0.3s ease;
        }
        .cart button:hover .cart-label {
          color: #5d4e77;
        }

        .cart-total {
          font-size: 1.5rem !important;
          background: linear-gradient(135deg, #d8409d 0%, #ff6b9d 100%);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          font-weight: 800 !important;
          letter-spacing: -0.5px;
        }

        /* Tagline Styling */
        .header-tagline {
          font-family: 'Poppins', 'Segoe UI', 'Roboto', sans-serif;
          font-size: 0.95rem;
          font-weight: 700;
          background: linear-gradient(135deg, #d8409d 0%, #5d4e77 50%, #e63946 100%);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          letter-spacing: 0.8px;
          text-align: center;
          font-style: italic;
          margin-top: 6px;
          padding: 8px 12px;
          border-radius: 8px;
          background-color: rgba(216, 64, 157, 0.08);
          backdrop-filter: blur(10px);
          box-shadow: 0 4px 15px rgba(216, 64, 157, 0.15), inset 0 1px 2px rgba(255, 255, 255, 0.5);
          transition: all 0.3s ease;
          position: relative;
        }

        .header-tagline:hover {
          box-shadow: 0 8px 25px rgba(216, 64, 157, 0.25), inset 0 1px 2px rgba(255, 255, 255, 0.8);
          transform: translateY(-2px);
          background-color: rgba(216, 64, 157, 0.12);
        }

        @media (max-width: 991px) {
          .main-menu .nav-link {
            font-size: 1rem;
          }
          .header-tagline {
            font-size: 0.85rem;
            padding: 6px 10px;
            margin-top: 4px;
          }
        }
      </style>
      <div class="container-fluid">
        <div class="row py-1 border-bottom align-items-center">
          
          <div class="col-sm-4 col-lg-3 text-center text-sm-start">
            <div class="main-logo" style="margin-left: 40px;">
  <?php
    // Use the new Dessert Magic logo file provided for this project.
    // Place the image at `user/images/dessert-magic.png` and this will display.
    // Fallback to existing known logo files if this one is not yet present.
    $preferred_logo = 'images/logo4.png';
    $candidates = [$preferred_logo, 'images/logo4.png', 'images/logo4.png', 'images/logo4.png', 'images/logo4.png'];
    $logo_path = '';
    foreach ($candidates as $cand) {
        if (file_exists(__DIR__ . '/' . $cand)) {
            $logo_path = $cand;
            break;
        }
    }
    // if nothing found, leave blank to avoid broken image
    if (!$logo_path) {
        $logo_path = '';
    }
  ?>
  <a href="index.php">
<?php if ($logo_path): ?>
                <img src="<?php echo $logo_path; ?>" alt="Dessert Magic" class="img-fluid header-logo">
<?php endif; ?>
              </a>
            </div>
            <!-- <div class="header-tagline">✨ Taste the Magic of Desserts ✨</div> -->
          </div>
          
          <div class="col-sm-6 offset-sm-2 offset-md-0 col-lg-5 d-none d-lg-block">
            <div class="search-bar row bg-light p-2 my-0 rounded-4">
              <div class="col-md-4 d-none d-md-block">
                <select id="product_select" class="form-select border-0 bg-transparent" onchange="(function(el){ if(!el.value) return; var sel = el.options[el.selectedIndex]; var target = sel.getAttribute('data-target') || 'category'; var vendor = (document.getElementById('vendor_select')||{}).value || ''; var url = (target === 'product') ? 'viewProduct.php?id='+el.value : 'viewCategory.php?id='+el.value; if(vendor) url += '&vendor_id='+vendor; window.location.href = url; })(this)">
                  <option value="">All Categories</option>
                  <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat['categories_id']; ?>" data-target="category"><?php echo htmlspecialchars($cat['categories_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-11 col-md-7">
                <form id="search-form" class="text-center" onsubmit="if(document.getElementById('header_search').value.trim()){ window.location.href='search_results.php?q='+encodeURIComponent(document.getElementById('header_search').value.trim()); } return false;">
                  <div style="position:relative;">
                    <input id="header_search" name="q" type="search" class="form-control border-0 bg-transparent" placeholder="Search Products" autocomplete="off" />
                    <div id="search_suggestions" class="list-group" style="position:absolute;top:100%;left:0;right:0;z-index:1050;display:none;margin-top:6px;border-radius:6px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);"></div>
                  </div>
                </form>
              </div>
              <div class="col-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M21.71 20.29L18 16.61A9 9 0 1 0 16.61 18l3.68 3.68a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.39ZM11 18a7 7 0 1 1 7-7a7 7 0 0 1-7 7Z"/></svg>
              </div>
            </div>
          </div>
          
          <div class="col-sm-8 col-lg-4 d-flex justify-content-end gap-5 align-items-center justify-content-center justify-content-sm-end" style="margin-right: -100px;">

            <ul class="d-flex justify-content-end list-unstyled m-0">
              <li class="dropdown">
                <a href="#" class="rounded-circle header-icon-btn profile-icon p-2 mx-1 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                  <svg width="32" height="32" viewBox="0 0 24 24"><use xlink:href="#user"></use></svg>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <?php if (!empty($_SESSION['user_id'])): ?>
                    <li class="dropdown-header text-wrap">
                      <strong><?php echo htmlspecialchars($loggedInUser['user_name'] ?? $_SESSION['user_name'] ?? 'Customer'); ?></strong><br>
                      <small><?php echo htmlspecialchars($loggedInUser['email'] ?? $_SESSION['user_email'] ?? ''); ?></small><br>
                      <?php if (!empty($loggedInUser['phone'])): ?>
                        <small><?php echo htmlspecialchars($loggedInUser['phone']); ?></small><br>
                      <?php endif; ?>
                      <?php if (!empty($loggedInUser['address'])): ?>
                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($loggedInUser['address'])); ?></small>
                      <?php endif; ?>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                    <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
                    <li><a class="dropdown-item" href="cart.php">View Cart</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                  <?php else: ?>
                    <li><a class="dropdown-item" href="login.php">User Login / Register</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="dropdown-item-text px-3 text-wrap text-muted" style="font-size:0.95rem;">
                      Want to become a Vendor? <a href="../admin/vendor/register.php" class="text-primary">Click here</a>.
                    </li>

                    <!-- <li><a class="dropdown-item" href="../rider/login.php">Rider - Login</a></li> -->
                  <?php endif; ?>
                </ul>
              </li>
              <!-- Notification icon -->
              <li class="dropdown" id="notif-dropdown">
                <a href="#" class="rounded-circle header-icon-btn notif-icon p-2 mx-1 position-relative dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                  <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 24c1.104 0 2-.896 2-2h-4c0 1.104.896 2 2 2zm6.002-6v-5c0-3.07-1.633-5.64-4.581-6.32V6c0-.828-.672-1.5-1.5-1.5S10.5 5.172 10.5 6v.68C7.552 7.36 5.92 9.93 5.92 13v5l-1.92 2v1h16v-1l-1.918-2z"/></svg>
                  <span id="notif-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="min-width:16px;height:16px;display:none;">0</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" id="notif-list" style="width:300px;max-height:400px;overflow:auto;padding-bottom:0;">
                  <li class="dropdown-header">Notifications</li>
                  <li><hr class="dropdown-divider"></li>
                  <li class="text-center"><small>No notifications</small></li>
                </ul>
                <button id="clear-all-notif-btn" style="display:none;">Clear All</button>
              </li>
              <li>
                <a href="#" class="rounded-circle header-icon-btn wishlist-icon p-2 mx-1 position-relative" id="wishlist-btn" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWishlist" aria-controls="#offcanvasWishlist">
                  <svg width="32" height="32" viewBox="0 0 24 24"><use xlink:href="#heart"></use></svg>
                  <span id="wishlist-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="min-width:16px;height:16px;display:none;">0</span>
                </a>
              </li>
              <li class="d-lg-none">
                <a href="#" class="rounded-circle bg-light p-2 mx-1" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-controls="offcanvasCart">
                  <svg width="24" height="24" viewBox="0 0 24 24"><use xlink:href="#cart"></use></svg>
                </a>
              </li>
              <li class="d-lg-none">
                <a href="#" class="rounded-circle bg-light p-2 mx-1" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSearch" aria-controls="offcanvasSearch">
                  <svg width="24" height="24" viewBox="0 0 24 24"><use xlink:href="#search"></use></svg>
                </a>
              </li>
            </ul>

            <div class="cart text-end d-none d-lg-block dropdown">
                <?php
                  $GST_RATE = 0.05; // 5% GST
                  $cart = $_SESSION['cart'] ?? array();
                  $total = 0.0;
                  $count = 0;
                  foreach($cart as $ci){ 
                    $count += intval($ci['quantity'] ?? 1); 
                    $p = isset($ci['price']) ? floatval(preg_replace('/[^0-9\.\-]/','', (string)$ci['price'])) : 0.0; 
                    $qty = intval($ci['quantity'] ?? 1);
                    $item_total = $p * $qty;
                    $item_gst = $item_total * $GST_RATE;
                    $total += $item_total + $item_gst;
                  }
                ?>
              <button class="border-0 d-flex flex-column gap-2 lh-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-controls="offcanvasCart">
                <span class="cart-label">🛒 Your Cart</span>
                <span class="cart-total">₹<?php echo number_format($total,2); ?></span>
              </button>
            </div>
          </div>

        </div>
      </div>
      <div class="container-fluid">
        <div class="row py-1">
          <div class="d-flex  justify-content-center justify-content-sm-between align-items-center">
            <nav class="main-menu d-flex navbar navbar-expand-lg">

              <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
                aria-controls="offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
              </button>

              <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">

                <div class="offcanvas-header justify-content-center">
                  <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                </div>

                <div class="offcanvas-body">
              
                  <?php $selected_vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0; ?>
                  <div style="margin-bottom: 8px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">
                      <!-- <svg style="width: 18px; height: 18px; display: inline-block; margin-right: 6px; vertical-align: middle;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg> -->
                      <h6 class="fw-bold">🛒 Shop by Store</h6>
                    </label>
                    <select id="vendor_select" class="filter-categories border-0 mb-0 me-5" onchange="if(this.value) window.location.href='all_vendor_products.php?vendor_id='+this.value; else window.location.href='vendor_products_list.php';">
                      <option value="">Browse All Shops</option>
                      <?php foreach($vendors as $vendor): ?>
                      <option value="<?php echo $vendor['vendor_id']; ?>" <?php echo ($selected_vendor_id && $selected_vendor_id == $vendor['vendor_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($vendor['shop_name'] ?: $vendor['vendor_name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
              
                  <ul class="navbar-nav justify-content-end menu-list list-unstyled d-flex gap-md-3 mb-0">
                   
                    
                   
                    
                    <li class="nav-item">
                      <a class="nav-link fw-bold" href="index.php">Home</a>
                    </li>
                     <li class="nav-item">
                      <a class="nav-link fw-bold" href="all_products.php">Products</a>
                    </li>
                     <li class="nav-item">
                      <a class="nav-link fw-bold" href="about.php">About Us</a>
                    </li>
                     <li class="nav-item">
                      <a class="nav-link fw-bold" href="contact.php">Contact Us</a>
                    </li>
                     <?php if (!empty($_SESSION['user_id'])): ?>
                    
                    <li class="nav-item">
                      <a class="nav-link fw-bold" href="orders.php">My Orders</a>
                    </li>
                    <?php endif; ?>

                     <li class="nav-item">
                      <a class="nav-link fw-bold" href=""></a>
                    </li>
                  
                    <!-- <?php if (!empty($_SESSION['user_id'])): ?>
                    
                    <li class="nav-item">
                      <a class="nav-link fw-bold" href="orders.php">My Orders</a>
                    </li>
                    <?php endif; ?> -->
                                <div class="header-tagline">At Dessert Magic, We Turn Everyday Cravings into Extraordinary Experiences with Freshly Made Cakes, Waffles, and Shakes.</div>
                    <!-- <li class="nav-item dropdown">
                      <a class="nav-link dropdown-toggle" role="button" id="pages" data-bs-toggle="dropdown" aria-expanded="false">Pages</a>
                      <ul class="dropdown-menu" aria-labelledby="pages">
                        <li><a href="vendor_products_list.php" class="dropdown-item">All Vendors</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a href="index.html" class="dropdown-item">About Us </a></li>
                        <li><a href="index.html" class="dropdown-item">Shop </a></li>
                        <li><a href="index.html" class="dropdown-item">Single Product </a></li>
                        <li><a href="index.html" class="dropdown-item">Cart </a></li>
                        <li><a href="index.html" class="dropdown-item">Checkout </a></li>
                        <li><a href="index.html" class="dropdown-item">Blog </a></li>
                        <li><a href="index.html" class="dropdown-item">Single Post </a></li>
                        <li><a href="index.html" class="dropdown-item">Styles </a></li>
                        <li><a href="index.html" class="dropdown-item">Contact </a></li>
                        <li><a href="index.html" class="dropdown-item">Thank You </a></li>
                        <li><a href="index.html" class="dropdown-item">My Account </a></li>
                        <li><a href="index.html" class="dropdown-item">404 Error </a></li>
                      </ul>
                    </li> -->
                    
                    <!-- <li class="nav-item">
                      <a href="#sale" class="nav-link">Sale</a>
                    </li> -->
                   
                  </ul>
                
                </div>

              </div>
          </div>
        </div>
      </div>
    <script>
    (function(){
      var input = document.getElementById('header_search');
      var sugg = document.getElementById('search_suggestions');
      if(!input || !sugg) return;
      var timer = null;
      function clearS(){ sugg.innerHTML=''; sugg.style.display='none'; }
      function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
      function render(items){
        if(!items || items.length===0){ clearS(); return; }
        sugg.innerHTML = items.map(function(it){
          var img = it.product_image && it.product_image.length ? it.product_image : 'images/default-product.png';
          return (`<a href="viewProduct.php?id=${encodeURIComponent(it.product_id)}" class="list-group-item list-group-item-action d-flex align-items-center" style="padding:8px 12px;">
                    <img src="${img}" style="width:44px;height:44px;object-fit:cover;margin-right:10px;border-radius:6px;"> 
                    <div style="flex:1;min-width:0;"><div style="font-size:14px;font-weight:600;">${escapeHtml(it.product_name)}</div><small class="text-muted">${escapeHtml(it.category||'')}</small></div>
                  </a>`);
        }).join('');
        sugg.style.display = 'block';
      }
      input.addEventListener('input', function(){
        var q = this.value.trim();
        if(timer) clearTimeout(timer);
        if(!q){ clearS(); return; }
        timer = setTimeout(function(){
          fetch('search_products.php?q='+encodeURIComponent(q)).then(function(r){ return r.json(); }).then(function(data){ render(data); }).catch(function(e){ console.error(e); clearS(); });
        }, 250);
      });
      document.addEventListener('click', function(e){ if(!sugg.contains(e.target) && e.target !== input){ clearS(); } });
    })();
    </script>
    <!-- Wishlist Offcanvas Modal -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWishlist" aria-labelledby="offcanvasWishlistLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasWishlistLabel">My Wishlist</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body p-0">
        <div id="wishlist-items" style="padding: 15px;"></div>
      </div>
    </div>
    <!-- Wishlist JavaScript -->
    <script>
    (function(){
      // Wishlist functions
      window.WishlistManager = {
        storageKey: 'foodmart_wishlist',
        
        getWishlist: function() {
          try {
            var data = localStorage.getItem(this.storageKey);
            return data ? JSON.parse(data) : [];
          } catch (e) {
            return [];
          }
        },
        
        saveWishlist: function(items) {
          localStorage.setItem(this.storageKey, JSON.stringify(items));
        },
        
        addToWishlist: function(productId, productName, productPrice, productImage) {
          var wishlist = this.getWishlist();
          var exists = wishlist.find(function(item) { return item.id == productId; });
          if (!exists) {
            wishlist.push({
              id: productId,
              name: productName,
              price: productPrice,
              image: productImage
            });
            this.saveWishlist(wishlist);
            this.updateDisplay();
            return true;
          }
          return false;
        },
        
        removeFromWishlist: function(productId) {
          var wishlist = this.getWishlist();
          wishlist = wishlist.filter(function(item) { return item.id != productId; });
          this.saveWishlist(wishlist);
          this.updateDisplay();
        },
        
        isInWishlist: function(productId) {
          var wishlist = this.getWishlist();
          return wishlist.some(function(item) { return item.id == productId; });
        },
        
        updateDisplay: function() {
          var wishlist = this.getWishlist();
          var countBadge = document.getElementById('wishlist-count');
          var itemsDiv = document.getElementById('wishlist-items');
          
          if (countBadge) {
            if (wishlist.length > 0) {
              countBadge.textContent = wishlist.length;
              countBadge.style.display = 'block';
            } else {
              countBadge.style.display = 'none';
            }
          }
          
          if (itemsDiv) {
            if (wishlist.length === 0) {
              itemsDiv.innerHTML = '<div style="text-align: center; padding: 30px 15px; color: #999;"><p>Your wishlist is empty</p></div>';
            } else {
              itemsDiv.innerHTML = wishlist.map(function(item) {
                var imgSrc = item.image || 'images/default-product.png';
                // build form for quantity and add to cart
                var form =
                  '<form method="post" action="add_to_cart.php" style="display:flex;align-items:center;gap:8px;margin-top:8px;" data-wishlist-id="' + item.id + '">' +
                    '<input type="hidden" name="product_id" value="' + escapeHtml(item.id) + '">' +
                    '<input type="hidden" name="name" value="' + escapeHtml(item.name) + '">' +
                    '<input type="hidden" name="price" value="' + escapeHtml(item.price) + '">' +
                    '<input type="hidden" name="image" value="' + escapeHtml(item.image) + '">' +
                    '<div class="product-qty" style="display:flex;align-items:center;">' +
                      '<button type="button" class="quantity-left-minus btn btn-sm btn-outline-secondary" data-type="minus">-</button>' +
                      '<input type="text" name="quantity" value="1" class="form-control input-number text-center" style="width:40px;" />' +
                      '<button type="button" class="quantity-right-plus btn btn-sm btn-outline-secondary" data-type="plus">+</button>' +
                    '</div>' +
                    '<button type="submit" class="btn btn-sm btn-primary" style="padding:4px 8px;">Add to Cart</button>' +
                  '</form>';

                return '<div style="display: flex; gap: 12px; padding: 12px; border-bottom: 1px solid #eee; align-items: flex-start;">' +
                  '<img src="' + escapeHtml(imgSrc) + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;" alt="' + escapeHtml(item.name) + '">' +
                  '<div style="flex: 1; min-width: 0;">' +
                  '<a href="viewProduct.php?id=' + encodeURIComponent(item.id) + '" style="color: #333; text-decoration: none; display: block; font-weight: 500; margin-bottom: 4px;">' + escapeHtml(item.name) + '</a>' +
                  '<div style="color: #666; font-size: 13px; margin-bottom: 8px;">₹' + escapeHtml(item.price) + '</div>' +
                  form +
                  '<button onclick="WishlistManager.removeFromWishlist(' + item.id + ')" style="background: #ff6b6b; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 12px; cursor: pointer; margin-top:8px;">Remove</button>' +
                  '</div>' +
                  '</div>';
              }).join('');

              // after injecting markup, attach qty handlers and form submit interception
              setTimeout(function(){
                // use simple DOM methods in case jQuery isn't available yet
                itemsDiv.querySelectorAll('.product-qty').forEach(function(el){
                  var input = el.querySelector('input[name="quantity"]');
                  var plus = el.querySelector('.quantity-right-plus');
                  var minus = el.querySelector('.quantity-left-minus');
                  if(plus){
                    plus.addEventListener('click', function(e){ e.preventDefault(); var v=parseInt(input.value)||0; input.value=v+1; });
                  }
                  if(minus){
                    minus.addEventListener('click', function(e){ e.preventDefault(); var v=parseInt(input.value)||0; if(v>0) input.value=v-1; });
                  }
                });

                // intercept wishlist add-to-cart forms to post then go to cart
                itemsDiv.querySelectorAll('form[data-wishlist-id]').forEach(function(f){
                  f.addEventListener('submit', function(e){
                    e.preventDefault();
                    var wid = this.getAttribute('data-wishlist-id');
                    var formData = new FormData(this);
                    // send to server
                    fetch(this.action, {method:'POST', body: formData})
                      .then(function(resp){
                        // remove item from wishlist immediately
                        if(wid) WishlistManager.removeFromWishlist(wid);
                        // navigate to cart page once request completes
                        window.location.href = 'cart.php';
                      }).catch(function(){
                        // fallback: navigate anyway
                        window.location.href = 'cart.php';
                      });
                  });
                });
              },0);
            }
          }
          
          // Update product card buttons
          document.querySelectorAll('.btn-wishlist').forEach(function(btn) {
            var productId = btn.getAttribute('data-product-id');
            if (productId && WishlistManager.isInWishlist(parseInt(productId))) {
              btn.classList.add('active');
              btn.style.color = '#ff6b6b';
            } else {
              btn.classList.remove('active');
              btn.style.color = 'inherit';
            }
          });
        }
      };
      
      function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
      }
      
      // Initialize on page load
      window.addEventListener('DOMContentLoaded', function() {
        WishlistManager.updateDisplay();
      });
    })();
    </script>

    <!-- notifications script -->
    <script>
    (function(){
      // Store notification timers globally
      window.notificationTimers = window.notificationTimers || {};
      
      function updateUnread(){
        fetch('get_unread_count.php')
          .then(r=>r.json())
          .then(d=>{
            var c = document.getElementById('notif-count');
            if(!c) return;
            if(d.success && d.unread>0){
              c.textContent=d.unread;
              c.style.display='block';
            } else {
              c.style.display='none';
            }
          }).catch(function(){});
      }
      
      function setupAutoDismiss(notifId, timeRemaining) {
        if (!timeRemaining || timeRemaining <= 0) return;
        
        // Clear any existing timer for this notification
        if (window.notificationTimers[notifId]) {
          clearTimeout(window.notificationTimers[notifId]);
        }
        
        // Set up auto-dismiss timer - remove notification after timeRemaining seconds
        window.notificationTimers[notifId] = setTimeout(function() {
          var liElement = document.querySelector('[data-notif-id="' + notifId + '"]');
          if (liElement) {
            liElement.style.opacity = '0.5';
            setTimeout(function() {
              liElement.remove();
              updateUnread();
            }, 200);
          }
          delete window.notificationTimers[notifId];
        }, timeRemaining * 1000);
      }
      
      
      function clearAllNotifications() {
        console.log('[NOTIF] Clearing all notifications...');
        fetch('clear_all_notifications.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).then(function(r){ return r.json(); })
          .then(function(data){
            console.log('[NOTIF] Clear all response:', data);
            if(data.success){
              console.log('[NOTIF] All notifications cleared');
              // Remove all notification items
              var notifItems = document.querySelectorAll('[data-notif-id]');
              notifItems.forEach(function(item){
                item.style.opacity = '0.5';
                setTimeout(function(){
                  item.remove();
                }, 200);
              });
              // Show no notifications message
              setTimeout(function(){
                var list = document.getElementById('notif-list');
                if(list && list.querySelectorAll('[data-notif-id]').length === 0){
                  var noNotif = list.querySelector('.text-center');
                  if(!noNotif){
                    var li = document.createElement('li');
                    li.className = 'text-center';
                    li.innerHTML = '<small>No notifications</small>';
                    list.appendChild(li);
                  }
                }
                updateUnread();
              }, 250);
            }
          }).catch(function(err){
            console.error('[NOTIF] Error clearing notifications:', err);
          });
      }
      
      function loadNotifications(){
        console.log('[NOTIF] Loading notifications...');
        fetch('fetch_notifications.php')
          .then(r=>r.json())
          .then(d=>{
            console.log('[NOTIF] Notifications response:', d);
            var list = document.getElementById('notif-list');
            if(!list) return;
            if(d.success){
              var html = '';
              if(d.notifications.length){
                console.log('[NOTIF] Found ' + d.notifications.length + ' notification(s)');
                d.notifications.forEach(function(n){
                  var cls = n.status === 'unread' ? 'fw-bold bg-light' : '';
                  var notifStyle = '';
                  
                  // Different styling for cancelled and completed notifications
                  if (n.title.indexOf('Cancel') !== -1 || n.title.indexOf('✗') !== -1) {
                    notifStyle = 'style="border-left: 4px solid #e63946; background: rgba(230, 57, 70, 0.05);"';
                  } else if (n.title.indexOf('Delivered') !== -1 || n.title.indexOf('✓') !== -1) {
                    notifStyle = 'style="border-left: 4px solid #27ae60; background: rgba(39, 174, 96, 0.05);"';
                  }
                  
                  html += '<li ' + notifStyle + ' data-notif-id="' + n.notification_id + '">' +
                          '<a href="javascript:void(0)" class="dropdown-item ' + cls + '" data-id="' + n.notification_id + '">' +
                          '<small class="text-muted">' + n.created_at + '</small><br/>' +
                          '<div style="font-weight:600;color:#1a1a2e;margin:6px 0;">' + n.title + '</div>' +
                          '<div style="color:#555;font-size:12px;line-height:1.3;">' + n.message + '</div>' +
                          '</a></li>';
                  
                  // Setup auto-dismiss if available
                  if (n.time_remaining && n.time_remaining > 0) {
                    setTimeout(function() {
                      setupAutoDismiss(n.notification_id, n.time_remaining);
                    }, 100);
                  }
                });
              } else {
                console.log('[NOTIF] No notifications found');
                html = '<li class="text-center"><small>No notifications</small></li>';
              }
              list.innerHTML = '<li class="dropdown-header">Notifications</li><li><hr class="dropdown-divider"></li>' + html;
              
              // Show/hide clear all button
              var clearBtn = document.getElementById('clear-all-notif-btn');
              if(clearBtn){
                if(d.notifications.length > 0){
                  clearBtn.style.display = 'block';
                  clearBtn.onclick = clearAllNotifications;
                } else {
                  clearBtn.style.display = 'none';
                }
              }
            }
          }).catch(function(){});
      }
      
      document.addEventListener('DOMContentLoaded', function(){
        updateUnread();
        setInterval(updateUnread, 30000);
        var dropdown = document.getElementById('notif-dropdown');
        if(dropdown){
          dropdown.addEventListener('shown.bs.dropdown', loadNotifications);
          // Refresh notifications every 10 seconds when dropdown is open
          dropdown.addEventListener('shown.bs.dropdown', function() {
            var refreshTimer = setInterval(function() {
              if (!dropdown.classList.contains('show')) {
                clearInterval(refreshTimer);
              } else {
                loadNotifications();
              }
            }, 10000);
          });
        }
      });
    })();
    </script>
    </header>


