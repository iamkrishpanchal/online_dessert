    <?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'connection.php';

// ensure discount column exists; create if missing
$hasVendorDisc = false;
$colCheck = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'vendor_discount_percent'");
if ($colCheck && mysqli_num_rows($colCheck) > 0) {
    $hasVendorDisc = true;
} else {
    // attempt to add it silently (migration may not have run yet)
    @mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN vendor_discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00");
    // re-check
    $colCheck2 = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'vendor_discount_percent'");
    if ($colCheck2 && mysqli_num_rows($colCheck2) > 0) {
        $hasVendorDisc = true;
    }
}

// Ensure status column exists for vendor status operations
$statusCheck = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'status'");
if (!$statusCheck || mysqli_num_rows($statusCheck) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
}

// Ensure is_online column exists to quickly mark vendors offline when blocked
$isOnlineCheck = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'is_online'");
if (!$isOnlineCheck || mysqli_num_rows($isOnlineCheck) === 0) {
    @mysqli_query($conn, "ALTER TABLE tbl_vendors ADD COLUMN is_online TINYINT(1) DEFAULT 0");
}

// handle approval/rejection from admin actions
if (isset($_GET['action']) && isset($_GET['vendor_id'])) {
    $action = $_GET['action'];
    $vid = intval($_GET['vendor_id']);
    if ($vid > 0) {
        if ($action === 'approve') {
            mysqli_query($conn, "UPDATE tbl_vendors SET status = 'active' WHERE vendor_id = {$vid}");
            header('Location: vendor_detail.php?filter=pending&success=Vendor+approved');
            exit;
        } elseif ($action === 'reject') {
            mysqli_query($conn, "DELETE FROM tbl_vendors WHERE vendor_id = {$vid}");
            header('Location: vendor_detail.php?filter=pending&success=Vendor+rejected+and+removed');
            exit;
        } elseif ($action === 'block') {
            mysqli_query($conn, "UPDATE tbl_vendors SET status = 'inactive', is_online = 0 WHERE vendor_id = {$vid}");
            header('Location: vendor_detail.php?filter=all&success=Vendor+blocked');
            exit;
        } elseif ($action === 'unblock') {
            mysqli_query($conn, "UPDATE tbl_vendors SET status = 'active' WHERE vendor_id = {$vid}");
            header('Location: vendor_detail.php?filter=all&success=Vendor+unblocked');
            exit;
        }
    }
}

// handle vendor view details
$viewVendor = null;
if (isset($_GET['view_vendor_id'])) {
    $viewVid = intval($_GET['view_vendor_id']);
    if ($viewVid > 0) {
        $viewStmt = mysqli_prepare($conn, "SELECT vendor_id, vendor_name, shop_name, email, phone, address, image_path, logo_path, status, created_at FROM tbl_vendors WHERE vendor_id = ? LIMIT 1");
        if ($viewStmt) {
            mysqli_stmt_bind_param($viewStmt, 'i', $viewVid);
            mysqli_stmt_execute($viewStmt);
            $viewRes = mysqli_stmt_get_result($viewStmt);
            if ($viewRes) {
                $viewVendor = mysqli_fetch_assoc($viewRes);
            }
            mysqli_stmt_close($viewStmt);
        }
    }
}

