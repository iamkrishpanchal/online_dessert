<?php
// Ensure database connection is available
global $conn;

if (!isset($conn) || !$conn) {
    // Try to include connection from current directory
    if (file_exists(__DIR__ . '/connection.php')) {
        include __DIR__ . '/connection.php';
    } else {
        // Fallback - show error message
        echo '<div class="col-span-full bg-red-100 p-4 rounded text-red-700">Database connection not available.</div>';
        exit;
    }
}

// Verify connection is valid
if (!$conn) {
    echo '<div class="col-span-full bg-red-100 p-4 rounded text-red-700">Invalid database connection.</div>';
    exit;
}

$vendors = [];
// List of vendors to exclude
$excluded_vendors = ['krish', 'krushiv', 'honey', 'pushti'];
// Check if vendor table exists
$has_vendor_table = false;
$res = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_vendors'");
if ($res && mysqli_num_rows($res) > 0) {
    $has_vendor_table = true;
}

if ($has_vendor_table) {
    // Try to select common vendor columns
    // First detect which helper columns exist to avoid SQL errors
    $colRes = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_vendors");
    $vendorCols = [];
    $has_is_online = false;
    $has_last_active = false;
    $has_status = false;
    $has_image_path = false;
    $has_logo_path = false;
    $image_col_name = 'image_path'; // default
    $logo_col_name = 'logo_path';
    
    if ($colRes) {
        while ($crow = mysqli_fetch_assoc($colRes)) {
            $vendorCols[] = $crow['Field'];
            if ($crow['Field'] === 'is_online') $has_is_online = true;
            if ($crow['Field'] === 'status') $has_status = true;
            if ($crow['Field'] === 'last_active') $has_last_active = true;
            if ($crow['Field'] === 'image_path') $has_image_path = true;
            if ($crow['Field'] === 'logo_path') $has_logo_path = true;
            // Check for alternative image column names
            if (in_array($crow['Field'], ['image', 'profile_image', 'vendor_image', 'photo'])) {
                $has_image_path = true;
                $image_col_name = $crow['Field'];
            }
            // alternative names for logo column
            if (in_array($crow['Field'], ['logo', 'shop_logo'])) {
                $has_logo_path = true;
                $logo_col_name = $crow['Field'];
            }
        }
    }

    // Build query depending on available columns
    $select = "vendor_id, vendor_name, shop_name, phone, address, created_at";
    if ($has_image_path) {
        $select .= ", " . $image_col_name;
    }
    if ($has_logo_path) {
        $select .= ", " . $logo_col_name;
    }
    if ($has_is_online) {
        $select .= ", is_online";
    }
    if ($has_status) {
        $select .= ", status";
    }
    // Don't filter by online status - show all vendors
    $whereSql = '';

    $vquery = "SELECT " . $select . " FROM tbl_vendors" . $whereSql . " ORDER BY shop_name ASC";
    $vres = @mysqli_query($conn, $vquery);
    if ($vres) {
        while ($vrow = mysqli_fetch_assoc($vres)) {
            $id = $vrow['vendor_id'];
            $shop = $vrow['shop_name'] ?: $vrow['vendor_name'] ?: ('Vendor ' . $id);
            // Skip if vendor is in exclusion list
            if (in_array(strtolower($shop), $excluded_vendors)) {
                continue;
            }
            $contact = $vrow['phone'] ?? '';
            $address = $vrow['address'] ?? '';
            $joined = $vrow['created_at'] ?? '';
            $img = '';
            // Prefer logo if available, otherwise vendor image
            if ($has_logo_path && isset($vrow[$logo_col_name]) && !empty($vrow[$logo_col_name])) {
                $img = $vrow[$logo_col_name];
            } elseif ($has_image_path && isset($vrow[$image_col_name]) && !empty($vrow[$image_col_name])) {
                $img = $vrow[$image_col_name];
            }
            // Determine online status: if both status and is_online exist, require both
            $online = 1;
            if ($has_status && isset($vrow['status'])) {
                $st = strtolower(trim((string)$vrow['status']));
                $online = ($st === 'active' || $st === '1') ? 1 : 0;
            }
            if ($has_is_online && isset($vrow['is_online'])) {
                $online = $online && ((int)$vrow['is_online'] === 1) ? 1 : 0;
            }

            $vendors[] = [
                'id' => $id,
                'shop_name' => $shop,
                'vendor_name' => $vrow['vendor_name'] ?? '',
                'contact' => $contact,
                'address' => $address,
                'joined' => $joined,
                'image' => $img,
                'is_online' => $online
            ];
        }
    }
} else {
    // Fallback: derive vendors from tbl_product
    // First check if tbl_product table exists
    $tableRes = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_product'");
    $has_product_table = ($tableRes && mysqli_num_rows($tableRes) > 0);
    
    if ($has_product_table) {
        // Check if tbl_product has vendor identifying columns
        $colRes = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_product");
        $cols = [];
        $has_vendor_id = false;
        $has_vendor_name = false;
        
        if ($colRes) {
            while ($crow = mysqli_fetch_assoc($colRes)) {
                $cols[] = $crow['Field'];
                if ($crow['Field'] === 'vendor_id') $has_vendor_id = true;
                if ($crow['Field'] === 'vendor_name') $has_vendor_name = true;
            }
        }
        
        // If vendor columns exist, get distinct vendors from products
        if ($has_vendor_id || $has_vendor_name) {
            $selectFields = [];
            if ($has_vendor_id) $selectFields[] = 'vendor_id AS id';
            if ($has_vendor_name) $selectFields[] = 'vendor_name AS shop_name';
            
            $pquery = "SELECT DISTINCT " . implode(', ', $selectFields) . " FROM tbl_product 
                       WHERE vendor_name IS NOT NULL AND vendor_name != '' 
                       ORDER BY vendor_name ASC";
            $pres = @mysqli_query($conn, $pquery);
            if ($pres) {
                $vendor_count = 0;
                while ($prow = mysqli_fetch_assoc($pres)) {
                    if ($prow['shop_name']) {
                        // Skip if vendor is in exclusion list
                        if (in_array(strtolower($prow['shop_name']), $excluded_vendors)) {
                            continue;
                        }
                        $vendor_count++;
                        $vendors[] = [
                            'id' => $prow['id'] ?: urlencode($prow['shop_name']),
                            'shop_name' => $prow['shop_name'],
                            'contact' => '' // Contact info not available from products table
                        ];
                    }
                }
            }
        }
    }
}

