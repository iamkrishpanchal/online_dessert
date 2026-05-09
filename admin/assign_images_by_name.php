<?php
// Admin tool — assign images to products based on product name keywords
// Requires admin login
include 'session.php';
include 'connection.php';

// Feature removed — prevent accidental execution and redirect back to vendors
header('Location: vendor_detail.php?msg=feature_removed');
exit;

// keyword => image path (use files that already exist in the repo)
$mapping = [
    ['keywords' => ['cookie','cookies'], 'image' => 'images/chocolat/b3ac7733c4c0f81d13b5024bebae3408.jpg'],
    ['keywords' => ['pancake','pancakes'], 'image' => 'images/chocolat/pancake.jpg'],
    ['keywords' => ['waffle','waffles'], 'image' => 'images/chocolat/waffle.jpeg'],
    ['keywords' => ['cake','cakes','cheesecake'], 'image' => 'images/chocolat/d9161098cfb44018eb751a017e9557c1.jpg'],
    ['keywords' => ['donut','donuts'], 'image' => 'images/chocolat/858fbe5ead0dff780cf58ffdb0725096.jpg'],
    ['keywords' => ['pastri','pastries','pastry'], 'image' => 'images/chocolat/pastries.jpg'],
    // fallback mapping for 'chocolate' flavored items
    ['keywords' => ['chocolate'], 'image' => 'images/chocolat/d9161098cfb44018eb751a017e9557c1.jpg'],
];

// Find target products (for single vendor or all products)
if ($globalRun) {
    $prodSql = "SELECT product_id, product_name, product_image FROM tbl_products";
    $prodStmt = mysqli_prepare($conn, $prodSql);
    mysqli_stmt_execute($prodStmt);
    $pres = mysqli_stmt_get_result($prodStmt);
    $products = $pres ? mysqli_fetch_all($pres, MYSQLI_ASSOC) : [];
} else {
    $prodSql = "SELECT product_id, product_name, product_image FROM tbl_products WHERE vendor_id = ?";
    $prodStmt = mysqli_prepare($conn, $prodSql);
    mysqli_stmt_bind_param($prodStmt, 'i', $vendor_id);
    mysqli_stmt_execute($prodStmt);
    $pres = mysqli_stmt_get_result($prodStmt);
    $products = $pres ? mysqli_fetch_all($pres, MYSQLI_ASSOC) : [];
} 

$updates = [];
$skipped = [];

foreach ($products as $p) {
    $pid = intval($p['product_id']);
    $pname = strtolower($p['product_name'] ?? '');
    $curImg = trim((string)($p['product_image'] ?? ''));

    // check if current image already points to an existing file or is a valid remote URL
    $hasImageFile = false;
    if ($curImg !== '') {
        if (preg_match('#^https?://#i', $curImg)) {
            $hasImageFile = true;
        } else {
            $candidates = [
                __DIR__ . '/' . $curImg,
                __DIR__ . '/uploads/' . $curImg,
                __DIR__ . '/vendor/uploads/' . $curImg,
                __DIR__ . '/uploads/products/' . $curImg,
                __DIR__ . '/user/' . $curImg,
            ];
            foreach ($candidates as $cand) { if (file_exists($cand)) { $hasImageFile = true; break; } }
        }
    }
    if ($hasImageFile) { $skipped[] = [ 'id'=>$pid, 'name'=>$p['product_name'], 'reason'=>'already-has-image' ]; continue; }

    // try to match mapping
    $selected = null;
    foreach ($mapping as $m) {
        foreach ($m['keywords'] as $kw) {
            if ($kw !== '' && strpos($pname, $kw) !== false) { $selected = $m['image']; break 2; }
        }
    }

    if ($selected) {
        $u = mysqli_prepare($conn, "UPDATE tbl_products SET product_image = ? WHERE product_id = ?");
        mysqli_stmt_bind_param($u, 'si', $selected, $pid);
        mysqli_stmt_execute($u);
        if (mysqli_stmt_affected_rows($u) >= 0) {
            $updates[] = ['id'=>$pid, 'name'=>$p['product_name'], 'image'=>$selected];
        }
        mysqli_stmt_close($u);
    } else {
        $skipped[] = [ 'id'=>$pid, 'name'=>$p['product_name'], 'reason'=>'no-mapping' ];
    }
}

// Show results and provide link back
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Assign Images — Results</title>
<link rel="stylesheet" href="dist/css/app.css"></head><body class="py-5 md:py-0 bg-black/[0.15] dark:bg-transparent">
<div class="flex mt-[4.7rem] md:mt-0 overflow-hidden">
    <?php include 'sideMenu.php'; ?>
    <div class="content">
        <div class="p-6">
            <?php if ($globalRun): ?>
                <a href="vendor_detail.php" class="btn btn-secondary mb-4">← Back to vendors</a>
                <h2 class="text-lg font-medium">Auto-assign images (global run)</h2>
            <?php else: ?>
                <a href="vendor_products.php?vendor_id=<?php echo $vendor_id; ?>" class="btn btn-secondary mb-4">← Back</a>
                <h2 class="text-lg font-medium">Auto-assign images for vendor #<?php echo intval($vendor_id); ?></h2>
            <?php endif; ?>
            <p class="mt-3">Updated <strong><?php echo count($updates); ?></strong> products. Skipped <strong><?php echo count($skipped); ?></strong>.</p>

            <?php if (!empty($updates)): ?>
                <div class="mt-4">
                    <h4>Updates</h4>
                    <ul>
                        <?php foreach ($updates as $u): ?>
                            <li><?php echo htmlspecialchars($u['name']); ?> — <code><?php echo htmlspecialchars($u['image']); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($skipped)): ?>
                <div class="mt-4">
                    <h4>Skipped</h4>
                    <ul>
                        <?php foreach ($skipped as $s): ?>
                            <li><?php echo htmlspecialchars($s['name']); ?> — <?php echo htmlspecialchars($s['reason']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mt-6">
                <a href="vendor_products.php?vendor_id=<?php echo $vendor_id; ?>" class="btn btn-primary">Back to vendor products</a>
            </div>
        </div>
    </div>
</div>
<script src="dist/js/app.js"></script>
</body></html>
<?php
mysqli_close($conn);
exit;
?>