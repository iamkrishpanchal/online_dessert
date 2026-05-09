<?php
include 'session.php';
include 'connection.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Product</title>
</head>

<body>
    <!DOCTYPE html>
    <html lang="en" class="light">
    <!-- BEGIN: Head -->

    <head>

        <!-- BEGIN: CSS Assets-->
        <link rel="stylesheet" href="dist/css/app.css" />
        <!-- END: CSS Assets-->
    </head>
    <!-- END: Head -->

    <body class="py-0 md:py-0 bg-[#f4f7fb] dark:bg-slate-950">
        <div class="flex mt-0 md:mt-0 overflow-hidden">
            <!-- BEGIN: Side Menu -->
            <?php include 'sideMenu.php' ?>
            <!-- END: Side Menu -->
            <!-- BEGIN: Content -->
            <div class="content">
                <!-- BEGIN: Top Bar -->
                <?php include 'topBar.php' ?>
                <!-- END: Top Bar -->
                <div class="intro-y flex items-center mt-8">
                    <h2 class="text-lg font-medium mr-auto">

                    </h2>
                </div>
                <div class="grid grid-cols-12 gap-6 mt-5">
                    <div class="intro-y col-span-12 lg:col-span-12">
                        <!-- BEGIN: Basic Table -->
                        <div class="intro-y box">
                            <!-- <div class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60">
                                <h2 class="font-medium text-base mr-auto">
                                    Product

                                </h2> -->
                                <!-- <div class="form-check form-switch w-full sm:w-auto sm:ml-auto mt-3 sm:mt-0">

                                    <input id="show-example-1" data-target="#basic-table" class="show-code form-check-input mr-0 ml-3" type="checkbox">
                                </div> -->
                            </div>
                            <div class="p-5" id="basic-table">
                                <div class="preview">
                                    <?php include 'vendors_list.php'; ?>
                                </div>
                            </div>
                        </div>
                        <!-- END: Basic Table -->

                    </div>
                </div>
                    <!-- BEGIN: JS Assets-->
                    <script src="https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/markerclusterer.js"></script>
                    <script src="dist/js/app.js"></script>
                    <!-- END: JS Assets-->
            </div>
        </div>
    </body>
    <style>
        .product-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
            padding: 10px 0;
        }

        .product-card {
            background: #f9f9f9;      
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 50%;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .product-card-image {
            position: relative;
            width: 40%;
            height: 50px;
            overflow: hidden;
            background: #f5f5f5;
        }

        .product-card-image img {
            width: 40%;
            height: 50%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-card-image img {
            transform: scale(1.05);
        }

        .product-sr-no {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-card-content {
            padding: 16px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin: 0 0 12px 0;
            line-height: 1.4;
            display: -webkit-box;

            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .product-status {
            margin-bottom: 12px;
        }

        .badge-active {
            display: inline-block;
            background: #7c4a2f;
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-inactive {
            display: inline-block;
            background: #c8a07f;
            color: #4c2e1b;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #eee;
        }

        .btn-action {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-view {
            background: #e7f3ff;
            color: #0066cc;
        }

        .btn-view:hover {
            background: #0066cc;
            color: white;
        }

        .btn-edit {
            background: #7c4a2f;
            color: #ffffff;
        }

        .btn-edit:hover {
            background: #5a351f;
            color: white;
        }

        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }

        .btn-delete:hover {
            background: #c62828;
            color: white;
        }

        @media (max-width: 768px) {
            .product-cards-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
            }
        }

        @media (max-width: 480px) {
            .product-cards-grid {
                grid-template-columns: 1fr;
            }
        }

        /* layout polish */
        .content {
            width: 100%;
            padding: 1.2rem;
            min-height: calc(100vh - 60px);
        }

        .top-bar {
            display: flex;
            align-items: center;
            padding: 0.75rem 0.75rem;
            background-color: #fff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 3px 10px rgba(30, 41, 59, 0.08);
        }

        .intro-y.box {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
        }

        .intro-y .font-medium {
            color: #1f2937;
        }

        .intro-y h2 {
            margin-bottom: 0;
        }

        /* Additional styling for viewProduct page */
        .preview {
            background: #fefefe;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(103, 120, 143, 0.16);
            box-shadow: 0 10px 30px rgba(30, 41, 59, 0.08);
        }

        .preview .vendor-section h3 {
            background: linear-gradient(135deg, #98cab9 0%, #a3cab6 100%);
            color: black !important;
            padding: 1.2rem 1.8rem;
            border-radius: 12px;
            font-size: 1.8rem !important;
            font-weight: 700 !important;
            margin-bottom: 1.5rem !important;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.25);
            letter-spacing: 0.5px;
        }

        .preview .vendor-card-detailed {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 12px;
            border-left: 5px solid #13402a;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .preview .vendor-card-detailed:hover {
            box-shadow: 0 12px 28px rgba(102, 126, 234, 0.2);
            border-left-color: #764ba2;
            transform: translateY(-3px);
        }

        .preview .vendor-profile-img {
            border-radius: 12px;
            border: 3px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .preview .vendor-view-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
            background: linear-gradient(120deg, #1c4f39, #326053) !important;
            color: white !important;
            -webkit-text-fill-color: white !important;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none !important;
            padding: 12px 22px;
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

        .preview .vendor-view-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transition: left 0.6s ease;
            z-index: -1;
        }

        .preview .vendor-view-btn:hover::before {
            left: 100%;
        }

        .preview .vendor-view-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 24px rgba(102, 126, 234, 0.4), 0 0 20px rgba(102, 126, 234, 0.2);
            color: white !important;
            text-decoration: none !important;
            background: linear-gradient(120deg, #1c4f39, #326053) !important;
            border-color: rgba(255,255,255,0.3);
        }

        .preview .vendor-view-btn:active {
            transform: translateY(-1px) scale(0.98);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .preview .vendor-view-btn:focus {
            outline: none;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3), 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .preview .vendor-badge {
            font-size: 12px;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
            color: white !important;
            -webkit-text-fill-color: white !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            display: inline-block;
            white-space: nowrap;
        }

        .preview .vendor-badge.online {
            background: linear-gradient(120deg, #10b981, #059669) !important;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .preview .vendor-badge.offline {
            background: linear-gradient(120deg, #6b7280, #4b5563) !important;
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        .preview .vendor-info h3 {
            font-size: 18px;
            font-weight: 700;
            background: linear-gradient(120deg, #1f2937 0%, #667eea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0 0 12px 0;
        }

        .preview .vendor-detail-row {
            margin: 8px 0;
            font-size: 13px;
            color: #555;
            display: flex;
            align-items: center;
        }

        .preview .vendor-detail-row strong {
            color: #333;
            font-weight: 600;
        }
    </style>
    <script>
        function handleEdit(productId) {
            window.location.href = "editProduct.php?product_id=" + productId;
        }

        function handleDelete(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = "deleteProduct.php?product_id=" + productId;
            }
        }

        function handleView(productId) {
            window.location.href = "single_product.php?productId=" + productId;
        }
    </script>

    </html>