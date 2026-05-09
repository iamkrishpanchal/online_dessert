<?php
include 'session.php';
include 'connection.php';

if (isset($_POST["btnSubmit"])) {
    $error = "";
    $vendor_id = $_SESSION['vendor_id'] ?? null;
    
    if (!$vendor_id) {
        echo "<script>alert('Vendor ID not found. Please login again.'); window.location.href='login.php';</script>";
        exit;
    }
    
    // Validate inputs
    $category_name = isset($_POST["category_name"]) ? trim($_POST["category_name"]) : "";
    $category_description = isset($_POST["category_description"]) ? trim($_POST["category_description"]) : "";
    $category_status = isset($_POST["category_status"]) ? trim($_POST["category_status"]) : "";
    $image_name = "";
    
    if (empty($category_name)) {
        $error = "Category name is required!";
    } elseif (empty($category_description)) {
        $error = "Category description is required!";
    } elseif ($category_status === "" || $category_status === null) {
        $error = "Category status is required!";
    } elseif (!isset($_FILES["category_image"]) || $_FILES["category_image"]["size"] == 0) {
        $error = "Category image is required!";
    } else {
        // Handle image upload
        $target_dir = "uploads/";
        $image_file = $_FILES["category_image"]["name"];
        $image_tmp = $_FILES["category_image"]["tmp_name"];
        $image_size = $_FILES["category_image"]["size"];
        $image_error = $_FILES["category_image"]["error"];
        
        // Validate image
        if ($image_error !== 0) {
            $error = "Error uploading image!";
        } elseif ($image_size > 5000000) { // 5MB limit
            $error = "Image size must be less than 5MB!";
        } else {
            $allowed_types = array("jpg", "jpeg", "png", "gif");
            $file_ext = strtolower(pathinfo($image_file, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $allowed_types)) {
                $error = "Only JPG, PNG, and GIF files are allowed!";
            } else {
                // Generate unique filename
                $image_name = time() . "_" . basename($image_file);
                $target_file = $target_dir . $image_name;
                
                if (!move_uploaded_file($image_tmp, $target_file)) {
                    $error = "Failed to upload image!";
                }
            }
        }
    }
    
    if (empty($error) && !empty($image_name)) {
        // Insert category into database with vendor_id using prepared statement
        $sql = "INSERT INTO tbl_categories (categories_name, categories_description, categories_image, categories_status, vendor_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssi', $category_name, $category_description, $image_name, $category_status, $vendor_id);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if($result) {
                echo "<script>alert('Category added successfully!');</script>";
                echo "<script>window.location.href='viewCategory.php';</script>";
                exit;
            } else {
                $error = "Error adding category: " . mysqli_error($conn);
                echo "<script>alert('$error');</script>";
            }
        } else {
            $error = "Database error (prepare failed): " . mysqli_error($conn);
            echo "<script>alert('$error');</script>";
        }
    }
    
    if (!empty($error)) {
        echo "<script>alert('$error');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<!-- BEGIN: Head -->

<head>
    <title>Add Category</title>
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
                    Add New Category
                </h2>
            </div>
            <div class="grid grid-cols-8 gap-6 mt-8">
                <div class="intro-y col-span-12 lg:col-span-8">
                    <!-- BEGIN: Input Form -->
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                            <h3 class="text-base font-medium">Category Details</h3>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="p-5">
                                <div class="preview">
                                    <!-- Category ID (Auto Increment)
                                     <div class="mb-4">
                                        <label class="form-label">Category ID</label>
                                        <input type="text" class="form-control" value="Auto Generated" disabled placeholder="Auto generated on insert">
                                        <small class="text-gray-500">This will be automatically generated</small>
                                    </div> --> 

                                    <!-- Category Name -->
                                    <div class="mb-4">
                                        <label for="category_name" class="form-label">Category Name *</label>
                                        <input id="category_name" type="text" class="form-control" placeholder="Enter category name (e.g., Cakes, Pastries, Cookies)" name="category_name" required>
                                        <!-- <small class="text-gray-500">Maximum 25 characters</small> -->
                                    </div>

                                    <!-- Category Description -->
                                    <div class="mb-6">
                                        <label for="category_description" class="form-label">Category Description *</label>
                                        <textarea id="category_description" class="form-control" placeholder="Enter category description" name="category_description" rows="4" required></textarea>
                                        <!-- <small class="text-gray-500">Provide a brief description of this category</small> -->
                                    </div>

                                    <!-- Category Status -->
                                    <div class="mb-4">
                                        <label for="category_status" class="form-label">Category Status *</label>
                                        <select id="category_status" class="form-control" name="category_status" required>
                                            <option value="">-- Select Status --</option>
                                            <option value="1">Available</option>
                                            <option value="0">Unavailable</option>
                                        </select>
                                        <!-- <small class="text-gray-500">Choose whether this category is available or unavailable</small> -->
                                    </div>

                                    
                                    <!-- Category Image -->
                                    <div class="mb-4">
                                        <label for="category_image" class="form-label">Category Image *</label>
                                        <input id="category_image" type="file" class="form-control" accept="image/jpeg,image/png,image/gif" name="category_image" required>
                                        <!-- <small class="text-gray-500">Allowed formats: JPG, PNG, GIF (Max 5MB)</small> -->
                                    </div>

                                    <!-- Buttons -->
                                    <div class="mt-6">
                                        <button type="submit" class="btn btn-primary w-28 mr-2 mb-2" name="btnSubmit" value="Submit">
                                            <i data-lucide="check" class="w-4 h-4 mr-2"></i> Add Category
                                        </button>
                                        <a href="viewCategory.php" class="btn btn-secondary w-28 mr-2 mb-2">
                                            <i data-lucide="x" class="w-4 h-4 mr-2"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- END: Input Form -->
                </div>

                <!-- Information Panel -->
                <!-- <div class="intro-y col-span-12 lg:col-span-4">
                    <div class="intro-y box p-5">
                        <h4 class="font-medium mb-3">Information</h4>
                        <ul class="text-sm space-y-2">
                            <li class="flex items-start">
                                <i data-lucide="check-circle" class="w-4 h-4 mr-2 mt-0.5 text-green-500"></i>
                                <span>Category ID is auto-generated</span>
                            </li>
                            <li class="flex items-start">
                                <i data-lucide="check-circle" class="w-4 h-4 mr-2 mt-0.5 text-green-500"></i>
                                <span>All fields are required</span>
                            </li>
                            <li class="flex items-start">
                                <i data-lucide="check-circle" class="w-4 h-4 mr-2 mt-0.5 text-green-500"></i>
                                <span>Available categories will be visible to customers</span>
                            </li>
                            <li class="flex items-start">
                                <i data-lucide="check-circle" class="w-4 h-4 mr-2 mt-0.5 text-green-500"></i>
                                <span>You can edit categories after creation</span>
                            </li>
                        </ul>
                    </div>
                </div> -->
            </div>
        </div>
        <!-- END: Content -->
    </div>

    <!-- BEGIN: JS Assets-->
    <script src="dist/js/app.js"></script>
    <!-- END: JS Assets-->
</body>

</html>
