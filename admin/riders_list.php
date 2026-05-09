<?php
session_start();
include 'connection.php';

// Check admin login
if (empty($_SESSION['admin_id']) && empty($_SESSION['vendor_id'])) {
    header('Location: ../login.php');
    exit;
}

// ensure tbl_riders exists (auto-create if missing)
$tblRes = mysqli_query($conn, "SHOW TABLES LIKE 'tbl_riders'");
if (!$tblRes || mysqli_num_rows($tblRes) === 0) {
    $create = "CREATE TABLE IF NOT EXISTS tbl_riders (
        rider_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        vehicle_type VARCHAR(50) DEFAULT NULL,
        vehicle_number VARCHAR(50) DEFAULT NULL,
        latitude DECIMAL(10,7) DEFAULT NULL,
        longitude DECIMAL(10,7) DEFAULT NULL,
        is_online TINYINT(1) NOT NULL DEFAULT 0,
        status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    mysqli_query($conn, $create);
}

// fetch riders
$ridRes = mysqli_query($conn, "SELECT * FROM tbl_riders ORDER BY created_at DESC");
$riders = [];
if ($ridRes) {
    while ($r = mysqli_fetch_assoc($ridRes)) {
        $riders[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Riders - Admin</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .riders-table { 
            background: white; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
        }
        .riders-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1.5rem; 
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        .riders-header h2 { 
            margin: 0; 
            font-size: 1.8rem; 
            font-weight: 800; 
            letter-spacing: 0.02em;
            background: linear-gradient(120deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .btn-add-rider {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white !important;
            font-weight: 700;
            border-radius: 0.75rem;
            padding: 0.6rem 1.4rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.35);
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }
        .btn-add-rider::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.2) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }
        .btn-add-rider:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.45);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        @keyframes shimmer {
            0% { transform: translate(-100%, -100%) rotate(45deg); }
            100% { transform: translate(100%, 100%) rotate(45deg); }
        }
        .riders-table .table {
            border-collapse: collapse !important;
            border: none !important;
            margin: 0 !important;
            --bs-table-bg: transparent !important;
            --bs-table-border-color: transparent !important;
        }
        .riders-table .table thead {
            background: linear-gradient(110deg, #c7d2e0 0%, #d8dfe8 100%) !important;
            border-bottom: 2px solid #cbd5e1 !important;
            color: #1e293b;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .riders-table .table thead th {
            border: none !important;
            font-weight: 700;
            color: #1e293b;
            padding: 1rem !important;
            font-size: 0.95rem;
        }
        .riders-table .table tbody tr {
            border: none !important;
            background-color: #ffffff !important;
            transition: all 0.25s ease;
        }
        .riders-table .table tbody tr:nth-child(even) {
            background-color: #f8fafc !important;
        }
        .riders-table .table tbody tr td {
            border: none !important;
            padding: 0.95rem 1rem !important;
            vertical-align: middle;
            color: #475569;
            font-size: 0.95rem;
        }
        .riders-table .table tbody tr:hover {
            background: linear-gradient(90deg, #ede9fe 0%, #f0f4f8 100%) !important;
            box-shadow: inset 0 0 12px rgba(99, 110, 255, 0.1) !important;
            transform: scale(1.01);
        }
        .riders-table .table tbody tr td:first-child {
            font-weight: 700;
            color: #1e293b;
            background: rgba(99, 110, 255, 0.05);
        }
        .status-badge { 
            padding: 0.5rem 0.9rem; 
            border-radius: 6px; 
            font-size: 0.85rem; 
            font-weight: 700;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }
        .status-active { 
            background: linear-gradient(120deg, #d1fae5, #a7f3d0);
            color: #065f46; 
        }
        .status-inactive { 
            background: linear-gradient(120deg, #fecaca, #fca5a5);
            color: #7f1d1d; 
        }
        .btn-sm { 
            padding: 0.4rem 0.8rem; 
            margin: 0 2px;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-warning {
            background: linear-gradient(120deg, #f59e0b, #f97316) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25);
        }
        .btn-warning:hover {
            background: linear-gradient(120deg, #f97316, #ea580c) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
        }
        .btn-danger {
            background: linear-gradient(120deg, #ef4444, #dc2626) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.25);
        }
        .btn-danger:hover {
            background: linear-gradient(120deg, #dc2626, #b91c1c) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.35);
        }
        .alert-info {
            border: 1px solid #cfe2ff;
            background-color: #e9f7ff;
            color: #0b3f70;
            border-radius: 0.85rem;
            box-shadow: 0 2px 10px rgba(26, 115, 232, 0.1);
        }
        .riders-table {
            background: #ffffff;
            border-radius: 0.9rem;
            overflow: hidden;
            box-shadow: 0 15px 25px rgba(16, 41, 112, 0.12);
        }
        .riders-table .table tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.08) !important;
        }
        body {
            background: linear-gradient(135deg, #f4f8fc 0%, #edf5ff 100%);
            min-height: 100vh;
        }
        /* Ensure content doesn't overflow into sidebar area */
        .wrapper {
            overflow: hidden !important;
            width: 100%;
        }
    </style>
</head>
    <body class="py-0 md:py-0 dark:bg-transparent" style="background: linear-gradient(135deg, #f0f4f8, #e8ecf1);">
    <div class="flex mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content w-full">
           
            <div class="wrapper p-6">
                <div class="riders-header">
                    <h2>🚴 Delivery Riders Management</h2>
                    <a href="rider_form.php" class="btn-add-rider">+ Add New Rider</a>
                </div>

                <?php if (count($riders) === 0): ?>
                <div class="alert alert-info text-center py-5" role="alert">
                    <h5>No riders yet</h5>
                    <p>Click "Add New Rider" to add your first delivery rider.</p>
                </div>
                <?php else: ?>
                <div class="riders-table">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riders as $r): ?>
                            <tr class="rider-row">
                                <td><strong><?php echo htmlspecialchars($r['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($r['email']); ?></td>
                                <td><?php echo htmlspecialchars($r['phone']); ?></td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($r['vehicle_type'] ?? 'N/A'); ?><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($r['vehicle_number'] ?? 'N/A'); ?></span>
                                    </small>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo ($r['status'] === 'active') ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ucfirst($r['status']); ?>
                                    </span>
                                </td>
                                <td><small class="text-muted"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></small></td>
                                <td>
                                    <a href="rider_form.php?rider_id=<?php echo $r['rider_id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="post" action="toggle_rider_status.php" class="d-inline-block" onsubmit="return toggleRiderStatus(this, event);">
                                        <input type="hidden" name="rider_id" value="<?php echo $r['rider_id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $r['status'] === 'active' ? 'inactive' : 'active'; ?>">

                                    </form>
                                    <a href="delete_rider.php?rider_id=<?php echo $r['rider_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete rider? This cannot be undone.');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="dist/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleRiderStatus(form, event) {
            event.preventDefault();
            const formData = new FormData(form);

            fetch('toggle_rider_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Rider status updated successfully.');
                    location.reload();
                } else {
                    alert(data.message || 'Unable to update rider status.');
                }
            })
            .catch(() => {
                alert('Unable to update rider status at this time.');
            });

            return false;
        }
    </script>
</body>
</html>