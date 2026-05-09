<?php
session_start();
include 'connection.php';

// Check admin login
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    header('Location: ../login.php');
    exit;
}

$id = intval($_GET['rider_id'] ?? 0);

if ($id > 0) {
    // Delete the rider
    $del = mysqli_prepare($conn, "DELETE FROM tbl_riders WHERE rider_id=?");
    if ($del) {
        mysqli_stmt_bind_param($del,'i',$id);
        if (mysqli_stmt_execute($del)) {
            // Success
        }
        mysqli_stmt_close($del);
    }
}

// Redirect back to riders list
header('Location: riders_list.php');
exit;
?>
