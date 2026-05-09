<?php
session_start();
include 'connection.php';

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect if not logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch user data
$query = "SELECT * FROM tbl_users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// load recent notifications for display (optional)
$notifications = [];
$notif_stmt = mysqli_prepare($conn, "SELECT n.notification_id, n.title, n.message, n.status, n.created_at
        FROM tbl_notifications n
        LEFT JOIN tbl_orders o ON n.order_id = o.order_id
        WHERE n.user_id = ?
          AND n.order_id IS NOT NULL
          AND (o.order_status IS NULL OR o.order_status <> 'Completed')
          AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ORDER BY n.created_at DESC
        LIMIT 20");
if ($notif_stmt) {
    mysqli_stmt_bind_param($notif_stmt, 'i', $user_id);
    mysqli_stmt_execute($notif_stmt);
    $notif_res = mysqli_stmt_get_result($notif_stmt);
    if ($notif_res) {
        $notifications = mysqli_fetch_all($notif_res, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($notif_stmt);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        $update_query = "UPDATE tbl_users SET user_name = ?, phone = ?, address = ? WHERE user_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'sssi', $name, $phone, $address, $user_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $success = 'Profile updated successfully!';
            $_SESSION['user_name'] = $name;
            // Refresh user data with a new query
            $refresh_query = "SELECT * FROM tbl_users WHERE user_id = ?";
            $refresh_stmt = mysqli_prepare($conn, $refresh_query);
            mysqli_stmt_bind_param($refresh_stmt, 'i', $user_id);
            mysqli_stmt_execute($refresh_stmt);
            $refresh_result = mysqli_stmt_get_result($refresh_stmt);
            $user = mysqli_fetch_assoc($refresh_result);
            mysqli_stmt_close($refresh_stmt);
        } else {
            $error = 'Failed to update profile';
        }
        mysqli_stmt_close($update_stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FoodMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>

<main class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Profile</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['user_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" value="<?php echo htmlspecialchars($user['city'] ?? 'Surat'); ?>" disabled>
                            <small class="text-muted">City cannot be changed</small>
                        </div>
                        
                        <div class="d-grid gap-2 d-sm-flex">
                            <button type="submit" class="btn btn-success">Update Profile</button>
                            <a href="index.php" class="btn btn-secondary">Back to Home</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Ensure profile page does not load or display orders
document.addEventListener('DOMContentLoaded', function(){
    // Remove any order-related elements that might be injected
    document.querySelectorAll('[class*="order"], [id*="order"]').forEach(function(el){
        if(el.closest('main')) el.remove();
    });
});
</script>
</body>
</html>
