<?php
include 'session.php';
include 'connection.php';

// Get current rider's profile information
$rider_id = $_SESSION['rider_id'] ?? null;

if (!$rider_id) {
    header('Location: login.php');
    exit;
}

$rider = [];
$query = "SELECT rider_id, name, email, phone, vehicle_type, vehicle_number, status, created_at, is_online, profile_image FROM tbl_riders WHERE rider_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $rider_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $rider = mysqli_fetch_assoc($result);
}
mysqli_stmt_close($stmt);

// Handle profile update
$updateSuccess = false;
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone = trim($_POST['phone'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    $profile_image = $rider['profile_image']; // keep current image

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/riders/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_name = $_FILES['profile_image']['name'];
        $file_size = $_FILES['profile_image']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_ext)) {
            $updateError = '❌ Only JPG, PNG, and GIF files are allowed!';
        } elseif ($file_size > $max_size) {
            $updateError = '❌ File size must be less than 5MB!';
        } else {
            $new_filename = 'rider_' . $rider_id . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                // Delete old image if exists
                if (!empty($rider['profile_image']) && file_exists($rider['profile_image'])) {
                    @unlink($rider['profile_image']);
                }
                $profile_image = $upload_dir . $new_filename;
            } else {
                $updateError = '❌ Failed to upload image!';
            }
        }
    }

    if (empty($updateError) && !empty($phone)) {
        $updateQuery = "UPDATE tbl_riders SET phone = ?, vehicle_type = ?, vehicle_number = ?, profile_image = ? WHERE rider_id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, 'ssssi', $phone, $vehicle_type, $vehicle_number, $profile_image, $rider_id);
        
        if (mysqli_stmt_execute($updateStmt)) {
            $updateSuccess = true;
            // Refresh rider data
            $stmt2 = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt2, 'i', $rider_id);
            mysqli_stmt_execute($stmt2);
            $result2 = mysqli_stmt_get_result($stmt2);
            if ($result2 && mysqli_num_rows($result2) > 0) {
                $rider = mysqli_fetch_assoc($result2);
            }
            mysqli_stmt_close($stmt2);
        } else {
            $updateError = 'Failed to update profile. Please try again.';
        }
        mysqli_stmt_close($updateStmt);
    } else {
        $updateError = 'Phone number is required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Profile - Rider Dashboard</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .profile-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        .profile-subtitle {
            font-size: 14px;
            color: #999;
        }
        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid #76c076;
        }
        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: #333;
            word-break: break-word;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .online-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #e3f2fd;
            color: #1976d2;
        }
        .edit-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        .edit-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #76c076;
            box-shadow: 0 0 0 3px rgba(118, 192, 118, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .btn-update {
            background: #76c076;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-update:hover {
            background: #5fa85f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(118, 192, 118, 0.3);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .read-only {
            background: #f5f5f5;
            color: #666;
        }
        .read-only:focus {
            background: #f5f5f5;
            border-color: #ddd !important;
            box-shadow: none !important;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <!-- BEGIN: Mobile Menu -->
    <div class="mobile-menu md:hidden">
        <div class="mobile-menu-bar">
            <a href="dashboard.php" class="flex mr-auto">
                <img alt="Dessert Magic" class="w-6" src="src/cake2.png">
            </a>
            <a href="javascript:;" class="mobile-menu-toggler"> <i data-lucide="bar-chart-2" class="w-8 h-8 text-white transform -rotate-90"></i> </a>
        </div>
    </div>

    <div class="flex overflow-hidden">
        <!-- BEGIN: Side Menu -->
        <?php include 'sideMenu.php'; ?>
        <!-- END: Side Menu -->

        <!-- BEGIN: Content -->
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php'; ?>
            <!-- END: Top Bar -->

            <!-- BEGIN: Content -->
            <div class="page-content">
                <div class="profile-container">
                    <?php if ($updateSuccess): ?>
                        <div class="alert alert-success">✓ Profile updated successfully!</div>
                    <?php endif; ?>
                    <?php if ($updateError): ?>
                        <div class="alert alert-error">✗ <?php echo htmlspecialchars($updateError); ?></div>
                    <?php endif; ?>

                    <div class="profile-header">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <?php 
                            $profile_image = 'dist/images/profile-5.jpg'; // default
                            if (!empty($rider['profile_image'])) {
                                // Adjust path if it's from admin folder uploads
                                $img_path = $rider['profile_image'];
                                if (file_exists($img_path)) {
                                    $profile_image = $img_path;
                                } elseif (file_exists('../' . $img_path)) {
                                    $profile_image = '../' . $img_path;
                                }
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #76c076; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        </div>
                        <div class="profile-title"><?php echo htmlspecialchars($rider['name'] ?? 'Rider'); ?></div>
                        <div class="profile-subtitle">Rider ID: #<?php echo $rider_id; ?></div>
                        <div style="margin-top: 12px;">
                            <span class="status-badge <?php echo ($rider['status'] === 'active') ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo ucfirst($rider['status'] ?? 'inactive'); ?>
                            </span>
                            <?php if ($rider['is_online']): ?>
                                <span class="online-badge">● Online</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #333;">Registration Details</h3>
                        <div class="profile-info">
                            <div class="info-item">
                                <div class="info-label">📧 Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($rider['email'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">📞 Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($rider['phone'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">🚗 Vehicle Type</div>
                                <div class="info-value"><?php echo htmlspecialchars($rider['vehicle_type'] ?? 'Not specified'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">🔢 Vehicle Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($rider['vehicle_number'] ?? 'Not specified'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">📅 Joined Date</div>
                                <div class="info-value"><?php echo date('M d, Y', strtotime($rider['created_at'] ?? '')); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="edit-section">
                        <h3 class="edit-title">Update Profile</h3>
                        <form method="POST" enctype="multipart/form-data" novalidate>
                            <div class="form-group">
                                <label for="profile_picture">Profile Picture</label>
                                <input type="file" id="profile_picture" name="profile_image" accept="image/*">
                                <small style="color: #666; font-size: 12px;">Allowed: JPG, PNG, GIF (Max 5MB)</small>
                                <?php if (!empty($rider['profile_image'])): 
                                    $img_path = $rider['profile_image'];
                                    if (!file_exists($img_path) && file_exists('../' . $img_path)) {
                                        $img_path = '../' . $img_path;
                                    }
                                    if (file_exists($img_path)):
                                ?>
                                    <div style="margin-top: 10px;">
                                        <img src="<?php echo htmlspecialchars($img_path); ?>" style="max-width: 120px; height: auto; border-radius: 8px;">
                                    </div>
                                <?php endif; endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($rider['email'] ?? ''); ?>" class="read-only" readonly disabled>
                                <small style="color: #999; font-size: 12px;">Email cannot be changed</small>
                            </div>

                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($rider['name'] ?? ''); ?>" class="read-only" readonly disabled>
                                <small style="color: #999; font-size: 12px;">Contact admin to change name</small>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone">Phone Number *</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($rider['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_type">Vehicle Type</label>
                                    <input type="text" id="vehicle_type" name="vehicle_type" placeholder="e.g., Bike, Car, Scooter" value="<?php echo htmlspecialchars($rider['vehicle_type'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="vehicle_number">Vehicle Number / Plate</label>
                                <input type="text" id="vehicle_number" name="vehicle_number" placeholder="e.g., GJ-01-AB-1234" value="<?php echo htmlspecialchars($rider['vehicle_number'] ?? ''); ?>">
                            </div>

                            <button type="submit" name="update_profile" class="btn-update">💾 Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- END: Content -->
        </div>
        <!-- END: Content -->
    </div>

    <!-- BEGIN: Tail -->
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://unpkg.com/@popperjs/core"></script>
    <script src="dist/js/app.js"></script>
    <!-- END: Tail -->
</body>
</html>
