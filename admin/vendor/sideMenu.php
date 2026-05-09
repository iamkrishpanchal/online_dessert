<?php
// show vendor's shop name and logo in sidebar
include __DIR__ . '/session.php';
include __DIR__ . '/../connection.php';
$vendor_id = intval($_SESSION['vendor_id']);
$shopName = 'Vendor';
$logoPath = '';
$vendorInfo = mysqli_query($conn, "SELECT shop_name, logo_path FROM tbl_vendors WHERE vendor_id = {$vendor_id} LIMIT 1");
if ($vendorInfo && ($row = mysqli_fetch_assoc($vendorInfo))) {
    if (!empty($row['shop_name'])) {
        $shopName = $row['shop_name'];
    }
    if (!empty($row['logo_path'])) {
        // most stored logos are relative filenames under uploads/vendors
        $logoPath = $row['logo_path'];
        if (!preg_match('#^(https?://|/)#', $logoPath)) {
            $logoPath = 'uploads/vendors/' . $logoPath;
        }
    }
}
?>
<!-- custom styling for dark-blue rounded side-menu items -->
<style>
    .side-nav {
        background-color: #0a2540 !important;
        border-radius: 0.75rem;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 265px;
        height: 100vh;
        overflow-y: auto;
        padding-top: 1rem;
        z-index: 999;
    }
    .content {
        margin-left: 275px !important;
    }
    .side-nav ul, .side-nav li {
        background-color: transparent !important;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .side-nav .side-menu {
        background-color: #0a2540 !important;
        color: #fff !important;
        border-radius: 0.5rem;
        margin-bottom: 0.35rem;
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0.75rem 0.75rem;
        border: none;
        box-shadow: none;
        text-decoration: none;
    }
    .side-nav .side-menu:hover,
    .side-nav .side-menu.active,
    .side-nav .side-menu.side-menu--active {
        background-color: #0a1c33 !important;
        color: #fff !important;
    }
    .side-nav .side-menu:hover .side-menu__icon,
    .side-nav .side-menu.active .side-menu__icon,
    .side-nav .side-menu.side-menu--active .side-menu__icon,
    .side-nav .side-menu:hover .side-menu__title,
    .side-nav .side-menu.active .side-menu__title,
    .side-nav .side-menu.side-menu--active .side-menu__title,
    .side-nav .side-menu .side-menu__sub-icon,
    .side-nav .intro-x span {
        color: #fff !important;
    }
    .side-nav a.intro-x,
    .side-nav a.intro-x span {
        background-color: transparent !important;
        color: #fff !important;
    }
</style>
<!-- ensure shop name text is always readable -->
<style>
    .side-nav .intro-x span {
        color: #fff !important;
    }
</style>
<!-- BEGIN: Side Menu -->
<nav class="side-nav" style="background-color:#0a2540;"> <!-- dark blue background -->
    <a href="" class="intro-x flex items-center pl-5 pt-4 mt-3">
        <?php if ($logoPath): ?>
            <img alt="<?php echo htmlspecialchars($shopName); ?> Logo" class="w-8 xl:w-12" src="<?php echo htmlspecialchars($logoPath); ?>" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
        <?php else: ?>
            <img alt="<?php echo htmlspecialchars($shopName); ?> Logo" class="w-8 xl:w-12" src="src/Red_and_Brown_Playful_Sweet_Donuts_and_Bakery_Logo.png" onerror="this.style.display='none'; this.nextElementSibling.classList.remove('hidden');">
        <?php endif; ?>
        <span class="text-white text-lg ml-3" style="display:inline-block;"><?php echo htmlspecialchars($shopName); ?></span>
    </a>
    <div class="side-nav__devider my-6"></div>
    <ul>
       <li>
            <a href="dashboard.php" class="side-menu">
                    <div class="side-menu__icon"> <i data-lucide="home"></i> </div>
                    <div class="side-menu__title"> Dashboard</div>
                </a>
            </li>
        
        <li>
            <a href="javascript:;" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="tag"></i> </div>
                <div class="side-menu__title">
                    Categories
                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                </div>
            </a>
            <ul class="">

            <li>
                    <a href="addCategory.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="plus-circle"></i> </div>
                        <div class="side-menu__title">
                            Add Category</div>
                    </a>
                </li>

                <li>
                    <a href="viewCategory.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="eye"></i> </div>
                        <div class="side-menu__title">
                            All Categories</div>
                    </a>
                </li>
                <!-- <li>
                    <a href="addCategory.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="plus-circle"></i> </div>
                        <div class="side-menu__title">
                            Add Category</div>
                    </a>
                </li> -->
            </ul>
        </li>

         <li>
            <a href="javascript:;" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="shopping-bag"></i> </div>
                <div class="side-menu__title">
                    Products
                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                </div>
            </a>
            <ul class="">

            <li>
                    <a href="addProduct.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="plus-circle"></i> </div>
                        <div class="side-menu__title">
                            Add Products</div>
                    </a>
                </li>

                <li>
                    <a href="viewProduct.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="eye"></i> </div>
                        <div class="side-menu__title">
                            All Products</div>
                    </a>       
            </ul>
        </li>
    



        <li>
            <a href="stockManagement.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="package"></i> </div>
                <div class="side-menu__title">
                    Stock Management
                </div>
            </a>
        </li>
    
        <li>
            <a href="javascript:;" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="list"></i> </div>
                <div class="side-menu__title">
                    Order History
                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                </div>
            </a>
            <ul class="">

                <li>
                    <a href="allorders.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="eye"></i> </div>
                        <div class="side-menu__title">
                            All Orders</div>
                    </a>       
            </ul>
        </li>

        <li>
            <a href="total_revenue.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="trending-up"></i> </div>
                <div class="side-menu__title">
                    Total Revenue
                </div>
            </a>
        </li>


            <li>
                <a href="account_status.php" class="side-menu">
                    <div class="side-menu__icon"> <i data-lucide="user-check"></i> </div>
                    <div class="side-menu__title">
                        Account Status
                    </div>
                </a>
            </li>

        
    


        <li>
                    <a href="feedback.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="message-square"></i> </div>
                        <div class="side-menu__title">
                           Feedback
                            <div class="side-menu__sub-icon "> <i data-lucide="arrow-right"></i> </div>
                        </div>
                    </a>
                    
        </li>
                <li>
                    <a href="logout.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="log-out"></i> </div>
                        <div class="side-menu__title">
                           Logout
                            <div class="side-menu__sub-icon "> <i data-lucide="arrow-right"></i> </div>
                        </div>
                    </a>
                    
        </li>
             
                
        </li>
            </ul>
        </li>
    </ul>
</nav>
<!-- END: Side Menu -->