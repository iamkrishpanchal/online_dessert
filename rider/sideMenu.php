<!-- BEGIN: Side Menu -->
<style>
    .side-nav { background: linear-gradient(120deg, #0f6f62 0%, #174f51 100%) !important; border-right: 4px solid rgba(10, 55, 45, 0.85) !important; }
    .side-nav .side-menu { background-color: rgba(255, 255, 255, 0.09) !important; color: #e6f8f2 !important; border-radius: 10px; margin-bottom: 0.5rem; transition: background 0.2s ease, transform 0.2s ease; }
    .side-nav .side-menu:hover, .side-nav .side-menu.active, .side-nav .side-menu.side-menu--active { background-color: rgba(255, 255, 255, 0.22) !important; transform: translateX(3px); }
    .side-nav .side-menu__title { color: #ddf6ec !important; font-weight: 600; }
    .side-nav .side-menu:hover .side-menu__title, .side-nav .side-menu.active .side-menu__title { color: #fff !important; }
    .side-nav .side-menu__icon .lucide, .side-nav .side-menu__icon .lucide *,
    .side-nav .side-menu__icon svg, .side-nav .side-menu__icon svg * { color: #a7ffe5 !important; stroke: #a7ffe5 !important; }
    .side-nav .side-menu.active .side-menu__icon .lucide, .side-nav .side-menu.active .side-menu__icon .lucide *,
    .side-nav .side-menu.side-menu--active .side-menu__icon .lucide, .side-nav .side-menu.side-menu--active .side-menu__icon .lucide *,
    .side-nav .side-menu.active .side-menu__icon svg, .side-nav .side-menu.active .side-menu__icon svg *,
    .side-nav .side-menu.side-menu--active .side-menu__icon svg, .side-nav .side-menu.side-menu--active .side-menu__icon svg * {
        color: #ffffff !important;
        stroke: #ffffff !important;
        fill: #ffffff !important;
    }
    .side-nav .side-nav__devider { border-top: 1px solid rgba(255,255,255,0.18); }
    .side-nav a.intro-x { padding-bottom: 0.5rem; }
</style>
<nav class="side-nav" style="background: linear-gradient(120deg, rgb(123, 151, 131) 25%);">
    <a href="dashboard.php" class="intro-x flex flex-col items-center justify-center pl-5 pt-4 mt-3 text-center">
        <img alt="Dessert Magic Logo" class="w-28 h-28 xl:w-32 xl:h-32 rounded-full shadow-lg object-cover" src="../user/images/ridersidemenu.jpg" onerror="this.src='../user/images/ridersidemenu.jpg'" />
        <!-- <span class="sr-only">Dessert Magic</span> -->
    </a>
    <div class="side-nav__devider my-6"></div>
    <ul>
        <li>
            <a href="dashboard.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="home"></i> </div>
                <div class="side-menu__title">Dashboard</div>
            </a>
        </li>
        <li>
            <a href="assigned_orders.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="bell"></i> </div>
                <div class="side-menu__title">Assigned Orders</div>
            </a>
        </li>
        <li>
            <a href="deliveryHistory.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="history"></i> </div>
                <div class="side-menu__title">Delivery History</div>
            </a>
        </li>
        <li>
            <a href="earnings.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="dollar-sign"></i> </div>
                <div class="side-menu__title">Earnings</div>
            </a>
        </li>
        <li>
            <a href="myProfile.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="user"></i> </div>
                <div class="side-menu__title">My Profile</div>
            </a>
        </li>
         <li>
            <a href="logout.php" class="side-menu">
                <div class="side-menu__icon"> <i data-lucide="log-out"></i> </div>
                <div class="side-menu__title">Logout</div>
            </a>
        </li>
    </ul>
</nav>
<!-- END: Side Menu -->