// Render vendor cards
?>
<div class="vendor-section mb-8">
    <h3 class="text-2xl font-bold mb-4">Available Shops</h3>
    <div class="vendor-grid">
    <?php if (count($vendors) == 0) { ?>
        <div style="background:#f3f4f6;padding:24px;border-radius:8px;text-align:center;color:#4b5563">
            No vendors found.
        </div>
    <?php } else {
        foreach ($vendors as $v) {
            $vid = htmlspecialchars($v['id']);
            $shop = htmlspecialchars($v['shop_name']);
            $contact = htmlspecialchars($v['contact']);
            
            // Link to vendor products page
            if (is_numeric($v['id'])) {
                $href = 'vendor_products.php?vendor_id=' . $vid;
            } else {
                $href = 'vendor_products.php?vendor_name=' . $vid;
            }
            
            $vendorImgPath = '';
            if (!empty($v['image'])) {
                $vendorImgPath = 'uploads/vendors/' . $v['image'];
            }
            ?>
        <div class="vendor-card-detailed">
            <div class="vendor-card-content">
                <?php if (!empty($vendorImgPath)) { ?>
                    <img src="<?php echo htmlspecialchars($vendorImgPath); ?>" alt="<?php echo $shop; ?>" class="vendor-profile-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <?php } ?>
                <div class="vendor-no-img" style="display:<?php echo empty($vendorImgPath) ? 'flex' : 'none'; ?>">No Image</div>
                <div class="vendor-info">
                    <h3 style="display:flex;align-items:center;gap:8px;">
                        <?php echo $shop; ?>
                        <?php if (isset($v['is_online']) && (int)$v['is_online'] === 1) { ?>
                            <span class="vendor-badge online">Online</span>
                        <?php } else { ?>
                            <span class="vendor-badge offline">Offline</span>
                        <?php } ?>
                    </h3>
                    <p class="vendor-detail-row"><strong>Vendor Name:</strong> <?php echo htmlspecialchars($v['vendor_name']); ?></p>
                    <p class="vendor-detail-row"><strong>Phone:</strong> <?php echo htmlspecialchars($v['contact']); ?></p>
                    <p class="vendor-detail-row"><strong>Address:</strong> <?php echo htmlspecialchars($v['address']); ?></p>
                    <p class="vendor-detail-row"><strong>Joined:</strong> <?php echo htmlspecialchars($v['joined']); ?></p>
                    <a href="<?php echo $href; ?>" class="vendor-view-btn">View Products →</a>
                </div>
            </div>
        </div>
        <?php }
    } ?>
    </div>
</div>

<style>
.vendor-section {
    padding: 0;
}

.vendor-section h3 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    font-size: 1.8rem !important;
    font-weight: 700 !important;
    margin-bottom: 1.5rem !important;
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
}

