<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'connection.php';
$rider_name = $_SESSION['rider_name'] ?? 'Rider';
$rider_email = $_SESSION['rider_email'] ?? 'rider@example.com';
$rider_id = $_SESSION['rider_id'] ?? 0;
$profile_image = 'dist/images/profile-5.jpg'; // default

// Get rider profile image and status from database
$rider_status = 'active';
if ($rider_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT profile_image, status FROM tbl_riders WHERE rider_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $rider_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            if (!empty($row['profile_image'])) {
                $img_path = $row['profile_image'];
                // Adjust path if it's from admin folder uploads
                if (file_exists($img_path)) {
                    $profile_image = $img_path;
                } elseif (file_exists('../' . $img_path)) {
                    $profile_image = '../' . $img_path;
                }
            }
            if (!empty($row['status'])) {
                $rider_status = $row['status'];
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<div class="top-bar -mx-4 px-4 md:mx-0 md:px-0">
                    <!-- BEGIN: Breadcrumb -->
                    <nav aria-label="breadcrumb" class="-intro-x mr-auto hidden sm:flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Application</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>
                    <!-- END: Breadcrumb -->
                    <!-- BEGIN: Welcome Message with Rider Info -->
                    <div class="-intro-x mr-auto flex items-center gap-3 hidden sm:flex">
                        <div class="w-10 h-10 rounded-full overflow-hidden shadow image-fit">
                            <img alt="Rider Profile" src="<?php echo htmlspecialchars($profile_image); ?>" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <p class="text-slate-500 text-sm">Welcome back,</p>
                            <p class="text-slate-800 font-semibold text-base"><?php echo htmlspecialchars($rider_name); ?></p>
                        </div>
                    </div>
                    <!-- END: Welcome Message with Rider Info -->
                    <!-- BEGIN: Rider Status Toggle -->
                    <div class="-intro-x mr-4 hidden sm:flex items-center">
                        <span class="text-slate-500 mr-3">Availability</span>
                        <button id="rider-status-toggle" type="button" class="py-2 px-4 rounded-full border text-sm font-medium transition <?php echo $rider_status === 'active' ? 'border-success text-success bg-success/10' : 'border-danger text-danger bg-danger/10'; ?>">
                            <?php echo ucfirst($rider_status); ?>
                        </button>
                    </div>
                    <!-- END: Rider Status Toggle -->
                    <!-- BEGIN: Search -->
                    
                    <!-- END: Search -->
                    <!-- BEGIN: Notifications -->
                    <?php if ($rider_id > 0): ?>
                    <div class="notif-dropdown-wrapper mr-4" id="notif-dropdown">
                        <button class="btn notification-btn rounded-circle p-2 position-relative" type="button" id="notif-button" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="notification-icon"><path d="M12 24c1.104 0 2-.896 2-2h-4c0 1.104.896 2 2 2zm6.002-6v-5c0-3.07-1.633-5.64-4.581-6.32V6c0-.828-.672-1.5-1.5-1.5S10.5 5.172 10.5 6v.68C7.552 7.36 5.92 9.93 5.92 13v5l-1.92 2v1h16v-1l-1.918-2z"/></svg>
                            <span id="notif-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="min-width:16px;height:16px;display:none;">0</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" id="notif-list" aria-labelledby="notif-button" style="width:380px;max-height:500px;overflow-y:auto; display:none;">
                            <li class="dropdown-header">
                                <h6 class="mb-0">Notifications</h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="text-center p-3"><small class="text-muted">No notifications</small></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <!-- END: Notifications -->
                    <style>
                        #notif-dropdown {
                            margin-left: auto;
                            position: relative;
                            z-index: 1130 !important;
                            display: inline-block;
                            pointer-events: auto !important;
                        }
                        #notif-list {
                            position: absolute !important;
                            top: calc(100% + 0.5rem) !important;
                            right: 0 !important;
                            left: auto !important;
                            z-index: 1150 !important;
                            width: 380px;
                            min-width: 280px;
                            background: #ffffff;
                            border: 1px solid rgba(0,0,0,0.08);
                            border-radius: 0.75rem;
                            box-shadow: 0 18px 50px rgba(0,0,0,0.12);
                            display: none !important;
                        }
                        #notif-list.show {
                            display: block !important;
                        }
                        #notif-list .dropdown-header,
                        #notif-list .dropdown-divider {
                            margin: 0;
                        }
                        #notif-list a.dropdown-item {
                            white-space: normal;
                        }
                        .notification-btn {
                            border: 1px solid rgba(10,37,64,0.12) !important;
                            background-color: #ffffff !important;
                            color: #0a2540 !important;
                            width: 56px;
                            height: 56px;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 12px 24px rgba(10,37,64,0.08);
                            position: relative;
                            z-index: 1051;
                            cursor: pointer;
                            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
                        }
                        .notification-btn:hover,
                        .notification-btn:focus {
                            background-color: #f8fbff !important;
                            transform: translateY(-1px);
                            box-shadow: 0 16px 32px rgba(10,37,64,0.12);
                            color: #0a2540 !important;
                        }
                        .notification-icon {
                            fill: currentColor;
                            stroke: none;
                            width: 28px;
                            height: 28px;
                        }
                        #notif-count {
                            min-width: 16px !important;
                            height: 16px !important;
                            font-size: 0.65rem;
                            line-height: 1;
                            border: 1px solid #fff;
                        }
                    </style>
                    <!-- BEGIN: Account Menu -->
                    <div class="intro-x dropdown w-8 h-8">
                        <div class="dropdown-toggle w-8 h-8 rounded-full overflow-hidden shadow-lg image-fit zoom-in" role="button" aria-expanded="false" data-tw-toggle="dropdown">
                            <img alt="Rider Profile" src="<?php echo htmlspecialchars($profile_image); ?>">
                        </div>
                        <div class="dropdown-menu w-56">
                            <ul class="dropdown-content bg-primary text-white">
                                <li class="p-2">
                                    <div class="font-medium"><?php echo htmlspecialchars($rider_name); ?></div>
                                    <div class="text-xs text-white/70 mt-0.5 dark:text-slate-500"><?php echo htmlspecialchars($rider_email); ?></div>
                                </li>
                                <li>
                                    <hr class="dropdown-divider border-white/[0.08]">
                                </li>
                                <li>
                                    <a href="myProfile.php" class="dropdown-item hover:bg-white/5"> <i data-lucide="user" class="w-4 h-4 mr-2"></i> Profile </a>
                                </li>
                                <li>
                                    <a href="javascript:;" class="dropdown-item hover:bg-white/5"> <i data-lucide="edit" class="w-4 h-4 mr-2"></i> Add Account </a>
                                </li>
                                <li>
                                    <a href="javascript:;" class="dropdown-item hover:bg-white/5"> <i data-lucide="lock" class="w-4 h-4 mr-2"></i> Reset Password </a>
                                </li>
                                <li>
                                    <a href="javascript:;" class="dropdown-item hover:bg-white/5"> <i data-lucide="help-circle" class="w-4 h-4 mr-2"></i> Help </a>
                                </li>
                                <li>
                                    <hr class="dropdown-divider border-white/[0.08]">
                                </li>
                                <li>
                                    <a href="logout.php" class="dropdown-item hover:bg-white/5"> <i data-lucide="toggle-right" class="w-4 h-4 mr-2"></i> Logout </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- END: Account Menu -->
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const statusButton = document.getElementById('rider-status-toggle');
                        if (!statusButton) return;

                        statusButton.addEventListener('click', function() {
                            const currentStatus = statusButton.textContent.trim().toLowerCase();
                            const targetStatus = currentStatus === 'active' ? 'inactive' : 'active';
                            statusButton.disabled = true;
                            statusButton.textContent = 'Updating...';

                            fetch('update_rider_status.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: 'status=' + encodeURIComponent(targetStatus)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    statusButton.textContent = targetStatus.charAt(0).toUpperCase() + targetStatus.slice(1);
                                    statusButton.className = 'py-2 px-4 rounded-full border text-sm font-medium transition ' +
                                        (targetStatus === 'active'
                                            ? 'border-success text-success bg-success/10'
                                            : 'border-danger text-danger bg-danger/10');
                                } else {
                                    alert(data.message || 'Unable to update status.');
                                    statusButton.textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
                                }
                            })
                            .catch(() => {
                                alert('Unable to update status at this time.');
                                statusButton.textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
                            })
                            .finally(() => { statusButton.disabled = false; });
                        });
                    });
                </script>                <script>
                    (function(){
                        function updateUnread() {
                            fetch('get_unread_count.php')
                                .then(function(r){ return r.json(); })
                                .then(function(d){
                                    var c = document.getElementById('notif-count');
                                    if (!c) return;
                                    if (d.success && d.unread > 0) {
                                        c.textContent = d.unread;
                                        c.style.display = 'inline-flex';
                                    } else {
                                        c.style.display = 'none';
                                    }
                                }).catch(function(){});
                        }

                        function loadNotifications() {
                            fetch('fetch_notifications.php')
                                .then(function(r){ return r.json(); })
                                .then(function(d){
                                    var list = document.getElementById('notif-list');
                                    if (!list) return;
                                    var html = '';
                                    if (Array.isArray(d) && d.length) {
                                        d.forEach(function(n){
                                            var isUnread = n.status === 'unread';
                                            var cls = isUnread ? 'fw-bold bg-light' : '';
                                            var icon = isUnread ? '<span class="d-inline-block bg-primary rounded-circle me-2" style="width:8px;height:8px;"></span>' : '';
                                            var messagePreview = n.message ? (n.message.length > 70 ? n.message.substring(0, 70) + '...' : n.message) : '';
                                            html += '<li><a href="javascript:void(0)" class="dropdown-item '+cls+' p-3" data-id="'+n.notification_id+'" data-title="'+escapeHtml(n.title)+'" data-message="'+escapeHtml(n.message)+'" data-time="'+escapeHtml(n.created_at || '')+'" onclick="viewNotification(this); return false;">' +
                                                    '<div class="d-flex align-items-start" style="gap:.75rem;">' + icon +
                                                    '<div><div style="font-weight:600; margin-bottom:4px;">'+escapeHtml(n.title||'')+'</div>' +
                                                    '<div style="font-size:0.9rem; color:#6c757d;">'+escapeHtml(messagePreview)+'</div>' +
                                                    '<div style="font-size:0.8rem; color:#adb5bd; margin-top:6px;">'+escapeHtml(n.created_at || '')+'</div></div></div></a></li>';
                                        });
                                    } else {
                                        html = '<li class="text-center p-3"><small class="text-muted">No notifications</small></li>';
                                    }
                                    list.innerHTML = '<li class="dropdown-header"><h6 class="mb-0">Notifications</h6></li><li><hr class="dropdown-divider"></li>' + html;
                                }).catch(function(){});
                        }

                        function escapeHtml(text) {
                            var div = document.createElement('div');
                            div.textContent = text || '';
                            return div.innerHTML;
                        }

                        function markAsRead(id) {
                            fetch('mark_notification_read.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'notification_id=' + encodeURIComponent(id)
                            }).then(function(){
                                updateUnread();
                                loadNotifications();
                            }).catch(function(){});
                        }

                        window.viewNotification = function(el) {
                            var id = el.getAttribute('data-id');
                            var title = el.getAttribute('data-title');
                            var message = el.getAttribute('data-message');
                            var time = el.getAttribute('data-time');

                            var modalHtml = '<div class="modal fade" id="notifModal" tabindex="-1" role="dialog" aria-labelledby="notificationLabel" aria-hidden="true">' +
                                '<div class="modal-dialog modal-dialog-centered" role="document">' +
                                '<div class="modal-content">' +
                                '<div class="modal-header" style="background-color:#e3f2fd; border-bottom:2px solid #0d6efd;">' +
                                '<h5 class="modal-title" id="notificationLabel">'+escapeHtml(title)+'</h5>' +
                                '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                                '</div>' +
                                '<div class="modal-body" style="line-height:1.6;">' +
                                '<p>'+escapeHtml(message)+'</p>' +
                                '<div style="margin-top:20px; padding-top:15px; border-top:1px solid #eee;">' +
                                '<small class="text-muted"><i class="bi bi-clock"></i> '+escapeHtml(time)+'</small>' +
                                '</div>' +
                                '</div>' +
                                '<div class="modal-footer">' +
                                '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>' +
                                '</div>' +
                                '</div>' +
                                '</div>' +
                                '</div>';

                            var existing = document.getElementById('notifModal');
                            if (existing) existing.remove();

                            document.body.insertAdjacentHTML('beforeend', modalHtml);
                            var modalEl = document.getElementById('notifModal');
                            if (modalEl && typeof bootstrap !== 'undefined') {
                                var modal = new bootstrap.Modal(modalEl, {backdrop: true});
                                modal.show();
                            }

                            if (id) {
                                markAsRead(id);
                            }
                        };

                        document.addEventListener('DOMContentLoaded', function(){
                            updateUnread();
                            loadNotifications();
                            setInterval(updateUnread, 30000);

                            var dropdown = document.getElementById('notif-dropdown');
                            var button = document.getElementById('notif-button');
                            var menu = document.getElementById('notif-list');
                            if (button && menu && dropdown) {
                                var closeDropdown = function(evt) {
                                    if (!evt || !dropdown.contains(evt.target)) {
                                        dropdown.classList.remove('show');
                                        menu.classList.remove('show');
                                        menu.style.display = 'none';
                                        button.setAttribute('aria-expanded', 'false');
                                        document.removeEventListener('click', closeDropdown);
                                    }
                                };

                                var openDropdown = function() {
                                    dropdown.classList.add('show');
                                    menu.classList.add('show');
                                    menu.style.display = 'block';
                                    button.setAttribute('aria-expanded', 'true');
                                    loadNotifications();
                                    setTimeout(function(){
                                        document.addEventListener('click', closeDropdown);
                                    }, 0);
                                };

                                var toggleDropdown = function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    if (dropdown.classList.contains('show')) {
                                        closeDropdown();
                                    } else {
                                        openDropdown();
                                    }
                                };

                                button.addEventListener('click', toggleDropdown);
                                document.addEventListener('keydown', function(evt) {
                                    if (evt.key === 'Escape') {
                                        closeDropdown();
                                    }
                                });
                            }
                        });
                    })();
                </script>                