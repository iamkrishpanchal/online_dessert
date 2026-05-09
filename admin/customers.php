<?php
session_start();
include 'connection.php';

// Only admin can access
if (empty($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Fetch user list (include last known delivery address if user address is empty)
$users = [];
$query = "SELECT u.user_id, u.user_name, u.email, u.phone, 
                 COALESCE(NULLIF(u.address, ''), o.delivery_address) AS address, 
                 u.created_at 
          FROM tbl_users u
          LEFT JOIN (
              SELECT o.user_id, o.delivery_address
              FROM tbl_orders o
              JOIN (
                  SELECT user_id, MAX(created_at) AS latest
                  FROM tbl_orders
                  WHERE delivery_address IS NOT NULL AND delivery_address != ''
                  GROUP BY user_id
              ) latest_order ON latest_order.user_id = o.user_id AND latest_order.latest = o.created_at
          ) o ON o.user_id = u.user_id
          ORDER BY u.created_at DESC";
$res = mysqli_query($conn, $query);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer List - Dessert Magic</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        .wrapper {
            background: linear-gradient(135deg, #f8fafc 0%, #f0f4f8 100%);
        }
        
        .intro-y.box {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(30, 41, 59, 0.12);
            overflow: hidden;
        }
        
        .intro-y.box .flex {
            background: linear-gradient(120deg, #1e293b 0%, #334155 100%);
            color: white;
            font-weight: 700;
            letter-spacing: 0.05em;
        }
        
        .intro-y.box .flex h2 {
            color: white;
            font-size: 1.1rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table thead {
            background: linear-gradient(110deg, #c7d2e0 0%, #d8dfe8 100%);
            color: #1e293b;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        
        .table thead th {
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #cbd5e1;
            font-size: 0.95rem;
        }
        
        .table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.25s ease;
            background-color: #ffffff;
        }
        
        .table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .table tbody tr:hover {
            background: linear-gradient(90deg, #ede9fe 0%, #f0f4f8 100%);
            box-shadow: inset 0 0 12px rgba(99, 110, 255, 0.1);
            transform: scale(1.01);
        }
        
        .table tbody td {
            padding: 0.95rem 1rem;
            color: #475569;
            font-size: 0.95rem;
            vertical-align: middle;
        }
        
        .table tbody td:first-child {
            font-weight: 700;
            color: #1e293b;
            background: rgba(99, 110, 255, 0.05);
        }
        
        .no-data {
            padding: 2rem;
            text-align: center;
            color: #64748b;
            font-size: 1.1rem;
        }
        
        .customer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding: 0 0.5rem;
        }
        
        .customer-header h2 {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(120deg, #1e293b 0%, #475569 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
    </style>
<body class="py-0 md:py-0 bg-gradient-to-br from-[#f0f4f8] to-[#e8ecf1]">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content">
            <div class="wrapper p-6">
                <div class="customer-header">
                    <h2>👥 Customer List</h2>
                </div>

                <div class="intro-y box mt-5">
                    <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                        <h2 class="font-medium text-base mr-auto">All Registered Users</h2>
                    </div>
                    <div class="p-5">
                        <div class="overflow-x-auto">
                            <?php if (count($users) === 0): ?>
                                <div class="no-data">No customers found.</div>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Address</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo intval($user['user_id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($user['address'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($user['created_at'] ?? ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="dist/js/app.js"></script>
</body>
</html>
