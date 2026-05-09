<?php
session_start();
// If a vendor is logged in via this endpoint, mark them offline/inactive as well
if (isset($_SESSION['vendor_id']) && is_numeric($_SESSION['vendor_id'])) {
	include 'connection.php';
	$vid = (int)$_SESSION['vendor_id'];
	// Update last_active to track logout, but do NOT change is_online
	// (vendor should manually click Inactive button to go offline)
	@mysqli_query($conn, "UPDATE tbl_vendors SET last_active = NOW() WHERE vendor_id = $vid");
	// Do not set status column to inactive either — only manual Inactive button should do this
		// Insert audit logout row
		$createAudit = "CREATE TABLE IF NOT EXISTS vendor_audit (
				id INT AUTO_INCREMENT PRIMARY KEY,
				vendor_id INT NOT NULL,
				action VARCHAR(32) NOT NULL,
				ip VARCHAR(45) DEFAULT NULL,
				user_agent TEXT DEFAULT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
		@mysqli_query($conn, $createAudit);
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$stmtLog = mysqli_prepare($conn, "INSERT INTO vendor_audit (vendor_id, action, ip, user_agent) VALUES (?, 'logout', ?, ?)");
		if ($stmtLog) { mysqli_stmt_bind_param($stmtLog, 'iss', $vid, $ip, $ua); @mysqli_stmt_execute($stmtLog); @mysqli_stmt_close($stmtLog); }
}
session_destroy();
header('Location: index.php');
exit;
?>