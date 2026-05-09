<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Include database connection
include 'connection.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Check if is_dismissed column exists
    $checkColumn = @mysqli_query($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                                         WHERE TABLE_SCHEMA = DATABASE() 
                                         AND TABLE_NAME = 'tbl_notifications' 
                                         AND COLUMN_NAME = 'is_dismissed'");
    
    if ($checkColumn && mysqli_num_rows($checkColumn) > 0) {
        // Column exists, use it
        $query = "UPDATE tbl_notifications 
                  SET is_dismissed = 1 
                  WHERE user_id = ? 
                  AND is_dismissed = 0";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications cleared',
                'cleared_count' => $affected
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            $stmt->close();
        }
    } else {
        // Column doesn't exist, delete notifications
        $query = "DELETE FROM tbl_notifications WHERE user_id = ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications cleared',
                'cleared_count' => $affected
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
            $stmt->close();
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
