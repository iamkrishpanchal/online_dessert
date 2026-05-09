<?php
/**
 * Endpoint: Delete/Dismiss Notification
 * Method: POST
 * Parameters: 
 *   - notification_id: ID of notification to dismiss
 * Purpose: Allows users to manually dismiss notifications by clicking the close button
 */
session_start();
include 'connection.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$notification_id = intval($_POST['notification_id'] ?? 0);

if (!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

// Verify notification belongs to user
$verify_sql = "SELECT notification_id FROM tbl_notifications WHERE notification_id = ? AND user_id = ?";
$verify_stmt = mysqli_prepare($conn, $verify_sql);
mysqli_stmt_bind_param($verify_stmt, 'ii', $notification_id, $user_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

if (mysqli_num_rows($verify_result) === 0) {
    mysqli_stmt_close($verify_stmt);
    echo json_encode(['success' => false, 'message' => 'Notification not found or does not belong to user']);
    exit;
}

mysqli_stmt_close($verify_stmt);

// Update notification to mark as dismissed
$update_sql = "UPDATE tbl_notifications SET is_dismissed = 1 WHERE notification_id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, 'i', $notification_id);

if (mysqli_stmt_execute($update_stmt)) {
    echo json_encode(['success' => true, 'message' => 'Notification dismissed']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_stmt_close($update_stmt);
mysqli_close($conn);
