<?php
session_start();
include 'connection.php';

// Check if vendor is logged in
if (!isset($_SESSION['vendor_id'])) {
    header('Location: login.php');
    exit;
}

$vendor_id = (int)$_SESSION['vendor_id'];
$success_msg = '';
$error_msg = '';

// detect discount column and include only if present
$hasDiscCol = false;
$colChk = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'vendor_discount_percent'");
if ($colChk && mysqli_num_rows($colChk) > 0) {
    $hasDiscCol = true;
}
$selectCols = 'vendor_id, vendor_name, email, phone, address, shop_name, image_path, logo_path';
if ($hasDiscCol) { $selectCols .= ', vendor_discount_percent'; }
$stmt = mysqli_prepare($conn, "SELECT {$selectCols} FROM tbl_vendors WHERE vendor_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$vendor = mysqli_fetch_assoc($res);

if (!$vendor) {
    $error_msg = 'Vendor not found.';
}
$vendor_discount = floatval($vendor['vendor_discount_percent'] ?? 0);

// Handle profile update
if (isset($_POST['update_profile'])) {
    $vendor_name = trim($_POST['vendor_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $shop_name = trim($_POST['shop_name'] ?? '');
    $vendor_discount = floatval($_POST['vendor_discount_percent'] ?? 0);
    $logo_path = $vendor['logo_path'] ?? '';
    
    if (empty($vendor_name)) {
        $error_msg = 'Vendor name is required.';
    } else {
        // Handle image upload
        $image_path = $vendor['image_path'] ?? '';
        if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && $_FILES['image']['size'] > 0) {
            $upload_dir = __DIR__ . '/../uploads/vendors/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file = $_FILES['image'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = 'profile_' . time() . '_' . uniqid() . '.' . strtolower($ext);
                $target_file = $upload_dir . $file_name;
                if (move_uploaded_file($file['tmp_name'], $target_file)) {
                    @chmod($target_file, 0644);
                    $image_path = $file_name;
                } else {
                    $error_msg = 'Failed to upload image.';
                }
            } else {
                $error_msg = 'Error uploading image.';
            }
        }

        // Handle shop logo upload (optional)
        if (isset($_FILES['shop_logo']) && ($_FILES['shop_logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE && $_FILES['shop_logo']['size'] > 0) {
            $upload_dir = __DIR__ . '/../uploads/vendors/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file = $_FILES['shop_logo'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, $allowed, true)) {
                    $error_msg = 'Shop logo must be JPG or PNG only.';
                } elseif ($file['size'] > 2 * 1024 * 1024) {
                    $error_msg = 'Shop logo must be under 2MB.';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $file_name = 'logo_' . time() . '_' . uniqid() . '.' . strtolower($ext);
                    $target_file = $upload_dir . $file_name;
                    if (move_uploaded_file($file['tmp_name'], $target_file)) {
                        @chmod($target_file, 0644);
                        $logo_path = $file_name;
                    } else {
                        $error_msg = 'Failed to upload shop logo.';
                    }
                }
            } else {
                $error_msg = 'Error uploading shop logo.';
            }
        }
        
        if (!$error_msg) {
            // Update vendor profile (include optional logo)
            if ($hasDiscCol) {
                $sql = "UPDATE tbl_vendors SET vendor_name = ?, phone = ?, address = ?, shop_name = ?, image_path = ?, logo_path = ?, vendor_discount_percent = ? WHERE vendor_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                // six strings for text fields, one double for discount, one integer for ID
                mysqli_stmt_bind_param($stmt, 'ssssssdi', $vendor_name, $phone, $address, $shop_name, $image_path, $logo_path, $vendor_discount, $vendor_id);
            } else {
                $sql = "UPDATE tbl_vendors SET vendor_name = ?, phone = ?, address = ?, shop_name = ?, image_path = ?, logo_path = ? WHERE vendor_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, 'ssssssi', $vendor_name, $phone, $address, $shop_name, $image_path, $logo_path, $vendor_id);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['vendor_name'] = $vendor_name;
                $success_msg = 'Profile updated successfully!';
                // Refresh vendor data
                $vendor['vendor_name'] = $vendor_name;
                $vendor['phone'] = $phone;
                $vendor['address'] = $address;
                $vendor['shop_name'] = $shop_name;
                $vendor['image_path'] = $image_path;
                $vendor['logo_path'] = $logo_path;
            } else {
                $error_msg = 'Failed to update profile.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Vendor Profile</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        .profile-container { max-width: 600px; margin: 40px auto; padding: 20px; }
        .profile-card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 30px; }
        .profile-header { text-align: center; margin-bottom: 30px; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0 auto 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; }
        .btn-secondary { background: #e5e7eb; color: #333; margin-left: 10px; }
        .btn-secondary:hover { background: #d1d5db; }
        .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .file-input { padding: 8px; }
        .btn-group { display: flex; gap: 10px; margin-top: 25px; }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <!-- BEGIN: Side Menu -->
        <?php include 'sideMenu.php' ?>
        <!-- END: Side Menu -->

        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->

            <div class="profile-container">
                <div class="profile-card">
                    <div class="profile-header">
                        <h1>Vendor Profile</h1>
                        <p style="color: #666; margin-top: 8px;">Edit your profile information</p>
                    </div>
                    
                    <?php if ($success_msg) { ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                    <?php } ?>
                    <?php if ($error_msg) { ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
                    <?php } ?>
                    
                    <?php if ($vendor) { ?>
                        <form method="POST" enctype="multipart/form-data">
                            <?php if (!empty($vendor['image_path']) || !empty($vendor['logo_path'])) { ?>
                                <div style="text-align: center; margin-bottom: 20px; display:flex; gap:20px; align-items:center; justify-content:center;">
                                    <?php if (!empty($vendor['image_path'])) { ?>
                                        <div>
                                            <img src="../uploads/vendors/<?php echo htmlspecialchars($vendor['image_path']); ?>" alt="Profile" class="profile-avatar" onerror="this.src='dist/images/profile-default.jpg'">
                                            <div style="text-align:center; font-size:13px; color:#666; margin-top:6px;">Profile</div>
                                        </div>
                                    <?php } ?>
                                    <?php if (!empty($vendor['logo_path'])) { ?>
                                        <div>
                                            <img src="../uploads/vendors/<?php echo htmlspecialchars($vendor['logo_path']); ?>" alt="Shop Logo" style="width:100px; height:100px; object-fit:cover; border-radius:8px; box-shadow:0 1px 4px rgba(0,0,0,0.08);" onerror="this.src='dist/images/logo-placeholder.png'">
                                            <div style="text-align:center; font-size:13px; color:#666; margin-top:6px;">Shop Logo</div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>

                            <div class="form-group">
                                <label for="shop_logo">Shop Logo</label>
                                <input type="file" id="shop_logo" name="shop_logo" accept="image/jpeg,image/png" class="file-input">
                                <small style="color: #999; display: block; margin-top: 5px;">Optional. JPG or PNG only. Max 2MB</small>
                            </div>

                            <div class="form-group">
                                <label for="image">Profile Picture</label>
                                <input type="file" id="image" name="image" accept="image/*" class="file-input">
                                <small style="color: #999; display: block; margin-top: 5px;">Max file size: 5MB. Formats: JPG, PNG, GIF</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="vendor_name">Vendor Name *</label>
                                <input type="text" id="vendor_name" name="vendor_name" value="<?php echo htmlspecialchars($vendor['vendor_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="shop_name">Shop Name</label>
                                <input type="text" id="shop_name" name="shop_name" value="<?php echo htmlspecialchars($vendor['shop_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email (Cannot be changed)</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($vendor['email'] ?? ''); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($vendor['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address"><?php echo htmlspecialchars($vendor['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="vendor_discount_percent">Vendor-wide Discount (%)</label>
                                <input type="number" step="0.01" id="vendor_discount_percent" name="vendor_discount_percent" value="<?php echo htmlspecialchars($vendor_discount ?? 0); ?>">
                                <small style="color:#999;display:block;margin-top:5px;">This percentage will be applied to all your products if it exceeds any individual product discount.</small>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                <a href="dashboard.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">Cancel</a>
                            </div>
                        </form>
                    <?php } else { ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="dist/js/app.js"></script>
</body>
</html>