.vendor-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    padding: 10px 0;
}

.vendor-card-detailed {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border-left: 5px solid #667eea;
}

.vendor-card-detailed:hover {
    box-shadow: 0 12px 28px rgba(102, 126, 234, 0.2);
    border-left-color: #764ba2;
    transform: translateY(-3px);
}

.vendor-card-content {
    display: flex;
    gap: 20px;
    padding: 20px;
    align-items: flex-start;
}

.vendor-profile-img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 12px;
    flex-shrink: 0;
    border: 3px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
.vendor-no-img {
    width: 140px;
    height: 140px;
    background: linear-gradient(135deg, #f5f7fa 0%, #e9ecf1 100%);
    border-radius: 12px;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 12px;
    font-weight: 600;
    border: 2px dashed #d1d5db;
}

.vendor-info {
    flex-grow: 1;
}

.vendor-info h3 {
    font-size: 18px;
    font-weight: 700;
    background: linear-gradient(120deg, #1f2937 0%, #667eea 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0 0 12px 0;
}

.vendor-detail-row {
    margin: 8px 0;
    font-size: 13px;
    color: #555;
    display: flex;
    align-items: center;
}

.vendor-detail-row strong {
    color: #333;
}


.vendor-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
    background: linear-gradient(120deg, #667eea, #764ba2) !important;
    color: white !important;
    -webkit-text-fill-color: white !important;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none !important;
    padding: 11px 20px;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    cursor: pointer;
    pointer-events: auto;
    position: relative;
    z-index: 10;
    border: 2px solid transparent;
    letter-spacing: 0.3px;
    overflow: hidden;
    white-space: nowrap;
}

.vendor-view-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.6s ease;
    z-index: -1;
}

.vendor-view-btn:hover::before {
    left: 100%;
}

.vendor-view-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 24px rgba(102, 126, 234, 0.4), 0 0 20px rgba(102, 126, 234, 0.2);
    color: white !important;
    text-decoration: none !important;
    background: linear-gradient(120deg, #764ba2, #667eea) !important;
    border-color: rgba(255,255,255,0.3);
}

.vendor-view-btn:active {
    transform: translateY(-1px) scale(0.98);
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.vendor-view-btn:focus {
    outline: none;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3), 0 0 0 3px rgba(102, 126, 234, 0.2);
}

.vendor-badge {
    font-size: 12px;
    font-weight: 700;
    padding: 5px 10px;
    border-radius: 20px;
    color: white !important;
    -webkit-text-fill-color: white !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    display: inline-block;
    white-space: nowrap;
}
.vendor-badge.online { 
    background: linear-gradient(120deg, #10b981, #059669) !important;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.vendor-badge.offline { 
    background: linear-gradient(120deg, #6b7280, #4b5563) !important;
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
}

@media (max-width: 768px) {
    .vendor-card-content {
        flex-direction: column;
        gap: 12px;
    }
    
    .vendor-profile-img,
    .vendor-no-img {
        width: 100%;
        height: 200px;
    }
}

@media (max-width: 480px) {
    .vendor-card-content {
        padding: 12px;
    }
    
    .vendor-info h3 {
        font-size: 16px;
    }
    
    .vendor-detail-row {
        font-size: 12px;
    }
}
</style>                