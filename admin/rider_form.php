<?php
session_start();
include 'connection.php';

// Check admin login
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    header('Location: ../login.php');
    exit;
}

// Ensure profile_image column exists in tbl_riders
$colCheck = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_riders LIKE 'profile_image'");
if (!$colCheck || mysqli_num_rows($colCheck) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_riders ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL");
}

$rider = [
    'rider_id' => 0,
    'name' => '',
    'email' => '',
    'phone' => '',
    'vehicle_type' => '',
    'vehicle_number' => '',
    'status' => 'active',
    'profile_image' => ''
];

$isEdit = false;

if (isset($_GET['rider_id'])) {
    $id = intval($_GET['rider_id']);
    $stmt = mysqli_prepare($conn, "SELECT rider_id,name,email,phone,vehicle_type,vehicle_number,status,profile_image FROM tbl_riders WHERE rider_id=?");
    mysqli_stmt_bind_param($stmt,'i',$id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $rider = $row;
        $isEdit = true;
    }
    mysqli_stmt_close($stmt);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    $status = isset($_POST['status']) && $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    $rider_id = intval($_POST['rider_id'] ?? 0);
    $profile_image = $rider['profile_image'];

    // Keep posted values in form if validation fails
    $rider['name'] = $name;
    $rider['email'] = $email;
    $rider['phone'] = $phone;
    $rider['vehicle_type'] = $vehicle_type;
    $rider['vehicle_number'] = $vehicle_number;
    $rider['status'] = $status;

    $validationErrors = [];

    // Validate required fields
    if ($name === '') {
        $validationErrors[] = '❌ Rider name is required.';
    } elseif (strlen($name) < 2 || strlen($name) > 100) {
        $validationErrors[] = '❌ Rider name must be between 2 and 100 characters.';
    } elseif (!preg_match('/^[\p{L} \-\'\.]+$/u', $name)) {
        $validationErrors[] = '❌ Rider name may only contain letters, spaces, hyphens, apostrophes, and periods.';
    }

    if ($email === '') {
        $validationErrors[] = '❌ Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validationErrors[] = '❌ Please enter a valid email address.';
    } else {
        if ($rider_id > 0) {
            $dupStmt = mysqli_prepare($conn, 'SELECT rider_id FROM tbl_riders WHERE email = ? AND rider_id != ?');
            if ($dupStmt) {
                mysqli_stmt_bind_param($dupStmt, 'si', $email, $rider_id);
                mysqli_stmt_execute($dupStmt);
                $dupRes = mysqli_stmt_get_result($dupStmt);
                if ($dupRes && mysqli_num_rows($dupRes) > 0) {
                    $validationErrors[] = '❌ Email is already used by another rider.';
                }
                mysqli_stmt_close($dupStmt);
            }
        } else {
            $dupStmt = mysqli_prepare($conn, 'SELECT rider_id FROM tbl_riders WHERE email = ?');
            if ($dupStmt) {
                mysqli_stmt_bind_param($dupStmt, 's', $email);
                mysqli_stmt_execute($dupStmt);
                $dupRes = mysqli_stmt_get_result($dupStmt);
                if ($dupRes && mysqli_num_rows($dupRes) > 0) {
                    $validationErrors[] = '❌ Email is already used by another rider.';
                }
                mysqli_stmt_close($dupStmt);
            }
        }
    }

    if ($phone === '') {
        $validationErrors[] = '❌ Phone number is required.';
    } else {
        $digits = preg_replace('/\D+/', '', $phone);
        if (!preg_match('/^[0-9+()\-\s]+$/', $phone)) {
            $validationErrors[] = '❌ Phone number contains invalid characters.';
        } elseif (strlen($digits) < 7 || strlen($digits) > 15) {
            $validationErrors[] = '❌ Phone number must contain 7 to 15 digits.';
        }
    }

    if ($vehicle_number !== '' && !preg_match('/^[A-Z]{2}-\d{2}-[A-Z]{2}-\d{4}$/', $vehicle_number)) {
        $validationErrors[] = '❌ Vehicle number must be in format: GJ-01-AJ-0101 (State-District-Series-Number)';
    }

    if (!$isEdit) {
        $password = trim($_POST['password'] ?? '');
        if ($password === '') {
            $validationErrors[] = '❌ Password is required for new riders.';
        } elseif (strlen($password) < 8) {
            $validationErrors[] = '❌ Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $validationErrors[] = '❌ Password must contain at least one letter and one number.';
        }
    }

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

        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_ext, $allowed_ext)) {
            $validationErrors[] = '❌ Only JPG, PNG, and GIF files are allowed for profile picture.';
        } elseif ($file_size > $max_size) {
            $validationErrors[] = '❌ Profile picture must be less than 5MB.';
        } else {
            $new_filename = 'rider_' . time() . '.' . $file_ext;
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                if (!empty($rider['profile_image']) && file_exists($rider['profile_image'])) {
                    @unlink($rider['profile_image']);
                }
                $profile_image = $upload_dir . $new_filename;
            } else {
                $validationErrors[] = '❌ Failed to upload profile picture.';
            }
        }
    }

    if (empty($validationErrors)) {
        if ($rider_id > 0) {
            $upd = mysqli_prepare($conn, "UPDATE tbl_riders SET name=?,email=?,phone=?,vehicle_type=?,vehicle_number=?,status=?,profile_image=? WHERE rider_id=?");
            if ($upd) {
                mysqli_stmt_bind_param($upd,'sssssssi',$name,$email,$phone,$vehicle_type,$vehicle_number,$status,$profile_image,$rider_id);
                if (mysqli_stmt_execute($upd)) {
                    $success = '✅ Rider updated successfully!';
                    echo '<script>setTimeout(() => { window.location.href = "riders_list.php"; }, 1000);</script>';
                } else {
                    $error = '❌ Error updating rider: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($upd);
            } else {
                $error = '❌ Unable to update rider at this time.';
            }
        } else {
            $password = trim($_POST['password'] ?? '');
            if ($password === '') {
                $error = '❌ Password is required for new riders!';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $ins = mysqli_prepare($conn, "INSERT INTO tbl_riders (name,email,phone,password,vehicle_type,vehicle_number,status,profile_image) VALUES (?,?,?,?,?,?,?,?)");
                if ($ins) {
                    mysqli_stmt_bind_param($ins,'ssssssss',$name,$email,$phone,$passwordHash,$vehicle_type,$vehicle_number,$status,$profile_image);
                    if (mysqli_stmt_execute($ins)) {
                        $success = '✅ Rider added successfully!';
                        echo '<script>setTimeout(() => { window.location.href = "riders_list.php"; }, 1000);</script>';
                    } else {
                        $error = '❌ Error adding rider: ' . mysqli_error($conn);
                    }
                    mysqli_stmt_close($ins);
                } else {
                    $error = '❌ Unable to add rider at this time.';
                }
            }
        }
    } else {
        $error = implode(' ', $validationErrors);
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Rider - Admin</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(145deg, #f8f9ff, #e7f1fd 40%, #eef7f7 100%); color: #2e3a45; }
        .wrapper { display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 130px); padding: 2rem 1rem; }
        .form-card { background: rgba(255, 255, 255, 0.95); border-radius: 16px; box-shadow: 0 22px 38px rgba(41, 82, 152, 0.16), 0 10px 22px rgba(17, 60, 120, 0.09); padding: 2.5rem; max-width: 700px; width: 100%; border: 1px solid rgba(20, 56, 130, 0.12); backdrop-filter: blur(4px); }
        .form-card::before { content: ""; position: absolute; inset: 0; border-radius: 16px; padding: 1px; background: linear-gradient(120deg, rgba(33, 143, 255, 0.5), rgba(24, 190, 255, 0.28), rgba(77, 212, 194, 0.35)); -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); mask-composite: exclude; pointer-events: none; }
        .form-header { margin-bottom: 1.8rem; }
        .form-header h2 { font-size: 1.9rem; font-weight: 800; margin: 0; color: #0f2c64; letter-spacing: 0.5px; }
        .form-header h2::before { content: ""; display: inline-block; width: 42px; height: 5px; margin-right: 9px; background: linear-gradient(90deg, #0026ff, #0de6f4); border-radius: 999px; vertical-align: middle; }
        .form-group { margin-bottom: 1.15rem; }
        .form-group label { display: block; font-size: 0.95rem; font-weight: 700; margin-bottom: 0.45rem; color: #18315c; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem 0.85rem; border: 1px solid #d8e3f6; border-radius: 8px; background: #fcfeff; box-shadow: inset 0 2px 4px rgba(13, 38, 77, 0.05); font-size: 1rem; color: #122a52; transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #3c8dff; box-shadow: 0 0 0 4px rgba(57, 147, 255, 0.22); transform: translateY(-1px); }
        input[type="file"] { border-radius: 8px; }
        .form-group small { color: #6b778c; }
        .image-preview-container { margin-top: 0.7rem; display: flex; align-items: center; gap: 0.5rem; }
        .image-preview { width: 80px; height: 80px; border-radius: 12px; object-fit: cover; border: 1px solid #d8e3f6; box-shadow: 0 4px 12px rgba(10, 42, 93, 0.12); }
        .btn-group { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.6rem; }
        .btn { padding: 0.68rem 1.25rem; border-radius: 10px; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; box-shadow: 0 12px 20px rgba(16, 44, 88, 0.1); transition: transform 0.18s ease, box-shadow 0.18s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: linear-gradient(90deg, #2f5dff 0%, #2ad4ff 100%); color: #fff; border: 1px solid transparent; }
        .btn-primary:hover { background: linear-gradient(90deg, #1b4ed0 0%, #12b5d7 100%); box-shadow: 0 15px 24px rgba(29, 94, 225, 0.35); }
        .btn-secondary { background: #465166; color: #fff; border: 1px solid #37425a; }
        .btn-secondary:hover { background: #39425a; box-shadow: 0 12px 20px rgba(33, 56, 79, 0.28); }
        .alert { border-radius: 10px; font-weight: 600; }
        .note { font-size: 0.9rem; color: #3d548f; margin-bottom: 1rem; }
        .page-layout { display: flex; align-items: flex-start; justify-content: center; gap: 1.5rem; }
        .form-card { width: 100%; max-width: 680px; }
        .form-area { width: 100%; }
        .image-panel { flex: 0 0 310px; display: flex; justify-content: center; align-items: flex-start; margin-top: 200px; }
        .side-panel-image { width: 100%; max-width: 320px; border-radius: 12px; box-shadow: 0 18px 28px rgba(12, 53, 105, 0.2); border: 2px solid rgba(255,255,255,0.82); }
        @media (max-width: 1200px) { .page-layout { flex-direction: column; align-items: stretch; } .image-panel { margin-top: 1rem; } }
        @media (max-width: 768px) { .form-card { padding: 1.5rem; } .page-layout { gap: 1rem; } }
    </style>
</head>
<body class="py-0 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-0 md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content w-full">
           
            <!-- <div class="wrapper p-6"> -->
            <div class="page-layout">
                <div class="form-card">
                    <div class="form-header">
                        <h2><?php echo $isEdit ? '✏️ Edit Rider' : '➕ Add New Rider'; ?></h2>
                    </div>
                    <div class="form-area">

                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="rider_id" value="<?php echo intval($rider['rider_id']); ?>">

                        <div class="form-group">
                            <label for="profile_image">Profile Picture</label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" placeholder="Upload rider profile image">
                            <small style="color: #666;">Allowed: JPG, PNG, GIF (Max 5MB)</small>
                            <?php if ($isEdit && !empty($rider['profile_image']) && file_exists($rider['profile_image'])): ?>
                                <div style="margin-top: 10px;">
                                    <img src="<?php echo htmlspecialchars($rider['profile_image']); ?>" style="max-width: 150px; height: auto; border-radius: 4px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="name">Rider Name *</label>
                            <input type="text" id="name" name="name" required minlength="2" maxlength="100" pattern="[A-Za-z\s\-\.']+" value="<?php echo htmlspecialchars($rider['name']); ?>" placeholder="Enter full name">
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required maxlength="255" value="<?php echo htmlspecialchars($rider['email']); ?>" placeholder="e.g. rider@example.com">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" required maxlength="20" pattern="[0-9+()\-\s]+" value="<?php echo htmlspecialchars($rider['phone']); ?>" placeholder="e.g. 9876543210">
                        </div>

                        <div class="form-group">
                            <label for="vehicle_type">Vehicle Type</label>
                            <input type="text" id="vehicle_type" name="vehicle_type" maxlength="100" value="<?php echo htmlspecialchars($rider['vehicle_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Bike, Car, Scooter">
                        </div>

                        <div class="form-group">
                            <label for="vehicle_number">Vehicle Number</label>
                            <input type="text" id="vehicle_number" name="vehicle_number" maxlength="13" pattern="[A-Z]{2}-[0-9]{2}-[A-Z]{2}-[0-9]{4}" value="<?php echo htmlspecialchars($rider['vehicle_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. GJ-01-AJ-0101">
                            <small style="color: #666;">Format: State-District-Series-Number (e.g., GJ-01-AJ-0101)</small>
                        </div>

                        <?php if (!$isEdit): ?>
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required placeholder="Enter a strong password">
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo $rider['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $rider['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $isEdit ? '💾 Update Rider' : '➕ Add Rider'; ?>
                            </button>
                            <a href="riders_list.php" class="btn btn-secondary">❌ Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="image-panel">
                <img src="../user/images/adminrider.jpg" alt="Rider Illustration" title="Rider illustration" class="side-panel-image" onerror="this.src='../user/images/adminrider.jpg'" />
            </div>
        </div>
    </div>
    <script src="dist/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Vehicle number auto-formatting
        document.getElementById('vehicle_number').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            
            // Remove any existing hyphens for processing
            value = value.replace(/-/g, '');
            
            // Format as GJ-01-AJ-0101
            let formatted = '';
            if (value.length > 0) formatted += value.substring(0, 2); // State code
            if (value.length > 2) formatted += '-' + value.substring(2, 4); // District
            if (value.length > 4) formatted += '-' + value.substring(4, 6); // Series
            if (value.length > 6) formatted += '-' + value.substring(6, 10); // Number
            
            // Limit to maximum length
            if (value.length > 10) {
                value = value.substring(0, 10);
                formatted = value.substring(0, 2) + '-' + value.substring(2, 4) + '-' + value.substring(4, 6) + '-' + value.substring(6, 10);
            }
            
            e.target.value = formatted;
        });
        
        // Prevent invalid characters on keypress
        document.getElementById('vehicle_number').addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            const allowed = /[A-Za-z0-9]/;
            
            if (!allowed.test(char)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>