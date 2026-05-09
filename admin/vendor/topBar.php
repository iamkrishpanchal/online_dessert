<div class="top-bar -mx-4 px-4 md:mx-0 md:px-0">
                    <?php
                    // Ensure vendor name and online status are defined before rendering any UI.
                    $vendor_is_online = 0;
                    $vendorName = 'Vendor';
                    if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
                        $vendorName = trim($_SESSION['vendor_name'] ?? 'Vendor');
                        if (isset($_SESSION['is_online'])) {
                            $vendor_is_online = intval($_SESSION['is_online']);
                        } elseif (!empty($_SESSION['vendor_id'])) {
                            include_once __DIR__ . '/../connection.php';
                            $vid = intval($_SESSION['vendor_id']);
                            $res = mysqli_query($conn, "SELECT COALESCE(is_online,0) AS is_online FROM tbl_vendors WHERE vendor_id = {$vid} LIMIT 1");
                            if ($res && ($row = mysqli_fetch_assoc($res))) {
                                $vendor_is_online = intval($row['is_online']);
                            }
                        }
                    }
                    ?>
                <style>
                    .vendor-toggle-wrap { display: flex; align-items: center; gap: 0.8rem; margin-right: 0.75rem; }
                    .vendor-toggle-label { color: #334155; font-size: 0.82rem; font-weight: 600; }
                    .vendor-toggle-switch { position: relative; display: inline-block; width: 56px; height: 30px; }
                    .vendor-toggle-switch input { opacity: 0; width: 0; height: 0; }
                    .vendor-toggle-slider { position: absolute; inset: 0; background: #cbd5e1; border-radius: 999px; transition: background 0.3s ease; box-shadow: inset 0 0 0 1px rgba(15,23,42,0.14); }
                    .vendor-toggle-slider::before { content: ""; position: absolute; height: 24px; width: 24px; left: 3px; top: 3px; border-radius: 999px; background: #ffffff; box-shadow: 0 3px 10px rgba(15,23,42,0.2); transition: transform 0.3s ease, background 0.3s ease; }
                    .vendor-toggle-switch input:checked + .vendor-toggle-slider { background: #22c55e; box-shadow: inset 0 0 0 1px rgba(34,197,94,0.35); }
                    .vendor-toggle-switch input:checked + .vendor-toggle-slider::before { transform: translateX(26px); background: #ffffff; }
                    .vendor-toggle-state { font-size: 0.78rem; font-weight: 700; color: #075985; text-transform: uppercase; letter-spacing: 0.08em; }
                    .vendor-toggle-state.offline { color: #b45309; }
                </style>
                    <!-- BEGIN: Vendor Status Toggle -->
                    <div class="vendor-toggle-wrap" style="margin-right: auto;">
                        <style>
                            .top-bar { overflow: visible !important; min-height: 110px; }
                            .intro-x.dropdown, .dropdown-toggle { overflow: visible !important; }
                            .dropdown-toggle { position: relative; top: 6px; padding-top: 6px; padding-bottom: 6px; }
                            .vendor-avatar { width: 74px; height: 74px; border-radius: 999px; object-fit: cover; border: 3px solid #e2e8f0; box-shadow: 0 6px 16px rgba(15, 23, 42, 0.35); }
                        </style>
                        <form method="post" id="vendorToggleForm">
                            <input type="hidden" id="vendor_status_input" name="vendor_status" value="<?php echo $vendor_is_online ? 'active' : 'inactive'; ?>">
                            <label class="vendor-toggle-switch">
                                <input type="checkbox" id="vendor_toggle_checkbox" <?php echo $vendor_is_online ? 'checked' : ''; ?>>
                                <span class="vendor-toggle-slider"></span>
                            </label>
                        </form>
                        <span id="vendor-status-text" class="vendor-toggle-state <?php echo $vendor_is_online ? '' : 'offline'; ?>"><?php echo $vendor_is_online ? 'Online' : 'Offline'; ?></span>
                    </div>
                    <!-- END: Vendor Status Toggle -->
                    <!-- BEGIN: Breadcrumb -->
                    <nav aria-label="breadcrumb" class="-intro-x hidden sm:flex">
                        <!-- <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Application</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol> -->
                    </nav>
                    <!-- END: Breadcrumb -->
                    <!-- BEGIN: Welcome Message -->
                    <?php
                    // ensure vendor name is always defined to avoid htmlspecialchars(null) deprecation warning
                    $vendorName = 'Vendor';
                    if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
                        $vendorName = trim($_SESSION['vendor_name'] ?? 'Vendor');
                    }
                    ?>
                    <div class="flex-1 text-center">
                        <div class="text-lg font-medium text-slate-900">Hi <?php echo htmlspecialchars($vendorName); ?>, <span class="font-normal">welcome back!</span></div>
                    </div>
                    <!-- END: Welcome Message -->
                    <!-- BEGIN: Search -->
                    
                    <!-- END: Search -->
                    <!-- BEGIN: Notifications -->
                    <!-- END: Notifications -->

                    
                    <script>
                        const vendorCheckbox = document.getElementById('vendor_toggle_checkbox');
                        const statusInput = document.getElementById('vendor_status_input');
                        const statusText = document.getElementById('vendor-status-text');
                        const form = document.getElementById('vendorToggleForm');

                        vendorCheckbox.addEventListener('change', () => {
                            const isOnline = vendorCheckbox.checked;
                            statusInput.value = isOnline ? 'active' : 'inactive';
                            statusText.textContent = isOnline ? 'Online' : 'Offline';
                            statusText.classList.toggle('offline', !isOnline);
                            form.submit();
                        });
                    </script>
                    <!-- END: Vendor Status Toggle -->
                    <!-- BEGIN: Account Menu -->
                    <?php
                    // Do not start a session here — caller should start the session before sending output.
                    $vendorName = 'Vendor';
                    if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
                        $vendorName = trim($_SESSION['vendor_name'] ?? 'Vendor');
                    }

                    // Default fallback uses existing logo available in project
                    $vendor_image_url = 'dist/images/logo.svg';

                    // Prefer an explicitly set session vendor image (if available).
                    if (!empty($_SESSION['vendor_image'])) {
                        $vendor_image_url = $_SESSION['vendor_image'];
                    }

                    $vendorId = intval($_SESSION['vendor_id'] ?? 0);
                    if ($vendorId > 0 && isset($conn)) {
                        // Prefer vendor image_path first (user avatar), then shop logo_path fallback.
                        $imgRes = mysqli_query($conn, "SELECT image_path, logo_path FROM tbl_vendors WHERE vendor_id = {$vendorId} LIMIT 1");
                        if ($imgRes && ($imgRow = mysqli_fetch_assoc($imgRes))) {
                            $imgPath = '';
                            if (!empty($imgRow['image_path'])) {
                                $imgPath = trim($imgRow['image_path']);
                            } elseif (!empty($imgRow['logo_path'])) {
                                $imgPath = trim($imgRow['logo_path']);
                            }

                            if (!empty($imgPath)) {
                                if (preg_match('#^(https?://|/)#', $imgPath)) {
                                    $vendor_image_url = $imgPath;
                                } else {
                                    // prefer vendor image path directory first, then admin uploads path.
                                    $candidates = [
                                        'uploads/vendors/' . ltrim($imgPath, '/'),
                                        '../uploads/vendors/' . ltrim($imgPath, '/'),
                                        '../../uploads/vendors/' . ltrim($imgPath, '/'),
                                    ];
                                    foreach ($candidates as $candidate) {
                                        $fullPath = __DIR__ . '/' . $candidate;
                                        if (file_exists($fullPath)) {
                                            $vendor_image_url = $candidate;
                                            break;
                                        }
                                        $fullPath2 = __DIR__ . '/../' . $candidate;
                                        if (file_exists($fullPath2)) {
                                            $vendor_image_url = '../' . $candidate;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    ?>
                    <div class="intro-x dropdown ml-auto">
                        <div class="dropdown-toggle flex flex-col items-center justify-center gap-2 px-4 py-3 rounded-lg hover:bg-slate-100 dark:hover:bg-darkmode-400 cursor-pointer transition" role="button" aria-expanded="false" data-tw-toggle="dropdown">
                            <img src="<?php echo htmlspecialchars($vendor_image_url); ?>" alt="Vendor" class="vendor-avatar" onerror="this.src='dist/images/avatar-placeholder.png'" />
                            <div class="font-medium text-base mt-2" style="color:#0a2540;white-space:nowrap;text-align:center;max-width:120px;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($vendorName); ?></div>
                        </div>
                        <div class="dropdown-menu w-56">
                            <ul class="dropdown-content" style="background:#ffffff; color:#0f172a;">
                                <li class="p-2">
                                    <div class="font-medium"><?php echo htmlspecialchars($vendorName); ?></div>
                                    <div class="text-xs text-white/70 mt-0.5 dark:text-slate-500">Vendor</div>
                                </li>
                                <li>
                                    <hr class="dropdown-divider border-white/[0.08]">
                                </li>
                                <li>
                                    <a href="profile.php" class="dropdown-item hover:bg-slate-100" style="color:#0f172a;"> <i data-lucide="user" class="w-4 h-4 mr-2"></i> Profile </a>
                                </li>
                                <li>
                                    <a href="profile.php" class="dropdown-item hover:bg-slate-100" style="color:#0f172a;"> <i data-lucide="edit" class="w-4 h-4 mr-2"></i> Add Account </a>
                                </li>
                                <li>
                                    <a href="resetPassword.php" class="dropdown-item hover:bg-slate-100" style="color:#0f172a;"> <i data-lucide="lock" class="w-4 h-4 mr-2"></i> Reset Password </a>
                                </li>
                                
                                <li>
                                    <hr class="dropdown-divider border-white/[0.08]">
                                </li>
                                <li>
                                    <a href="logout.php" class="dropdown-item hover:bg-slate-100" style="color:#0f172a;"> <i data-lucide="toggle-right" class="w-4 h-4 mr-2"></i> Logout </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- END: Account Menu -->
                </div>
                