<?php
// WARNING: destructive — deletes products that have no valid image file.
// Run only if you understand the consequences.

include 'user/connection.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Deleting products that are missing images</h2>\n";

// 1) collect products and determine which are "missing image"
$rows = mysqli_query($conn, "SELECT product_id, product_name, product_image FROM tbl_products");
$products = $rows ? mysqli_fetch_all($rows, MYSQLI_ASSOC) : [];

$toDelete = [];
$report = [];

foreach ($products as $p) {
    $pid = intval($p['product_id']);
    $pname = $p['product_name'] ?? '';
    $pimg = trim((string)($p['product_image'] ?? ''));

    // treat empty product_image as missing
    if ($pimg === '') {
        $toDelete[] = $pid;
        $report[$pid] = ['name' => $pname, 'image' => $pimg, 'reason' => 'empty image field'];
        continue;
    }

    // remote URLs are considered "present" (do not delete)
    if (preg_match('#^https?://#i', $pimg)) {
        continue;
    }

    // check common candidate locations for the referenced file
    $basename = basename($pimg);
    $candidates = [
        __DIR__ . '/admin/vendor/uploads/' . $basename,
        __DIR__ . '/admin/uploads/' . $basename,
        __DIR__ . '/uploads/vendors/' . $basename,
        __DIR__ . '/uploads/products/' . $basename,
        __DIR__ . '/user/uploads/products/' . $basename,
        __DIR__ . '/uploads/' . $basename,
        __DIR__ . '/user/uploads/' . $basename,
        __DIR__ . '/' . ltrim($pimg, '/'),
    ];

    $found = false;
    foreach ($candidates as $cand) {
        if (file_exists($cand)) { $found = true; break; }
    }

    if (!$found) {
        $toDelete[] = $pid;
        $report[$pid] = ['name' => $pname, 'image' => $pimg, 'reason' => 'referenced file not found'];
    }
}

if (empty($toDelete)) {
    echo "<p>No products missing images were found. Nothing to delete.</p>\n";
    mysqli_close($conn);
    exit;
}

echo "<p>Found <strong>" . count($toDelete) . "</strong> products to delete.</p>\n";

// 2) perform DELETE (single query)
$idList = implode(',', array_map('intval', $toDelete));
$delSql = "DELETE FROM tbl_products WHERE product_id IN ($idList)";
$delRes = mysqli_query($conn, $delSql);
if ($delRes === false) {
    echo "<p style='color:red;'>DELETE failed: " . htmlspecialchars(mysqli_error($conn)) . "</p>\n";
    mysqli_close($conn);
    exit;
}
$deleted = mysqli_affected_rows($conn);

echo "<p style='color:green;'>Deleted products: $deleted</p>\n";

// 3) attempt to remove any leftover image files that match the referenced names (only if they exist and are unreferenced)
$deletedFiles = 0;
foreach ($report as $pid => $meta) {
    $img = $meta['image'];
    if (!$img) continue;
    if (preg_match('#^https?://#i', $img)) continue;
    $basename = basename($img);
    $candidates = [
        __DIR__ . '/admin/vendor/uploads/' . $basename,
        __DIR__ . '/admin/uploads/' . $basename,
        __DIR__ . '/uploads/vendors/' . $basename,
        __DIR__ . '/uploads/products/' . $basename,
        __DIR__ . '/user/uploads/products/' . $basename,
        __DIR__ . '/uploads/' . $basename,
        __DIR__ . '/user/uploads/' . $basename,
        __DIR__ . '/' . ltrim($img, '/'),
    ];

    foreach ($candidates as $cand) {
        if (file_exists($cand)) {
            // ensure no other product references the same filename
            $safeCheck = mysqli_query($conn, "SELECT COUNT(*) as c FROM tbl_products WHERE product_image LIKE '" . mysqli_real_escape_string($conn, $basename) . "' OR product_image LIKE '%" . mysqli_real_escape_string($conn, $basename) . "%' LIMIT 1");
            $countRow = $safeCheck ? mysqli_fetch_assoc($safeCheck) : null;
            $countRef = intval($countRow['c'] ?? 0);
            if ($countRef <= 0) {
                @unlink($cand);
                $deletedFiles++;
                echo "<div>Deleted file: " . htmlspecialchars($cand) . "</div>\n";
            }
            break; // stop after first match
        }
    }
}

echo "<p>Deleted image files: $deletedFiles</p>\n";

// 4) final summary
echo "<h3>Summary</h3>\n";
echo "<ul>\n";
foreach ($report as $pid => $m) {
    echo "<li>Product ID $pid — " . htmlspecialchars($m['name']) . " — reason: " . htmlspecialchars($m['reason']) . "</li>\n";
}
echo "</ul>\n";

mysqli_close($conn);

?>