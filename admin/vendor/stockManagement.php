<?php
include 'session.php';
include 'connection.php';
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <title>Stock Management</title>
    <link rel="stylesheet" href="dist/css/app.css" />
    <style>
        /* Enhanced Table Styling */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.95rem;
        }

        .table thead {
            background: linear-gradient(135deg, #0a2540 0%, #0f3a5f 100%);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table thead th {
            color: #ffffff;
            font-weight: 600;
            padding: 16px 12px;
            text-align: left;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #0a2540;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            background-color: #ffffff;
        }

        .table tbody tr:hover {
            background-color: #f0f7ff;
            box-shadow: inset 4px 0px 0px #0a2540;
            transform: translateX(2px);
        }

        .table tbody td {
            padding: 14px 12px;
            color: #1e293b;
            font-weight: 500;
        }

        .table tbody tr:last-child {
            border-bottom: 2px solid #0a2540;
        }

        /* Product Name Styling */
        .table tbody td:nth-child(2) {
            color: #0a2540;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* Category Badge */
        .table tbody td:nth-child(3) {
            color: #475569;
            font-size: 0.9rem;
            padding: 14px 12px;
        }

        /* Price Styling */
        .table tbody td:nth-child(4) {
            color: #059669;
            font-weight: 700;
            font-size: 1rem;
        }

        /* Stock Badge Styling */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-align: center;
            min-width: 60px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .badge-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
        }

        .badge-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff;
        }

        .badge-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff;
        }

        /* Sr No. Column */
        .table tbody td:first-child {
            background-color: #f8fafc;
            font-weight: 700;
            color: #0a2540;
            text-align: center;
            width: 60px;
            border-left: 3px solid #0a2540;
        }

        /* Box/Card Enhancement */
        .intro-y.box {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        /* Header Section */
        .intro-y h2 {
            color: #0a2540;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .table thead th,
            .table tbody td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }
            
            .badge {
                padding: 4px 8px;
                font-size: 0.8rem;
                min-width: 50px;
            }
        }

        /* Empty state styling */
        .table tbody tr td[colspan="6"] {
            text-align: center;
            color: #94a3b8;
            padding: 30px 12px;
            font-weight: 500;
        }

        /* Update Button Styling */
        .btn-update {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-update:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }

        .btn-update:active {
            transform: translateY(0);
        }

        /* Modal Styling */
        .modal {
            display: none !important;
            position: fixed !important;
            z-index: 9999 !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.6) !important;
            animation: fadeIn 0.3s ease;
            overflow: auto;
        }

        .modal.show {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #ffffff !important;
            padding: 30px !important;
            border-radius: 8px !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3) !important;
            max-width: 400px !important;
            width: 90% !important;
            animation: slideIn 0.3s ease !important;
            margin: auto !important;
            position: relative !important;
            z-index: 10000 !important;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            color: #0a2540 !important;
            margin-bottom: 20px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }

        .close-btn {
            font-size: 24px !important;
            font-weight: bold !important;
            color: #94a3b8 !important;
            cursor: pointer !important;
            transition: color 0.3s ease !important;
            background: none !important;
            border: none !important;
            padding: 0 !important;
        }

        .close-btn:hover {
            color: #0a2540 !important;
        }

        .form-group {
            margin-bottom: 20px !important;
        }

        .form-group label {
            display: block !important;
            font-weight: 600 !important;
            color: #0a2540 !important;
            margin-bottom: 8px !important;
            font-size: 0.95rem !important;
        }

        .form-group input {
            width: 100% !important;
            padding: 10px 12px !important;
            border: 2px solid #e2e8f0 !important;
            border-radius: 6px !important;
            font-size: 1rem !important;
            transition: border-color 0.3s ease !important;
            box-sizing: border-box !important;
        }

        .form-group input:focus {
            outline: none !important;
            border-color: #3b82f6 !important;
            background-color: #f8fafc !important;
        }

        .form-actions {
            display: flex !important;
            gap: 10px !important;
            margin-top: 30px !important;
        }

        .btn-save, .btn-cancel {
            flex: 1 !important;
            padding: 10px 16px !important;
            border: none !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            font-size: 0.95rem !important;
        }

        .btn-save {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: #ffffff !important;
        }

        .btn-save:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4) !important;
        }

        .btn-cancel {
            background: #e2e8f0 !important;
            color: #475569 !important;
        }

        .btn-cancel:hover {
            background: #cbd5e1 !important;
        }

        .product-info {
            background-color: #f8fafc !important;
            padding: 12px !important;
            border-radius: 6px !important;
            margin-bottom: 20px !important;
            border-left: 4px solid #3b82f6 !important;
        }

        .product-info p {
            margin: 0 !important;
            color: #475569 !important;
            font-size: 0.9rem !important;
        }

        .product-info p strong {
            color: #0a2540 !important;
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
            
            <div class="intro-y flex items-center mt-8">
                <h2 class="text-2xl font-medium mr-auto flex items-center gap-2">
                    <i data-lucide="box" class="w-6 h-6"></i>
                    Stock Management
                </h2>
            </div>
            
            <div class="grid grid-cols-12 gap-6 mt-5">
                <!-- Current Stock Table -->
                <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-2xl mr-auto flex items-center gap-2">
                            <i data-lucide="database" class="w-6 h-6"></i>
                            Current Stock Levels
                        </h2>
                        </div>
                        
                        <div class="p-5" id="current-stock">
                            <div class="preview">
                                <div class="overflow-x-auto">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="whitespace-nowrap">Sr No.</th>
                                                <th class="whitespace-nowrap">Product Name</th>
                                                <th class="whitespace-nowrap">Category</th>
                                                <th class="whitespace-nowrap">Price</th>
                                                <th class="whitespace-nowrap">Current Stock</th>
                                                <th class="whitespace-nowrap">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php
    $vendor_id = $_SESSION['vendor_id'] ?? null;
    if (!$vendor_id) {
        echo "<tr><td colspan='6'>Vendor ID not found. Please login again.</td></tr>";
    } else {
        $sql = "SELECT p.product_id, p.product_name, p.product_price, p.stock, c.categories_name
                FROM tbl_products p
                INNER JOIN tbl_categories c ON p.category_id = c.categories_id
                WHERE p.vendor_id = ?
                ORDER BY p.product_name";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $vendor_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            echo "<tr><td colspan='6'>Database error.</td></tr>";
            $result = false;
        }

        $count = 1;
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<tr>';
                echo '<td>' . $count++ . '</td>';
                echo '<td>' . htmlspecialchars($row['product_name']) . '</td>';
                echo '<td>' . htmlspecialchars($row['categories_name']) . '</td>';
                echo '<td>Rs. ' . htmlspecialchars($row['product_price']) . '</td>';
                echo '<td><span class="badge ' . (($row['stock'] > 0) ? 'badge-success' : 'badge-danger') . '">' . htmlspecialchars($row['stock']) . '</span></td>';
                
                // Show update button if stock is 5 or less
                echo '<td>';
                if ($row['stock'] <= 5) {
                    $safeName = str_replace("'", "\\'", $row['product_name']);
                    echo '<button class="btn-update" onclick="openUpdateModal(' . $row['product_id'] . ', \'' . htmlspecialchars($safeName) . '\', ' . $row['stock'] . ')">Update Quantity</button>';
                } else {
                    echo '<span style="color: #94a3b8; font-size: 0.85rem;">Stock OK</span>';
                }
                echo '</td>';
                echo '</tr>';
            }
            mysqli_stmt_close($stmt);
        }
    }
