<?php
// include 'session.php';
include 'connection.php';

if (isset($_POST['register'])) {
    $errors = [];

    $vendor_name = trim($_POST['vendor_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $shop_name = trim($_POST['shop_name'] ?? '');
    $image_path = '';
    $logo_path = '';

    // Basic validations
    if ($vendor_name === '') {
        $errors[] = 'Vendor name is required.';
    }
    if (strlen($vendor_name) < 2) {
        $errors[] = 'Vendor name must be at least 2 characters.';
    }
    if (strlen($vendor_name) > 255) {
        $errors[] = 'Vendor name must not exceed 255 characters.';
    }
    if (!preg_match('/^[\p{L}&\'.,\-\s]+$/u', $vendor_name)) {
        $errors[] = "Vendor name may only contain letters, spaces, and these symbols: & ' . , -";
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (strlen($password_raw) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    // Validate password strength (at least one letter and one number)
    if (!preg_match('/[a-zA-Z]/', $password_raw) || !preg_match('/[0-9]/', $password_raw)) {
        $errors[] = 'Password must contain at least one letter and one number.';
    }
    // Allow digits, spaces, +, -, () in phone and require 7-15 chars when digits counted
    $phone_digits = preg_replace('/\D+/', '', $phone);
    if ($phone !== '' && (strlen($phone_digits) < 7 || strlen($phone_digits) > 15)) {
        $errors[] = 'Phone number looks invalid.';
    }
    if ($address === '') {
        $errors[] = 'Address is required.';
    }
    if (strlen($address) < 5) {
        $errors[] = 'Address must be at least 5 characters.';
    }
    if (strlen($address) > 500) {
        $errors[] = 'Address must not exceed 500 characters.';
    }
    if ($shop_name === '') {
        $errors[] = 'Shop name is required.';
    }
    if (strlen($shop_name) < 2) {
        $errors[] = 'Shop name must be at least 2 characters.';
    }
    if (strlen($shop_name) > 255) {
        $errors[] = 'Shop name must not exceed 255 characters.';
    }
    // Check if image file is provided (required)
    if (!isset($_FILES['vendor_image']) || $_FILES['vendor_image']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Vendor image is required.';
    }

    // Create vendors table if it doesn't exist
    $createTableSql = "CREATE TABLE IF NOT EXISTS tbl_vendors (
        vendor_id INT AUTO_INCREMENT PRIMARY KEY,
        vendor_name VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        shop_name VARCHAR(255) NOT NULL,
        image_path VARCHAR(255) DEFAULT NULL,
        logo_path VARCHAR(255) DEFAULT NULL,
        status ENUM('pending','active','inactive','suspended') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $createTableSql);
    // Ensure `shop_name` and `image_path` columns exist (for older installs)
    $col = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'shop_name'");
    if (!$col || mysqli_num_rows($col) == 0) {
        mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN shop_name VARCHAR(255) NOT NULL AFTER address");
    }
    $col = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'image_path'");
    if (!$col || mysqli_num_rows($col) == 0) {
        mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER shop_name");
    } else {
        // If column exists but is NOT NULL, alter it to allow NULL
        $colInfo = mysqli_fetch_assoc($col);
        if ($colInfo['Null'] === 'NO') {
            mysqli_query($conn, "ALTER TABLE tbl_vendors MODIFY COLUMN image_path VARCHAR(255) DEFAULT NULL");
        }
    }
    // Ensure logo_path column exists (for older installs)
    $colLogo = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'logo_path'");
    if (!$colLogo || mysqli_num_rows($colLogo) == 0) {
        mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL AFTER image_path");
    } else {
        $colInfoLogo = mysqli_fetch_assoc($colLogo);
        if ($colInfoLogo['Null'] === 'NO') {
            mysqli_query($conn, "ALTER TABLE tbl_vendors MODIFY COLUMN logo_path VARCHAR(255) DEFAULT NULL");
        }
    }

    $statusCol = mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'status'");
    if (!$statusCol || mysqli_num_rows($statusCol) == 0) {
        mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN status ENUM('pending','active','inactive','suspended') NOT NULL DEFAULT 'pending'");
    } else {
        $statusInfo = mysqli_fetch_assoc($statusCol);
        if ($statusInfo['Default'] !== 'pending') {
            @mysqli_query($conn, "ALTER TABLE tbl_vendors MODIFY COLUMN status ENUM('pending','active','inactive','suspended') NOT NULL DEFAULT 'pending'");
        }
    }

    if (empty($errors)) {
        // Check duplicate email
        $stmt = mysqli_prepare($conn, 'SELECT vendor_id FROM tbl_vendors WHERE email = ? LIMIT 1');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = 'Email is already registered.';
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = 'Database error (prepare failed).';
        }
    }

    if (empty($errors)) {
        $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);
        // Handle vendor image upload (optional)
        if (isset($_FILES['vendor_image']) && ($_FILES['vendor_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['vendor_image'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, $allowed, true)) {
                    $errors[] = 'Vendor image must be JPG or PNG only. (Detected: ' . $mime . ')';
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $errors[] = 'Vendor image must be under 2MB. (Size: ' . round($file['size'] / 1024 / 1024, 2) . 'MB)';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'vendor_' . uniqid() . '.' . strtolower($ext);
                    
                    // Get the admin uploads/vendors directory (not vendor subdirectory)
                    // Register.php is in admin/vendor/, so go up one level to admin/
                    $uploadDir = __DIR__ . '/../uploads/vendors';
                    
                    // Debug: Log directory
                    // error_log("Upload Dir: " . $uploadDir);
                    
                    // Check and create directory
                    if (!is_dir($uploadDir)) {
                        if (!@mkdir($uploadDir, 0777, true)) {
                            $errors[] = 'Failed to create upload directory.';
                        }
                    }
                    
                    if (empty($errors)) {
                        $dest = $uploadDir . '/' . $filename;
                        if (@move_uploaded_file($file['tmp_name'], $dest)) {
                            // chmod to make sure file is readable
                            @chmod($dest, 0644);
                            $image_path = $filename;
                        } else {
                            $errors[] = 'Failed to upload image file.';
                        }
                    }
                }
            } else {
                $errors[] = 'Error uploading image. Error code: ' . $file['error'];
            }
        }

            // Handle shop logo upload (optional)
            if (isset($_FILES['shop_logo']) && ($_FILES['shop_logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['shop_logo'];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['image/jpeg', 'image/png'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    if (!in_array($mime, $allowed, true)) {
                        $errors[] = 'Shop logo must be JPG or PNG only. (Detected: ' . $mime . ')';
                    } elseif ($file['size'] > 2 * 1024 * 1024) {
                        $errors[] = 'Shop logo must be under 2MB. (Size: ' . round($file['size'] / 1024 / 1024, 2) . 'MB)';
                    } else {
                        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'shop_logo_' . uniqid() . '.' . strtolower($ext);
                        $uploadDir = __DIR__ . '/../uploads/vendors';
                        if (!is_dir($uploadDir)) {
                            if (!@mkdir($uploadDir, 0777, true)) {
                                $errors[] = 'Failed to create upload directory.';
                            }
                        }
                        if (empty($errors)) {
                            $dest = $uploadDir . '/' . $filename;
                            if (@move_uploaded_file($file['tmp_name'], $dest)) {
                                @chmod($dest, 0644);
                                $logo_path = $filename;
                            } else {
                                $errors[] = 'Failed to upload shop logo file.';
                            }
                        }
                    }
                } else {
                    $errors[] = 'Error uploading shop logo. Error code: ' . $file['error'];
                }
            }

            // Insert vendor including optional image & logo paths
            $insert = mysqli_prepare($conn, 'INSERT INTO tbl_vendors (vendor_name, email, password, phone, address, shop_name, image_path, logo_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            if ($insert) {
                $status_value = 'pending';
                mysqli_stmt_bind_param($insert, 'sssssssss', $vendor_name, $email, $password_hashed, $phone, $address, $shop_name, $image_path, $logo_path, $status_value);
                if (mysqli_stmt_execute($insert)) {
                    mysqli_stmt_close($insert);
                    // Debug message showing what was saved
                    $debug_msg = 'Registration successful! Image: ' . ($image_path ? 'Yes (' . $image_path . ')' : 'No') . ' | Logo: ' . ($logo_path ? 'Yes (' . $logo_path . ')' : 'No');
                    echo "<script>console.log('" . $debug_msg . "'); alert('Registration successful! Please wait until the admin approves your request.'); window.location.href='login.php';</script>";
                    exit;
                } else {
                    $errors[] = 'Registration failed: ' . mysqli_stmt_error($insert);
                    mysqli_stmt_close($insert);
                }
            } else {
                $errors[] = 'Database error: ' . mysqli_error($conn);
            }
    }

    if (!empty($errors)) {
        $msg = htmlspecialchars(implode("\\n", $errors), ENT_QUOTES);
        echo "<script>alert('$msg');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
    /* Canyon bakery-inspired style copied from login.php */
    :root {
        --card-width: min(520px, 95vw);
        --card-radius: 2rem;
        --primary: #8a3b0f;
        --primary-soft: #ffc99c;
        --surface: #ffffffcc;
        --text: #4a3525;
        --shadow: 0 25px 45px rgba(20, 10, 8, 0.26);
    }

    @font-face {
        font-family: 'GRAVIS';
        src: url('dist/fonts/GRAVIS.ttf') format('truetype');
        font-weight: normal;
        font-style: normal;
        font-display: swap;
    }
    .gravis { font-family: 'GRAVIS', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

    body {
        margin: 0;
        min-height: 100vh;
        background: linear-gradient(180deg, rgba(255,255,255,0.45), rgba(255,255,255,0.45)), url('../../uploads/vendorbg.jpg') center/cover fixed no-repeat;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 0.8rem;
        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    }

    .login-card {
        width: 520px;
        max-width: 90vw;
        background: var(--surface);
        border-radius: var(--card-radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        border: 1px solid rgba(255, 174, 110, 0.32);
    }

    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .password-wrapper .toggle-password {
        position: absolute;
        right: 0.8rem;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        cursor: pointer;
        color: #5d422e;
        font-size: 1.1rem;
        padding: 0;
        line-height: 1;
    }
    .password-wrapper .toggle-password:focus {
        outline: 2px solid rgba(138, 59, 15, 0.35);
        outline-offset: 2px;
    }

    .login-hero {
        background: url('https://images.unsplash.com/photo-1515444744559-ff64f27f6f6d?auto=format&fit=crop&w=800&q=80') center/cover no-repeat;
        min-height: 80px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        position: relative;
    }

    .login-hero::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.26), rgba(255, 255, 255, 0.02));
    }

    .login-header {
        padding: 1.3rem 1rem;
        text-align: center;
        background-color: #ffffff;
    }
    .login-header h2 {
        margin: 0;
        font-size: 1.9rem;
        color: var(--primary);
        font-family: 'GRAVIS', sans-serif;
        letter-spacing: 0.02em;
    }

    .login-subtitle { color: #8f6230; font-weight: 500; margin-top: 0.35rem; }

    .login-body {
        padding: 1.1rem 1.1rem 1.5rem;
    }

    .form-group { margin-bottom: 0.9rem; display: flex; flex-direction: column; gap: 0.35rem; }

    .login-card label { color: #76543a; font-weight: 600; font-size: 0.95rem; }

    .login-card input.form-control,
    .login-card textarea.form-control {
        width: 100%;
        height: 44px;
        border-radius: 0.85rem;
        border: 1px solid #e3bfa6;
        background: #fff;
        padding: 0.55rem 0.8rem;
        box-shadow: inset 0 1px 8px rgba(0, 0, 0, 0.04);
        color: #5d422e;
        font-size: 1rem;
    }
    .login-card textarea.form-control { height: auto; min-height: 82px; padding: 0.6rem; }
    .login-card input.form-control:focus,
    .login-card textarea.form-control:focus {
        outline: 2px solid rgba(138, 59, 15, 0.28);
        border-color: #bd6f44;
    }

    .login-card .btn-primary,
    .login-card .btn-outline-primary {
        width: 100%;
        border-radius: 1rem;
        padding: 0.78rem 0.95rem;
        border: none;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: 0.01em;
        cursor: pointer;
    }

    .btn-primary {
        background: linear-gradient(130deg, #d07234 0%, #a8471b 100%);
        color: #fff;
        box-shadow: 0 12px 20px rgba(146, 66, 22, 0.35);
        border: 0;
    }

    .btn-outline-primary {
        background: transparent;
        border: 1px solid #d07336;
        color: #9c4f26;
        padding: 0.7rem 0.9rem;
    }

    .login-footer {
        text-align: center;
        margin-top: 0.4rem;
        font-size: 0.9rem;
        color: #75523a;
    }
    .login-footer a { color: #a8501e; text-decoration: none; font-weight: 600; }
    .login-footer a:hover { text-decoration: underline; }

    .error-box {
        background: #ffebe9;
        border: 1px solid #f6b3aa;
        color: #8f2815;
        border-radius: 0.8rem;
        padding: 0.8rem;
        font-size: 0.91rem;
        margin-bottom: 0.9rem;
    }

    @media (max-width: 450px) {
        :root { --card-width: min(100vw, 360px); }
        .login-card { border-radius: 1.4rem; }
    }
    </style>
</head>
<body>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;">
        <div class="login-card">
            <div class="login-hero"></div>
            <div class="login-header">
                <h2 class="gravis">🛍️ Vendor Registration</h2>
                <p class="login-subtitle">Create your account to manage your bakery menu and orders</p>
            </div>
            <div class="login-body">
                <form method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
                    <div class="form-group">
                        <label for="vendor_name">Vendor Name <span style="color:red">*</span></label>
                        <input id="vendor_name" type="text" class="form-control" name="vendor_name" value="<?php echo htmlspecialchars($vendor_name ?? ''); ?>" required minlength="2" maxlength="255" pattern="[A-Za-z&'.,\- ]+" title="Vendor name may only contain letters, spaces, and these symbols: & . , - '" placeholder="Enter your vendor business name">
                    </div>
                    <div class="form-group">
                        <label for="shop_name">Shop Name <span style="color:red">*</span></label>
                        <input id="shop_name" type="text" class="form-control" name="shop_name" value="<?php echo htmlspecialchars($shop_name ?? ''); ?>" required minlength="2" maxlength="255" placeholder="Enter your shop name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email <span style="color:red">*</span></label>
                        <input id="email" type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" required placeholder="Enter a valid email address">
                        <small style="color:#7b6f63;">We'll never share your email</small>
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span style="color:red">*</span></label>
                        <div class="password-wrapper">
                            <input id="password" type="password" class="form-control" name="password" autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false" required minlength="8" placeholder="Create a strong password (min 8 chars, 1 letter + 1 number)">
                            <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁</button>
                        </div>
                        <small style="color:#7b6f63;">Must contain at least one letter and one number</small>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone <span style="color:red">*</span></label>
                        <input id="phone" type="tel" inputmode="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required placeholder="Enter 7-15 digit phone number" pattern="[0-9+\-() ]{7,20}" title="Phone must be 7-15 digits">
                        <small style="color:#7b6f63;">Accepts: digits, +, -, (), and spaces</small>
                    </div>
                    <div class="form-group">
                        <label for="address">Address <span style="color:red">*</span></label>
                        <textarea id="address" class="form-control" name="address" required minlength="5" maxlength="500" placeholder="Enter your complete business address"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                        <small style="color:#7b6f63;">Minimum 5 characters, maximum 500</small>
                    </div>
                    <div class="form-group">
                        <label for="shop_logo">Shop Logo (Optional)</label>
                        <input id="shop_logo" type="file" class="form-control" name="shop_logo" accept="image/jpeg,image/png">
                        <small style="color:#7b6f63;">JPG or PNG only. Max 2MB</small>
                    </div>
                    <div class="form-group">
                        <label for="vendor_image">Vendor Image <span style="color:red">*</span></label>
                        <input id="vendor_image" type="file" class="form-control" name="vendor_image" accept="image/jpeg,image/png" required>
                        <small style="color:#7b6f63;">JPG or PNG only. Max 2MB. This field is required.</small>
                    </div>
                    <div class="form-group" style="margin-top:0.5rem;">
                        <input type="submit" class="btn btn-primary" name="register" value="Register">
                    </div>
                    <div class="login-footer">
                        <a href="login.php">Already have an account? Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.target);
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    button.textContent = '🙈';
                    button.setAttribute('aria-label', 'Hide password');
                } else {
                    input.type = 'password';
                    button.textContent = '👁';
                    button.setAttribute('aria-label', 'Show password');
                }
            });
        });
    });
    </script>
    <script src="dist/js/app.js"></script>
</body>
</html>
