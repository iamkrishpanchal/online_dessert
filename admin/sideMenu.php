<?php
// Calculate admin earnings (15% commission on completed paid orders)
$adminEarningsTotal = null;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION['admin_id'])) {
    if (!isset($conn)) {
        include 'connection.php';
    }
    $res = mysqli_query($conn, "SELECT IFNULL(SUM(total_amount),0) AS gross FROM tbl_orders WHERE order_status = 'Completed' AND payment_status = 'Paid'");
    $gross = 0;
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $gross = floatval($row['gross'] ?? 0);
    }
    $adminEarningsTotal = $gross * 0.15;
}
?>
<!-- custom styling for blue rounded side-menu items -->
<style>
    .side-nav {
        background-color: #091a11 !important;
        border-right: 4px solid #091a11 !important;
    }
    .side-nav + .content, .content {
        background-color: #f2f6fb !important;
        border-left: none !important;
    }
    .side-nav ul, .side-nav li {
        background-color: transparent !important;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .side-nav .side-menu {
        background-color: #091a11 !important;
        color: #fff !important;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        width: 100%;
        padding: 0.75rem 0.85rem;
        border: none;
        box-shadow: none;
    }
    .side-nav .side-menu:hover,
    .side-nav .side-menu.active,
    .side-nav .side-menu.side-menu--active {
        background-color: #091a11 !important;
        color: #fff !important;
    }
    .side-nav .side-menu,
    .side-nav .side-menu .side-menu__title,
    .side-nav .side-menu .side-menu__icon,
    .side-nav .side-menu .side-menu__sub-icon {
        text-decoration: none !important;
        border-bottom: none !important;
        box-shadow: none !important;
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

    .side-nav .side-menu .lucide,
    .side-nav .side-menu .lucide * {
        color: #fff !important;
        stroke: #fff !important;
        fill: none !important;
    }

    /* Disable active menu bottom highlight line from template for this theme */
    .side-nav .side-menu:before,
    .side-nav .side-menu.active:before,
    .side-nav .side-menu.side-menu--active:before {
        display: none !important;
        content: none !important;
        border: none !important;
    }

    .side-nav .side-menu.active,
    .side-nav .side-menu.side-menu--active {
        background-color: transparent !important;
        box-shadow: none !important;
        border-left: none !important;
        border-right: none !important;
    }

    /* Override default utility colors that may inject greens from app.css */
    .bg-success,
    .badge.bg-success,
    .badge-success,
    .text-success,
    .border-success {
        background-color: #7c4a2f !important;
        border-color: #7c4a2f !important;
        color: #ffffff !important;
    }

    .bg-primary,
    .text-primary {
        background-color: #6f3e24 !important;
        color: #ffffff !important;
    }

    .bg-warning,
    .text-warning {
        background-color: #a65a2c !important;
        color: #ffffff !important;
    }
</style>
<!-- ensure shop name text is always readable -->
<style>
    .side-nav .intro-x span {
        color: #fff !important;
    }

    /* make side menu fixed and persistent across page scrolls */
    .side-nav {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 268px !important;
        height: 100vh !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        z-index: 1100 !important;
        box-shadow: 2px 0 18px rgba(0,0,0,0.15) !important;
        border-right: 1px solid rgba(255,255,255,0.1) !important;
    }

    /* push page content to the right of fixed side nav */
    .content {
        margin-left: 268px !important;
        padding-top: 1rem !important;
        overflow-x: hidden !important;
        width: calc(100% - 268px) !important;
    }

    /* top bar stays fixed by default from the template; ensure no translation effect */
    .top-bar {
        position: sticky !important;
        top: 0 !important;
        background: #fff !important;
        z-index: 1050 !important;
    }

    .main-container, .layout-wrapper {
        min-height: 100vh !important;
    }
    
    body {
        overflow-x: hidden !important;
    }
</style>
<!-- BEGIN: Side Menu -->
<nav class="side-nav" style="background-color:#0a2540;"> <!-- dark blue background -->
    <!-- using 'latte' theme color to match vendor panel look -->
    <a href="dashboard.php" class="intro-x flex flex-col items-center justify-center pl-5 pt-6 mt-3 text-center">
        <img alt="Dessert Magic Logo" class="w-40 h-20 shadow-lg flex-shrink-0 mb-2 object-cover" src="../user/images/logoadmin.png" onerror="this.src='../user/images/logoadmin.png'; this.onerror=function(){this.src='../user/images/logoadmin.png'}">
        <span class="sr-only">Dessert Magic</span>
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
                <div class="side-menu__icon"> <i data-lucide="shopping-bag"></i> </div>
                <div class="side-menu__title">
                    Shop
                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                </div>
            </a>
            <ul class="">
                <li>
                    <a href="viewProduct.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="list"></i> </div>
                        <div class="side-menu__title"> Shop List</div>
                    </a>
                </li>     
            </ul>
        </li>

        <li>
            <a href="javascript:;" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="users"></i> </div>
                <div class="side-menu__title">
                    Vendor
                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                </div>
            </a>
            <ul class="">

                <li>
                    <a href="vendor_detail.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="users"></i> </div>
                        <div class="side-menu__title">
                            Vendor Details</div>
                    </a>
                    <!-- <a href="addProduct.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="plus-circle"></i> </div>
                        <div class="side-menu__title">
                            Vendor Request Approval</div>
                    </a> -->
                </li>
            </ul>
        </li>
    
        <li>
            <a href="javascript:;" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="users"></i> </div>
                <div class="side-menu__title">
                    Customer
                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                </div>
            </a>
            <ul class="">
                <li>
                    <a href="customers.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="users"></i> </div>
                        <div class="side-menu__title">Customer details</div>
                    </a>
                </li>
            </ul>
        </li>

        <li>
            <a href="javascript:;" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="truck"></i> </div>
                <div class="side-menu__title">
                    Rider
                    <div class="side-menu__sub-icon "> <i data-lucide="chevron-down"></i> </div>
                </div>
            </a>
            <ul class="">

                <li>
                    <a href="riders_list.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="truck"></i> </div>
                        <div class="side-menu__title">
                            Rider List</div>
                    </a>
                </li>
                <li>
                    <a href="rider_form.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="plus-circle"></i> </div>
                        <div class="side-menu__title">
                            Add Rider</div>
                    </a>
                </li>
                <!-- additional filters could be added later with ?status=active etc. -->
            </ul>
        </li>

            <li>
            <a href="orders_dashboard.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="shopping-cart"></i> </div>
                <div class="side-menu__title">
                    All Orders
                </div>
            </a>
        </li>

        <li>
            <a href="earnings.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="credit-card"></i> </div>
                <div class="side-menu__title">
                    Earnings
                    <?php if ($adminEarningsTotal !== null): ?>
                        <div class="side-menu__sub-icon "></div>
                    <?php endif; ?>
                </div>
            </a>
        </li>

         <!-- <li>
                    <a href="feedback.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="message-square"></i> </div>
                        <div class="side-menu__title">
                           Feedback
                            <div class="side-menu__sub-icon "> <i data-lucide="arrow-right"></i> </div>
                        </div>
                    </a>
                    
        </li> -->
    
       
                <li>
                    <a href="logout.php" class="side-menu">
                        <div class="side-menu__icon"> <i data-lucide="wallet"></i> </div>
                        <div class="side-menu__title">
                           logout
                            <div class="side-menu__sub-icon "> <i data-lucide="rupee-sign"></i> </div>
                        </div>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>
<script>
(function(){
    const sideMenuLinks = document.querySelectorAll('.side-nav .side-menu');
    const activeKey = 'adminActiveMenuLink';
    const expandedKey = 'adminExpandedSubmenus';

    function clearActive() {
        sideMenuLinks.forEach(link => link.classList.remove('active'));
    }

    function openParentMenus(link) {
        const rootUl = document.querySelector('.side-nav > ul');
        let parentUl = link.closest('ul');
        while (parentUl && parentUl !== rootUl) {
            if (parentUl.tagName === 'UL') {
                parentUl.classList.add('side-menu__sub-open');
                const parentLink = parentUl.previousElementSibling;
                if (parentLink && parentLink.classList.contains('side-menu')) {
                    const icon = parentLink.querySelector('.side-menu__sub-icon i');
                    if (icon) icon.classList.add('rotate-180');
                }
            }
            parentUl = parentUl.parentElement ? parentUl.parentElement.closest('ul') : null;
        }
    }

    sideMenuLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href && href !== 'javascript:;') {
                localStorage.setItem(activeKey, href);
                clearActive();
                this.classList.add('active');
                return; // allow navigation
            }

            // toggle submenu on parent toggles
            const subList = this.nextElementSibling;
            if (subList && subList.tagName === 'UL') {
                const isOpen = subList.classList.toggle('side-menu__sub-open');
                const icon = this.querySelector('.side-menu__sub-icon i');
                if (icon) icon.classList.toggle('rotate-180', isOpen);

                let expanded = [];
                try { expanded = JSON.parse(localStorage.getItem(expandedKey) || '[]'); } catch (err) { expanded = []; }
                const key = this.textContent.trim();
                const index = expanded.indexOf(key);

                if (isOpen && index === -1) {
                    expanded.push(key);
                } else if (!isOpen && index !== -1) {
                    expanded.splice(index, 1);
                }
                localStorage.setItem(expandedKey, JSON.stringify(expanded));
                e.preventDefault();
            }
        });
    });

    const currentPath = window.location.pathname.split('/').pop();
    let activeLink = null;
    sideMenuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href !== 'javascript:;' && href.split('/').pop() === currentPath) {
            activeLink = link;
        }
    });

    if (!activeLink) {
        const saved = localStorage.getItem(activeKey);
        if (saved) {
            activeLink = Array.from(sideMenuLinks).find(link => link.getAttribute('href') === saved);
        }
    }

    if (activeLink) {
        clearActive();
        activeLink.classList.add('active');
        openParentMenus(activeLink);
    }

    let storedExpanded = [];
    try { storedExpanded = JSON.parse(localStorage.getItem(expandedKey) || '[]'); } catch (err) { storedExpanded = []; }
    storedExpanded.forEach((title) => {
        const parentLink = Array.from(sideMenuLinks).find(link => link.textContent.trim().startsWith(title) && link.getAttribute('href') === 'javascript:;');
        if (parentLink) {
            const subList = parentLink.nextElementSibling;
            if (subList && subList.tagName === 'UL') {
                subList.classList.add('side-menu__sub-open');
                const icon = parentLink.querySelector('.side-menu__sub-icon i');
                if (icon) icon.classList.add('rotate-180');
            }
        }
    });
})();
</script>
<!-- END: Side Menu -->