?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock History Table -->
                <!-- <div class="intro-y col-span-12 lg:col-span-12">
                    <div class="intro-y box">
                        <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                            <h2 class="font-medium text-base mr-auto">Stock History</h2>
                        </div>
                        
                        <div class="p-5" id="basic-table">
                            <div class="preview">
                                <div class="overflow-x-auto">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="whitespace-nowrap">Sr No.</th>
                                                <th class="whitespace-nowrap">Product Name</th>
                                                <th class="whitespace-nowrap">Previous Stock</th>
                                                <th class="whitespace-nowrap">Quantity Added</th>
                                                <th class="whitespace-nowrap">New Stock</th>
                                                <th class="whitespace-nowrap">Date</th>
                                                <th class="whitespace-nowrap">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT sm.stock_id, p.product_name, sm.previous_quantity, sm.quantity_added, sm.new_quantity, sm.stock_date, sm.notes
                                                    FROM tbl_stock_management sm
                                                    INNER JOIN tbl_products p ON sm.product_id = p.product_id
                                                    ORDER BY sm.stock_date DESC";
                                            $result = mysqli_query($conn, $query);
                                            $count = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                ?>
                                                <tr>
                                                    <td><?php echo $count++; ?></td>
                                                    <td><?php echo htmlspecialchars($row["product_name"]); ?></td>
                                                    <td><?php echo $row["previous_quantity"]; ?></td>
                                                    <td><?php echo $row["quantity_added"]; ?></td>
                                                    <td><?php echo $row["new_quantity"]; ?></td>
                                                    <td><?php echo date('d-m-Y H:i', strtotime($row["stock_date"])); ?></td>
                                                    <td><?php echo htmlspecialchars($row["notes"] ?? ''); ?></td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
        <!-- END: Content -->
    </div>

    <!-- BEGIN: JS Assets-->
    <script src="dist/js/app.js"></script>
    <!-- END: JS Assets-->

    <!-- Update Quantity Modal -->
    <div id="updateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999; justify-content: center; align-items: center;">
        <div style="background: white; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 18px; font-weight: bold;">
                <span>Update Stock</span>
                <button onclick="closeUpdateModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
            </div>
            
            <div style="background: #f0f4f8; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                <p style="margin: 5px 0; font-size: 14px;"><strong>Product:</strong> <span id="modalProductName"></span></p>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Current Stock:</strong> <span id="modalCurrentStock"></span></p>
            </div>

            <form id="updateStockForm" style="display: flex; flex-direction: column;">
                <input type="hidden" id="productId" name="product_id">
                
                <div style="margin-bottom: 15px;">
                    <label for="newQuantity" style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px;">New Quantity:</label>
                    <input type="number" id="newQuantity" name="new_quantity" min="0" required placeholder="Enter new quantity" style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="notes" style="display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px;">Notes (Optional):</label>
                    <input type="text" id="notes" name="notes" placeholder="Add any notes" style="width: 100%; padding: 8px; border: 2px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" style="flex: 1; padding: 10px; background: #10b981; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Update Stock</button>
                    <button type="button" onclick="closeUpdateModal()" style="flex: 1; padding: 10px; background: #e2e8f0; color: #475569; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUpdateModal(productId, productName, currentStock) {
            console.log('Opening modal for product:', productId, productName, currentStock);
            
            const modal = document.getElementById('updateModal');
            if (!modal) {
                console.error('Modal element not found!');
                return;
            }

            // Set values
            document.getElementById('productId').value = productId;
            document.getElementById('modalProductName').textContent = productName;
            document.getElementById('modalCurrentStock').textContent = currentStock;
            document.getElementById('newQuantity').value = '';
            document.getElementById('notes').value = '';
            
            // Show modal
            modal.style.display = 'flex';
            console.log('Modal displayed');
            
            // Focus on input
            setTimeout(() => {
                document.getElementById('newQuantity').focus();
            }, 100);
        }

        function closeUpdateModal() {
            const modal = document.getElementById('updateModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal when clicking outside of it
        document.getElementById('updateModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeUpdateModal();
            }
        });

        // Handle form submission
        document.getElementById('updateStockForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productId = document.getElementById('productId').value;
            const newQuantity = document.getElementById('newQuantity').value;
            const notes = document.getElementById('notes').value;

            console.log('Form submitted:', {productId, newQuantity, notes});

            // Validate
            if (!productId || !newQuantity || isNaN(newQuantity) || newQuantity < 0) {
                alert('Please enter a valid quantity');
                return;
            }

            // Create form data
            const formData = new FormData();
            formData.append('action', 'update_stock');
            formData.append('product_id', productId);
            formData.append('new_quantity', newQuantity);
            formData.append('notes', notes);

            // Send request
            fetch('updateStock.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response text:', text);
                console.log('Response length:', text.length);
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed JSON:', data);
                    
                    if (data.success) {
                        alert('Stock updated successfully!');
                        closeUpdateModal();
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch(e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response was:', text.substring(0, 500));
                    alert('Server error: ' + text.substring(0, 200));
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Network error: ' + error.message);
            });
        });
    </script>
</body>
</html>
