<?php
// This migration ensures tbl_notifications has the columns needed for vendor and admin notifications.

$conn = mysqli_connect('localhost', 'root', '', 'online_dessert');
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

function addNotificationColumn($conn, $columnName, $columnDefinition, $indexName = null) {
    $check = "SHOW COLUMNS FROM tbl_notifications LIKE '" . $columnName . "'";
    $result = mysqli_query($conn, $check);
    if (!$result) {
        die('Error checking notification table: ' . mysqli_error($conn));
    }

    if (mysqli_num_rows($result) === 0) {
        echo "Adding {$columnName} to tbl_notifications...\n";
        $alter = "ALTER TABLE tbl_notifications ADD COLUMN {$columnDefinition}";
        if ($indexName) {
            $alter .= ", ADD INDEX {$indexName} ({$columnName})";
        }
        if (mysqli_query($conn, $alter)) {
            echo "{$columnName} added successfully.\n";
        } else {
            echo "Failed to add {$columnName}: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "{$columnName} column already exists in tbl_notifications.\n";
    }
}

addNotificationColumn($conn, 'vendor_id', 'vendor_id INT DEFAULT NULL', 'notif_vendor_idx');
addNotificationColumn($conn, 'admin_id', 'admin_id INT DEFAULT NULL', 'notif_admin_idx');
addNotificationColumn($conn, 'updated_at', 'updated_at TIMESTAMP NULL DEFAULT NULL');

// Ensure notifications can be created for vendors/admins without a user_id.
$checkUserNullable = mysqli_query($conn, "SHOW COLUMNS FROM tbl_notifications LIKE 'user_id'");
if ($checkUserNullable && mysqli_num_rows($checkUserNullable) > 0) {
    $userColumn = mysqli_fetch_assoc($checkUserNullable);
    if (strcasecmp($userColumn['Null'], 'YES') !== 0) {
        echo "Altering tbl_notifications.user_id to allow NULL...\n";
        $alterUser = "ALTER TABLE tbl_notifications MODIFY user_id INT DEFAULT NULL";
        if (mysqli_query($conn, $alterUser)) {
            echo "tbl_notifications.user_id now allows NULL.\n";
        } else {
            echo "Failed to alter user_id: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "tbl_notifications.user_id already nullable.\n";
    }
}

mysqli_close($conn);
