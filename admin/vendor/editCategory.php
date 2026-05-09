<?php
include 'session.php';
include 'connection.php';

$vendor_id = $_SESSION['vendor_id'] ?? null;
if (!$vendor_id) {
    echo "<script>alert('Vendor ID not found. Please login again.'); window.location.href='login.php';</script>";
    exit;
}

$idVal = isset($_GET["cat_id"]) ? (int)$_GET["cat_id"] : 0;
$sql = "SELECT * FROM tbl_categories WHERE categories_id=? AND vendor_id=?";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'ii', $idVal, $vendor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$row) {
        echo "<script>alert('Category not found or unauthorized access.'); window.location.href='viewCategory.php';</script>";
        exit;
    }
} else {
    echo "<script>alert('Database error.'); window.location.href='viewCategory.php';</script>";
    exit;
}

$name_val = $row['categories_name'] ?? '';
$description_val = $row['categories_description'] ?? '';
$image_val = $row['categories_image'] ?? '';
$status_val = $row['categories_status'] ?? '';

if (isset($_POST["btnSubmit"])) {
    $error = "";
    $Cname_val = mysqli_real_escape_string($conn, $_POST["cname"]);
    $Cdescription_val = mysqli_real_escape_string($conn, $_POST["cdescription"]);
    $Cstatus_val = mysqli_real_escape_string($conn, $_POST["cstatus"]);
    $image_name = $image_val; // Keep existing image by default
    
    // Handle image upload if a new image is provided
    if (isset($_FILES["category_image"]) && $_FILES["category_image"]["size"] > 0) {
        $target_dir = "uploads/";
        $image_file = $_FILES["category_image"]["name"];
        $image_tmp = $_FILES["category_image"]["tmp_name"];
        $image_size = $_FILES["category_image"]["size"];
        $image_error = $_FILES["category_image"]["error"];
        
        if ($image_error === 0 && $image_size <= 5000000) {
            $allowed_types = array("jpg", "jpeg", "png", "gif");
            $file_ext = strtolower(pathinfo($image_file, PATHINFO_EXTENSION));
            
            if (in_array($file_ext, $allowed_types)) {
                $image_name = time() . "_" . basename($image_file);
                $target_file = $target_dir . $image_name;
                
                if (!move_uploaded_file($image_tmp, $target_file)) {
                    $error = "Failed to upload image!";
                }
            } else {
                $error = "Only JPG, PNG, and GIF files are allowed!";
            }
        } else if ($image_error !== 0) {
            $error = "Error uploading image!";
        } else if ($image_size > 5000000) {
            $error = "Image size must be less than 5MB!";
        }
    }
    
    if (empty($error)) {
        $image_name = mysqli_real_escape_string($conn, $image_name);
        $sql = "UPDATE tbl_categories SET categories_name='$Cname_val', categories_description='$Cdescription_val', categories_image='$image_name', categories_status='$Cstatus_val' WHERE categories_id=$idVal";

        $result = mysqli_query($conn, $sql);

        if ($result) {
            echo "<script>alert('Record successfully updated');</script>";
            echo "<script>window.location.href='viewCategory.php';</script>";
            exit;
        } else {
            echo "<script>alert('Update failed: " . mysqli_error($conn) . "');</script>";
        }
    } else {
        echo "<script>alert('$error');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<!-- BEGIN: Head -->

<head>
    <!-- BEGIN: CSS Assets-->
    <link rel="stylesheet" href="dist/css/app.css" />
    <!-- END: CSS Assets-->
</head>
<!-- END: Head -->

<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <!-- BEGIN: Side Menu -->
        <?php include 'sideMenu.php' ?>
        <!-- END: Side Menu -->
        <!-- BEGIN: Content -->
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-lg font-medium mr-auto">
                    Edit Category
                </h2>
            </div>
            <div class="grid grid-cols-12 gap-6 mt-5">
                <div class="intro-y col-span-12 lg:col-span-12">
                    <!-- BEGIN: Input -->
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">

                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div id="input" class="p-5">
                                <div class="preview">
                                    <div class="mb-4">
                                        <label for="regular-form-1" class="form-label">Category Name</label>
                                        <input id="regular-form-1" type="text" class="form-control" placeholder="Enter Category Name" name="cname" value="<?php echo htmlspecialchars($name_val); ?>" required />
                                    </div>

                                    <div class="mb-4">
                                        <label for="category-description" class="form-label">Category Description</label>
                                        <textarea id="category-description" class="form-control" placeholder="Enter category description" name="cdescription" rows="4" required><?php echo htmlspecialchars($description_val); ?></textarea>
                                    </div>

                                    <div class="mb-4">
                                        <label for="category-image" class="form-label">Category Image</label>
                                        <?php if (!empty($image_val)): ?>
                                            <div class="mb-2">
                                                <img src="uploads/<?php echo htmlspecialchars($image_val); ?>" height="100" width="150" alt="Category Image">
                                            </div>
                                        <?php endif; ?>
                                        <input id="category-image" type="file" class="form-control" accept="image/jpeg,image/png,image/gif" name="category_image" />
                                        <small class="text-gray-500">Allowed formats: JPG, PNG, GIF (Max 5MB) - Leave empty to keep current image</small>
                                    </div>

                                    <div class="mb-4">
                                        <label for="regular-form-1" class="form-label">Category Status</label>
                                        <select class="form-control" name="cstatus" required>
                                            <option value="1" <?php echo ($status_val == '1') ? 'selected' : ''; ?>>Available</option>
                                            <option value="0" <?php echo ($status_val == '0') ? 'selected' : ''; ?>>Unavailable</option>
                                        </select>
                                    </div>

                                    <input type="submit" class="btn btn-primary w-24 mr-1 mb-2" name="btnSubmit" value="Submit">
                                </div>
                        </form>
                        </div>
                </div>
            </div>
            <!-- END: Input -->
            </div>
        <div class="intro-y col-span-12 lg:col-span-6"></div>
    </div>
</div>
    <!-- END: Content -->
    </div>
    <!-- BEGIN: JS Assets-->
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
    <script src="dist/js/app.js"></script>
    <!-- END: JS Assets-->
</body>
</html>