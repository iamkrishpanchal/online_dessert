<?php
// debug_show_category_data.php
// Usage: debug_show_category_data.php?id=14
// Shows sample rows from tbl_categories, tbl_vendors and joins to help debugging.

include __DIR__ . '/user/connection.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Debug: Category / Vendor Data</h2>";

if (!$conn) {
    echo "<p style='color:red'>DB connection failed: " . mysqli_connect_error() . "</p>";
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

function show_table($title, $res) {
    echo "<h3>".htmlspecialchars($title)."</h3>";
    if (!$res) { echo "<pre style='color:red'>Query failed</pre>"; return; }
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }
    if (empty($rows)) { echo "<p><em>No rows</em></p>"; return; }
    echo "<table border=1 cellpadding=6 cellspacing=0>";
    // header
    echo "<tr>";
    foreach (array_keys($rows[0]) as $h) echo "<th>".htmlspecialchars($h)."</th>";
    echo "</tr>";
    foreach ($rows as $r) {
        echo "<tr>";
        foreach ($r as $v) echo "<td>".htmlspecialchars((string)$v)."</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Show categories sample
$catQ = null;
if ($id > 0) {
    $catQ = mysqli_prepare($conn, "SELECT * FROM tbl_categories WHERE categories_id = ? LIMIT 5");
    mysqli_stmt_bind_param($catQ, 'i', $id);
    mysqli_stmt_execute($catQ);
    $catRes = mysqli_stmt_get_result($catQ);
} else {
    $catRes = mysqli_query($conn, "SELECT * FROM tbl_categories LIMIT 5");
}
show_table('tbl_categories (sample)', $catRes);

// Show vendors sample
$venRes = mysqli_query($conn, "SELECT vendor_id, shop_name, vendor_name, address, city, image_path, status, is_active FROM tbl_vendors LIMIT 10");
show_table('tbl_vendors (sample)', $venRes);

if ($id > 0) {
    // show vendors who joined via tbl_categories (vendor-specific categories)
    $joinQ = mysqli_prepare($conn, "SELECT c.*, v.vendor_id, v.shop_name FROM tbl_categories c JOIN tbl_vendors v ON c.vendor_id = v.vendor_id WHERE c.categories_id = ? LIMIT 50");
    mysqli_stmt_bind_param($joinQ, 'i', $id);
    mysqli_stmt_execute($joinQ);
    $joinRes = mysqli_stmt_get_result($joinQ);
    show_table('Categories joined to Vendors (by categories_id)', $joinRes);

    // show join by name (case-sensitive)
    $nameQ = mysqli_prepare($conn, "SELECT c.*, v.vendor_id, v.shop_name FROM tbl_categories c JOIN tbl_vendors v ON c.vendor_id = v.vendor_id WHERE c.categories_name = (SELECT categories_name FROM tbl_categories WHERE categories_id = ?) LIMIT 50");
    mysqli_stmt_bind_param($nameQ, 'i', $id);
    mysqli_stmt_execute($nameQ);
    $nameRes = mysqli_stmt_get_result($nameQ);
    show_table('Categories joined to Vendors (by categories_name)', $nameRes);
}

// Show product table existence
$tables = [];
$tr = mysqli_query($conn, "SHOW TABLES");
while ($r = mysqli_fetch_row($tr)) $tables[] = $r[0];
echo "<h3>Tables in DB (excerpt)</h3>";
echo "<pre>".htmlspecialchars(implode("\n", $tables))."</pre>";

echo "<p>Copy the above tables and sample rows into the chat so I can adjust the matching logic.</p>";

?>
