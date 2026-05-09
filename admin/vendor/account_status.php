<?php
include 'session.php';
include 'connection.php';

$vendor_id = intval($_SESSION['vendor_id']);

// Fetch vendor info
$vendor = null;
$vendorRes = mysqli_query($conn, "SELECT vendor_id, vendor_name, shop_name, email, phone, status, created_at FROM tbl_vendors WHERE vendor_id = {$vendor_id} LIMIT 1");
if ($vendorRes) {
    $vendor = mysqli_fetch_assoc($vendorRes);
}

// Friendly status labels
$statusLabel = 'Unknown';
if (!empty($vendor['status'])) {
    $statusMap = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'suspended' => 'Suspended',
        'pending' => 'Pending Approval'
    ];
    $statusKey = strtolower(trim($vendor['status']));
    $statusLabel = $statusMap[$statusKey] ?? ucfirst($statusKey);
}

?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Status - Dessert Magic</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        .admin-page { background: #f3f7fd; color: #1d2a4c; }
        .admin-page .content { padding: 1.25rem; }
        .admin-page .wrapper { max-width: 1160px; margin: 0 auto; }
        .admin-page .intro-y.box { border-radius: 16px; border: 1px solid #dde5f5; box-shadow: 0 10px 28px rgba(16, 36, 78, 0.12); background: #ffffff; }
        .admin-page .intro-y.box .p-5 { padding: 1.2rem; }
        .admin-page .top-bar { border-bottom: 1px solid #dde5f4; background: #ffffff; }
        .admin-page .table, .admin-page .box table { border: 1px solid #e7ecf7; border-radius: 10px; }
        .admin-page .table th, .admin-page .table td { padding: 0.8rem 0.95rem; }
        .admin-page .no-data { padding: 1rem; color: #5a6f93; }
        .admin-page .dashboard-metric { border: 1px solid #e8edfc; background: #fff; border-radius: 12px; }
        .admin-page .dashboard-metric h3 { margin-bottom: .3rem; }
    </style>
</head>
<body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent admin-page">
    <div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
        <?php include 'sideMenu.php'; ?>
        <div class="content">
            <!-- BEGIN: Top Bar -->
            <?php include 'topBar.php' ?>
            <!-- END: Top Bar -->
            <div class="wrapper p-6">
                <div class="intro-y flex items-center mt-8">
                    <h2 class="text-2xl font-medium mr-auto flex items-center gap-2">
                        <i data-lucide="user-check" class="w-6 h-6"></i>
                        Account Status
                    </h2>
                </div>

                <div class="intro-y box mt-5">
                    <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                        <h2 class="font-medium text-2xl mr-auto flex items-center gap-2">
                        <i data-lucide="info" class="w-6 h-6"></i>
                        Vendor Details
                    </h2>
                    </div>
                    <div class="p-5">
                        <?php if (!$vendor): ?>
                            <div class="text-center py-10">Unable to load vendor details.</div>
                        <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 bg-white rounded shadow-sm">
                                <div class="text-sm text-gray-500">Vendor Name</div>
                                <div class="text-xl font-bold mt-2"><?php echo htmlspecialchars($vendor['vendor_name'] ?? ''); ?></div>
                            </div>
                            <div class="p-4 bg-white rounded shadow-sm">
                                <div class="text-sm text-gray-500">Shop Name</div>
                                <div class="text-xl font-bold mt-2"><?php echo htmlspecialchars($vendor['shop_name'] ?? ''); ?></div>
                            </div>
                            <div class="p-4 bg-white rounded shadow-sm">
                                <div class="text-sm text-gray-500">Email</div>
                                <div class="text-xl font-bold mt-2"><?php echo htmlspecialchars($vendor['email'] ?? ''); ?></div>
                            </div>
                            <div class="p-4 bg-white rounded shadow-sm">
                                <div class="text-sm text-gray-500">Phone</div>
                                <div class="text-xl font-bold mt-2"><?php echo htmlspecialchars($vendor['phone'] ?? ''); ?></div>
                            </div>
                            <div class="p-4 bg-white rounded shadow-sm">
                                <div class="text-sm text-gray-500">Account Status</div>
                                <div class="text-xl font-bold mt-2"><?php echo htmlspecialchars($statusLabel); ?></div>
                            </div>
                            <div class="p-4 bg-white rounded shadow-sm">
                                <div class="text-sm text-gray-500">Joined</div>
                                <div class="text-xl font-bold mt-2"><?php echo htmlspecialchars($vendor['created_at'] ?? ''); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="dist/js/app.js"></script>
</body>
</html>