// handle vendor discount updates (only if column is available)
if ($hasVendorDisc && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_discount'])) {
    $vid = intval($_POST['vendor_id'] ?? 0);
    $disc = floatval($_POST['vendor_discount_percent'] ?? 0);
    if ($vid > 0) {
        $safe = mysqli_real_escape_string($conn, $disc);
        mysqli_query($conn, "UPDATE tbl_vendors SET vendor_discount_percent = '{$safe}' WHERE vendor_id = {$vid}");
        // reload so that changes are shown
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Fetch vendor statistics
$totalVendorsRes = mysqli_query($conn, 'SELECT COUNT(*) as total FROM tbl_vendors');
$totalVendors = mysqli_fetch_assoc($totalVendorsRes)['total'];

// Check if there's a status column to distinguish active/inactive vendors
$statusCheckRes = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'status'");
$hasStatusColumn = $statusCheckRes && mysqli_num_rows($statusCheckRes) > 0;

// Also detect is_online column (used to mark vendors who are currently logged in)
$isOnlineCheckRes = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors LIKE 'is_online'");
$hasIsOnlineColumn = $isOnlineCheckRes && mysqli_num_rows($isOnlineCheckRes) > 0;

$activeVendors = 0;
$inactiveVendors = 0;
$pendingVendors = 0;

// Prioritize is_online for active/inactive determination
if ($hasIsOnlineColumn) {
    $activeRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_vendors WHERE is_online = 1");
    $activeVendors = mysqli_fetch_assoc($activeRes)['count'] ?? 0;
    $inactiveVendors = $totalVendors - $activeVendors;
} elseif ($hasStatusColumn) {
    $activeRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_vendors WHERE status = 'active' OR status = '1'");
    $activeVendors = mysqli_fetch_assoc($activeRes)['count'];
    
    $inactiveRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_vendors WHERE status = 'inactive' OR status = '0'");
    $inactiveVendors = mysqli_fetch_assoc($inactiveRes)['count'];
} else {
    // If no status or is_online column, assume all are active
    $activeVendors = $totalVendors;
    $inactiveVendors = 0;
}

// Count pending vendor approval requests when the status column exists
if ($hasStatusColumn) {
    $pendingRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM tbl_vendors WHERE status = 'pending'");
    $pendingVendors = mysqli_fetch_assoc($pendingRes)['count'] ?? 0;
}

// Fetch vendors based on filter
$vendors = [];
$pageTitle = 'All Vendors';

// build column list for queries
$baseCols = 'vendor_id, vendor_name, shop_name, phone, address, image_path, created_at';
if ($hasVendorDisc) {
    $baseCols .= ', vendor_discount_percent';
}
if ($hasStatusColumn) {
    $baseCols .= ', status';
}
if ($hasIsOnlineColumn) {
    $baseCols .= ', is_online';
}

// Fetch using is_online for active/inactive (primary), status only for pending
if ($filter === 'pending' && $hasStatusColumn) {
    // Status column for pending approvals only
    $stmt = mysqli_prepare($conn, "SELECT {$baseCols} FROM tbl_vendors WHERE status = 'pending' ORDER BY vendor_id DESC");
    $pageTitle = 'Pending Vendor Requests';
} else if ($hasIsOnlineColumn) {
    // Use is_online for active/inactive filtering
    if ($filter === 'active') {
        $stmt = mysqli_prepare($conn, "SELECT {$baseCols} FROM tbl_vendors WHERE is_online = 1 ORDER BY vendor_id DESC");
        $pageTitle = 'Active Vendors';
    } elseif ($filter === 'inactive') {
        $stmt = mysqli_prepare($conn, "SELECT {$baseCols} FROM tbl_vendors WHERE is_online = 0 ORDER BY vendor_id DESC");
        $pageTitle = 'Inactive Vendors';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT {$baseCols} FROM tbl_vendors ORDER BY vendor_id DESC");
    }
} else if ($hasStatusColumn) {
    // Fall back to status column if no is_online
    if ($filter === 'active') {
        $stmt = mysqli_prepare($conn, "SELECT {$baseCols} FROM tbl_vendors WHERE status = 'active' OR status = '1' ORDER BY vendor_id DESC");
        $pageTitle = 'Active Vendors';
    } elseif ($filter === 'inactive') {
        $stmt = mysqli_prepare($conn, "SELECT {$baseCols} FROM tbl_vendors WHERE status = 'inactive' OR status = '0' ORDER BY vendor_id DESC");
        $pageTitle = 'Inactive Vendors';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT {$baseCols} FROM tbl_vendors ORDER BY vendor_id DESC");
    }
} else {
    $stmt = mysqli_prepare($conn, "SELECT {$baseCols} FROM tbl_vendors ORDER BY vendor_id DESC");
}

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $vendors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>All Vendors</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 3fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #606479 0%, #120d17 100%);
            padding: 24px;
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,.1);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }
        .stat-card.total {
            background: linear-gradient(135deg, #27346c 0%, #394084 100%);
        }
        .stat-card.active {
            background: linear-gradient(135deg, #1f3227 0%, #2d5240 100%);
        }
        .stat-card.inactive {
            background: linear-gradient(135deg, #792a2a 0%, #441b1b 100%);
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,.15);
        }
        .stat-card.active-filter {
            box-shadow: 0 8px 20px rgba(0,0,0,.2);
            transform: scale(1.05);
        }
        .stat-label {
            font-size: 13px;
            font-weight: 500;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .intro-y.box {
            max-width: 1260px;
            padding: 1.4rem 1.6rem;
            border-radius: 14px;
            background: linear-gradient(120deg, #e3e0e0, #e3e0e0);
            box-shadow: 0 10px 26px rgba(18, 57, 101, 0.12);
            margin: 1rem auto 1.2rem auto;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-top: 8px;
        }
        .vendors-container { padding: 20px; }
        .vendor-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .vendor-card {
            background: #fff;
            padding: 14px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,.06);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .vendor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,.12);
        }
        .vendor-details-grid {
            display: grid;
            grid-template-columns: 1fr;
            row-gap: 10px;
            column-gap: 0;
            line-height: 1.6;
        }
        .vendor-details-grid div {
            font-size: 16px;
            color: #24324b;
            line-height: 1.65;
        }
        .vendor-details-grid strong {
            color: #1d2b44;
            font-size: 17px;
        }
        .intro-y.box {
            font-size: 16px;
        }
        .vendor-img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            background: #f4f4f4;
            margin-bottom: 12px;
            display: block;
        }
        .vendor-img-placeholder {
            width: 100%;
            height: 160px;
            background: #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            margin-bottom: 12px;
        }
        .vendor-info {
            flex-grow: 1;
        }
        .vendor-info h3 {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        .vendor-info p {
            margin: 4px 0;
            font-size: 13px;
            color: #666;
        }
        .vendor-info .label {
            font-weight: 600;
            color: #444;
        }
        .vendor-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            border-top: 1px solid #eee;
            padding-top: 12px;
        }
        .vendor-actions a { /* existing rules */
            flex: 1;
            padding: 8px 12px;
            text-align: center;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-view {
            background: #e7f3ff;
            color: #0066cc;
        }
        .btn-view:hover {
            background: #0066cc;
            color: white;
        }
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        .btn-delete:hover {
            background: #dc2626;
            color: white;
        }
    
            .btn-secondary {
                background-color: #6c757d;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .btn-secondary:hover {
                background-color: #5a6268;
            }
        </style>
</head>
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
            <div class="vendors-container">
                <?php if (isset($_GET['success'])) { ?>
                    <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #10b981;">
                        ✅ <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php } else if (isset($_GET['error'])) { ?>
                    <div class="alert alert-error" style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #dc2626;">
                        ❌ <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php } ?>
                <!-- BEGIN: Vendor Statistics -->
                <div class="stats-container">
                    <a href="vendor_detail.php" class="stat-card total <?php echo $filter === '' ? 'active-filter' : ''; ?>">
                        <div class="stat-label">All Vendors</div>
                        <div class="stat-value"><?php echo $totalVendors; ?></div>
                    </a>
                    <?php if ($hasStatusColumn || $hasIsOnlineColumn) { ?>
                        <a href="vendor_detail.php?filter=pending" class="stat-card pending <?php echo $filter === 'pending' ? 'active-filter' : ''; ?>" style="background: linear-gradient(135deg, #7c528a 0%, #3b2e63 100%);">
                            <div class="stat-label">Pending Approvals</div>
                            <div class="stat-value"><?php echo $pendingVendors; ?></div>
                        </a>
                        <a href="vendor_detail.php?filter=active" class="stat-card active <?php echo $filter === 'active' ? 'active-filter' : ''; ?>">
                            <div class="stat-label">Active Vendors</div>
                            <div class="stat-value"><?php echo $activeVendors; ?></div>
                        </a>
                        <a href="vendor_detail.php?filter=inactive" class="stat-card inactive <?php echo $filter === 'inactive' ? 'active-filter' : ''; ?>">
                            <div class="stat-label">Inactive Vendors</div>
                            <div class="stat-value"><?php echo $inactiveVendors; ?></div>
                        </a>
                    <?php } else { ?>
                        <div class="stat-card active">
                            <div class="stat-label">Active Vendors</div>
                            <div class="stat-value"><?php echo $activeVendors; ?></div>
                        </div>
                        <div class="stat-card inactive">
                            <div class="stat-label">Inactive Vendors</div>
                            <div class="stat-value"><?php echo $inactiveVendors; ?></div>
                        </div>
                    <?php } ?>
                </div>
                <!-- END: Vendor Statistics -->

                <div class="intro-y flex items-center mt-8">
                    <h2 class="text-lg font-medium mr-auto"><?php echo $pageTitle; ?></h2>
                </div>

                <?php if ($viewVendor) { ?>
                    <div class="intro-y box p-4 mt-4">
                        <div class="flex justify-between items-center mb-3">
                            <div>
                                <div class="text-sm text-gray-500">Vendor Details</div>
                                <div class="font-semibold text-lg" style="margin-bottom: 8px;"><?php echo htmlspecialchars($viewVendor['shop_name'] ?: $viewVendor['vendor_name']); ?></div>
                            </div>
                            
                            
                        </div>
                        <div class="vendor-details-grid">
                            <div><strong>Name:</strong> <?php echo htmlspecialchars($viewVendor['vendor_name']); ?></div>
                            <div><strong>Phone:</strong> <?php echo htmlspecialchars($viewVendor['phone']); ?></div>
                            <div><strong>Address:</strong> <?php echo htmlspecialchars($viewVendor['address']); ?></div>
                            <div><strong>Joined:</strong> <?php echo htmlspecialchars($viewVendor['created_at']); ?></div>
                        </div>
                        <div class="flex gap-2" style="align-items: center;">
                                <a class="btn btn-primary" href="vendor_detail.php?filter=pending" style="margin-top: 4px;">Back to requests</a>
                                <?php
                                $vendorStatus = strtolower($viewVendor['status'] ?? '');
                                if ($vendorStatus === 'pending') {
                                    echo '<a class="btn btn-success" href="vendor_detail.php?action=approve&vendor_id=' . $viewVendor['vendor_id'] . '">Accept</a>';
                                    echo '<a class="btn btn-danger" href="vendor_detail.php?action=reject&vendor_id=' . $viewVendor['vendor_id'] . '">Reject</a>';
                                } elseif ($vendorStatus === 'active' || $vendorStatus === '1') {
                                    echo '<a class="btn btn-danger" href="vendor_detail.php?action=block&vendor_id=' . $viewVendor['vendor_id'] . '" onclick="return confirm(\'Are you sure you want to block this vendor?\');">Block</a>';
                                } elseif ($vendorStatus === 'inactive' || $vendorStatus === '0' || $vendorStatus === 'suspended') {
                                    echo '<a class="btn btn-success" href="vendor_detail.php?action=unblock&vendor_id=' . $viewVendor['vendor_id'] . '" onclick="return confirm(\'Are you sure you want to unblock this vendor?\');">Unblock</a>';
                                } else {
                                    // For any unknown state, offer block/unblock fallback
                                    echo '<a class="btn btn-danger" href="vendor_detail.php?action=block&vendor_id=' . $viewVendor['vendor_id'] . '" onclick="return confirm(\'Are you sure you want to block this vendor?\');">Block</a>';
                                }
                                ?>
                            </div>
                    </div>
                <?php } ?>

                <?php if (empty($vendors)) { ?>
                    <p class="text-gray-600 mt-6">No vendors found.</p>
                <?php } else { ?>
                    <div class="vendor-cards-grid">
                        <?php foreach ($vendors as $vendor) { 
                            $vendorImgPath = !empty($vendor['image_path']) ? 'uploads/vendors/' . $vendor['image_path'] : null;
                        ?>
                            <div class="vendor-card">
                                <?php if ($vendorImgPath) { ?>
                                    <img class="vendor-img" src="<?php echo htmlspecialchars($vendorImgPath); ?>" alt="<?php echo htmlspecialchars($vendor['shop_name']); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <?php } ?>
                                <div class="vendor-img-placeholder" style="<?php echo empty($vendorImgPath) ? 'display:flex' : 'display:none'; ?>">No Image</div>
                                
                                <div class="vendor-info">
                                    <h3><?php echo htmlspecialchars($vendor['shop_name'] ?: $vendor['vendor_name']); ?></h3>
                                    <p><span class="label">Vendor Name:</span> <?php echo htmlspecialchars($vendor['vendor_name']); ?></p>
                                    <p><span class="label">Phone:</span> <?php echo htmlspecialchars($vendor['phone']); ?></p>
                                    <p><span class="label">Address:</span> <?php echo htmlspecialchars($vendor['address']); ?></p>
                                    <p><span class="label">Joined:</span> <?php echo htmlspecialchars($vendor['created_at']); ?></p>
                                    
                                    <!-- <form method="post" style="margin-top:8px;">
                                        <input type="hidden" name="vendor_id" value="<?php echo $vendor['vendor_id']; ?>">
                                        <label style="font-size:13px;">Discount %
                                            <input type="number" step="0.01" name="vendor_discount_percent" value="<?php echo htmlspecialchars($vendor['vendor_discount_percent'] ?? 0); ?>" style="width:80px;margin-left:4px;"> 
                                        </label>
                                        <button type="submit" name="update_discount" class="btn btn-secondary" style="font-size:12px;padding:3px 6px;margin-left:4px;">Set</button>
                                    </form> -->
                                </div>

                                <div class="vendor-actions">
                                    <?php
                                        $cstatus = strtolower($vendor['status'] ?? '');
                                        if ($cstatus === 'pending') {
                                            echo '<a href="vendor_detail.php?action=approve&vendor_id=' . $vendor['vendor_id'] . '" class="btn-view" style="background:#d1fae5;color:#065f46;">Accept</a>';
                                            echo '<a href="vendor_detail.php?action=reject&vendor_id=' . $vendor['vendor_id'] . '" class="btn-delete" style="background:#fee2e2;color:#991b1b;">Reject</a>';
                                        } elseif ($cstatus === 'active' || $cstatus === '1') {
                                            echo '<a href="vendor_detail.php?action=block&vendor_id=' . $vendor['vendor_id'] . '" class="btn-delete" style="background:#f97316;color:#ffffff;" onclick="return confirm(\'Are you sure you want to block this vendor?\');">Block</a>';
                                        } elseif ($cstatus === 'inactive' || $cstatus === '0' || $cstatus === 'suspended') {
                                            echo '<a href="vendor_detail.php?action=unblock&vendor_id=' . $vendor['vendor_id'] . '" class="btn-view" style="background:#65a30d;color:#ffffff;" onclick="return confirm(\'Are you sure you want to unblock this vendor?\');">Unblock</a>';
                                        }
                                    ?>
                                    <?php $vendorDisplayName = $vendor['shop_name'] ?: $vendor['vendor_name']; ?>
                                    <a href="deleteVendor.php?vendor_id=<?php echo (int)$vendor['vendor_id']; ?>" onclick="return confirm('Are you sure you want to delete vendor: <?php echo addslashes($vendorDisplayName); ?>? This action cannot be undone.');" class="btn-delete" style="flex: 1; padding: 8px 12px; text-align: center; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s;">Delete</a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>

        </div>
        <!-- END: Content -->
    </div>

    <!-- BEGIN: JS Assets-->
    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
    <script src="dist/js/app.js"></script>
    <script>
        function deleteVendor(vendorId, vendorName) {
            if (confirm('Are you sure you want to delete vendor: ' + vendorName + '?\n\nThis action cannot be undone.')) {
                window.location.href = 'deleteVendor.php?vendor_id=' + vendorId;
            }
        }
    </script>
    <!-- END: JS Assets-->
</body>
</html>
