<div class="top-bar -mx-4 px-4 md:mx-0 md:px-0">
                    <!-- BEGIN: Breadcrumb -->
                    <!-- <nav aria-label="breadcrumb" class="-intro-x mr-auto hidden sm:flex">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="#">Application</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>  -->
                    <!-- END: Breadcrumb -->
                    <!-- BEGIN: Search -->
                    
                    <!-- END: Search -->
                    <!-- BEGIN: Center Title -->
                    <div class="topbar-title">Dessert Magic</div>
                    <!-- END: Center Title -->
                    <!-- BEGIN: Notifications -->
                    <?php if (!empty($_SESSION['vendor_id']) || !empty($_SESSION['admin_id'])): ?>
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
                    <!-- BEGIN: Account Menu -->
                    <!-- <div class="intro-x dropdown w-8 h-8">
                        <div class="dropdown-toggle w-8 h-8 rounded-full overflow-hidden shadow-lg image-fit zoom-in" role="button" aria-expanded="false" data-tw-toggle="dropdown">
                            <img alt="Midone - HTML Admin Template" src="dist/images/profile-5.jpg">
                        </div>
                        <div class="dropdown-menu w-56">
                            <ul class="dropdown-content custom-pastel">
                                <li class="p-2">
                                    <div class="font-medium"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></div>
                                    <div class="text-xs custom-pastel-text mt-0.5"><?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?></div>
                                </li>
                                <li>
                                    <hr class="dropdown-divider border-white/[0.08]">
                                </li>
                                <li>
                                    <a href="logout.php" class="dropdown-item hover:bg-white/5"> <i data-lucide="toggle-right" class="w-4 h-4 mr-2"></i> Logout </a>
                                </li>
                            </ul>
                        </div>
                    </div> -->
                    <!-- END: Account Menu -->
                    <style>
                        /* pastel background for admin dropdown */
                        .custom-pastel { background: #f4ebe6; color: #663f1f; }
                        .custom-pastel-text { color: #663f1f; }
                        .custom-pastel a { color: #663f1f; }
                        .custom-pastel a:hover { background: rgba(102,63,31,0.1); }

                        /* override breadcrumb green to brown theme */
                        .top-bar .breadcrumb .breadcrumb-item a { color: #7c4a2f !important; }
                        .top-bar .breadcrumb .breadcrumb-item.active { color: #5d3a20 !important; }
                        .top-bar .breadcrumb .breadcrumb-item + .breadcrumb-item::before { color: #8a5e40 !important; }

                        .top-bar {
                            display: flex;
                            align-items: center;
                            justify-content: flex-end !important;
                            width: 100% !important;
                            gap: 1rem;
                            padding: 1rem 1.5rem;
                            overflow: visible !important;
                            position: relative !important;
                            z-index: 1120 !important;
                            pointer-events: auto !important;
                            min-height: 90px;
                            background: linear-gradient(135deg, #f4f7ff 0%, #ffffff 65%);
                            border-bottom: 1px solid rgba(10,37,64,0.08);
                            border-radius: 0 0 18px 18px;
                            box-shadow: 0 16px 40px rgba(15,23,42,0.08);
                        }

                        .top-bar .topbar-title {
                            position: absolute;
                            left: 50%;
                            transform: translateX(-50%);
                            font-size: 1.1rem;
                            font-weight: 800;
                            color: #0a2540;
                            letter-spacing: 0.14em;
                            text-transform: uppercase;
                            white-space: nowrap;
                            padding: 0.75rem 1.25rem;
                            background: rgba(255,255,255,0.95);
                            border-radius: 999px;
                            border: 1px solid rgba(10,37,64,0.08);
                            box-shadow: 0 10px 30px rgba(10,37,64,0.08);
                        }

                        @media (max-width: 768px) {
                            .top-bar .topbar-title {
                                position: static;
                                transform: none;
                                margin: 0 auto;
                                background: transparent;
                                border: none;
                                box-shadow: none;
                                font-size: 1rem;
                                padding: 0;
                            }
                        }

                        .flex.overflow-hidden {
                            overflow: visible !important;
                        }

                        .content {
                            overflow: visible !important;
                        }

                        #notif-dropdown {
                            margin-left: auto;
                            position: relative;
                            z-index: 1130 !important;
                            display: inline-block;
                            pointer-events: auto !important;
                        }

                        .top-bar .dropdown-menu,
                        .dropdown-menu {
                            position: absolute !important;
                            top: calc(100% + 0.5rem) !important;
                            right: 0 !important;
                            left: auto !important;
                            z-index: 1150 !important;
                            width: 380px;
                            min-width: 280px;
                            background: #fff;
                            border: 1px solid rgba(0,0,0,0.08);
                            border-radius: 0.75rem;
                            box-shadow: 0 18px 50px rgba(0,0,0,0.12);
                            display: none !important;
                        }

                        .dropdown-menu.show {
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

                        .notification-btn:hover,
                        .notification-btn:focus {
                            background-color: #f3f6f9 !important;
                            color: #0a2540 !important;
                        }

                        .notification-icon {
                            fill: currentColor;
                            stroke: none;
                            width: 28px;
                            height: 28px;
                        }

                        .notif-reject-item {
                            border-left: 3px solid #dc3545;
                            background-color: #fff5f5;
                        }

                        .notif-reject-badge {
                            display: inline-block;
                            font-size: 0.7rem;
                            line-height: 1;
                            color: #842029;
                            background-color: #f8d7da;
                            border-radius: 0.75rem;
                            padding: 0.15rem 0.4rem;
                            margin-left: 0.45rem;
                        }

                        #notif-count {
                            min-width: 16px !important;
                            height: 16px !important;
                            font-size: 0.65rem;
                            line-height: 1;
                            border: 1px solid #fff;
                        }
                    </style>
                <?php if (!empty($_SESSION['vendor_id']) || !empty($_SESSION['admin_id'])): ?>
                <script>
                (function(){
                    var isAdmin = <?php echo !empty($_SESSION['admin_id']) ? 'true' : 'false'; ?>;
                    
                    function getAdminEndpoint(path) {
                        var loc = window.location.pathname;
                        var index = loc.indexOf('/admin/');
                        if (index === -1) {
                            return path;
                        }
                        return loc.slice(0, index + 7) + path;
                    }

                    function updateUnread(){
                        var url = isAdmin ? getAdminEndpoint('get_unread_count.php') : '../../user/get_unread_count.php';
                        fetch(url)
                          .then(r=>r.json())
                          .then(d=>{
                            var c = document.getElementById('notif-count');
                            if(!c) return;
                            if(d.success && d.unread>0){
                              c.textContent=d.unread;
                              c.style.display='inline-block';
                            } else {
                              c.style.display='none';
                            }
                          }).catch(function(err){
                            console.log('Error updating unread count:', err);
                          });
                    }
                    
                    function loadNotifications(){
                        var url = isAdmin ? getAdminEndpoint('fetch_notifications.php') : '../../vendor/fetch_notifications.php';
                        fetch(url)
                          .then(r=>r.json())
                          .then(d=>{
                            var list = document.getElementById('notif-list');
                            if(!list) return;
                            var html = '';
                            if(Array.isArray(d) && d.length){
                                d.forEach(function(n){
                                  var isRejected = /reject/i.test(n.title || n.message);
                                  var cls = n.status === 'unread' ? 'fw-bold bg-light' : '';
                                  if (isRejected) cls += ' notif-reject-item';
                                  var icon = isRejected
                                      ? '<i class="bi bi-exclamation-circle-fill text-danger" style="font-size:0.75rem; margin-right:8px;"></i>'
                                      : (n.status === 'unread' ? '<i class="bi bi-circle-fill" style="color:#0d6efd; font-size:0.35rem; margin-right:8px;"></i>' : '');
                                  var badge = isRejected ? '<span class="notif-reject-badge">Rider Rejected</span>' : '';
                                  var messagePreview = n.message.length > 70 ? n.message.substring(0, 70) + '...' : n.message;
                                  html += '<li><a href="javascript:void(0)" class="dropdown-item '+cls+' p-3" data-id="'+n.notification_id+'" data-title="'+escapeHtml(n.title)+'" data-message="'+escapeHtml(n.message)+'" data-time="'+n.created_at+'" onclick="viewNotification(this); return false;">' +
                                          icon +
                                          '<div style="font-weight:600; margin-bottom:4px;">'+escapeHtml(n.title)+badge+'</div>' +
                                          '<div style="font-size:0.9rem; color:#666;">'+escapeHtml(messagePreview)+'</div>' +
                                          '<div style="font-size:0.8rem; color:#999; margin-top:4px;">'+n.created_at+'</div></a></li>';
                                });
                            } else {
                                html = '<li class="text-center p-3"><small class="text-muted">No notifications</small></li>';
                            }
                            list.innerHTML = '<li class="dropdown-header"><h6 class="mb-0">Notifications</h6></li><li><hr class="dropdown-divider"></li>' + html;
                          }).catch(function(err){
                            console.log('Error loading notifications:', err);
                          });
                    }
                    
                    function escapeHtml(text) {
                        var div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }
                    
                    function markAsRead(id){
                        var url = isAdmin ? getAdminEndpoint('mark_notification_read.php') : '../../vendor/mark_notification_read.php';
                        fetch(url, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'id='+encodeURIComponent(id)
                        }).then(function(){ 
                            updateUnread();
                            loadNotifications();
                        }).catch(function(err){
                            console.log('Error marking as read:', err);
                        });
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
                            '<h5 class="modal-title" id="notificationLabel">'+title+'</h5>' +
                            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                            '</div>' +
                            '<div class="modal-body">' +
                            '<p style="line-height:1.6;">'+message+'</p>' +
                            '<div style="margin-top:20px; padding-top:15px; border-top:1px solid #eee;">' +
                            '<small class="text-muted"><i class="bi bi-clock"></i> '+time+'</small>' +
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
                        var modal = new bootstrap.Modal(document.getElementById('notifModal'), {backdrop: true});
                        modal.show();
                        
                        markAsRead(id);
                    };
                    
                    document.addEventListener('DOMContentLoaded', function(){
                        updateUnread();
                        loadNotifications();
                        setInterval(updateUnread, 30000);
                        
                        var dropdown = document.getElementById('notif-dropdown');
                        var button = document.getElementById('notif-button');
                        var menu = document.getElementById('notif-list');
                        if(button && menu){
                            var closeDropdown = function(evt){
                                if (!evt || !dropdown.contains(evt.target)){
                                    dropdown.classList.remove('show');
                                    menu.classList.remove('show');
                                    menu.style.display = 'none';
                                    button.setAttribute('aria-expanded', 'false');
                                    document.removeEventListener('click', closeDropdown);
                                }
                            };

                            var openDropdown = function(){
                                dropdown.classList.add('show');
                                menu.classList.add('show');
                                menu.style.display = 'block';
                                button.setAttribute('aria-expanded', 'true');
                                loadNotifications();
                                setTimeout(function(){
                                    document.addEventListener('click', closeDropdown);
                                }, 0);
                            };

                            var toggleDropdown = function(e){
                                e.preventDefault();
                                e.stopPropagation();
                                var isOpen = dropdown.classList.contains('show');
                                if(isOpen){
                                    closeDropdown();
                                } else {
                                    openDropdown();
                                }
                            };

                            button.addEventListener('click', toggleDropdown);
                            document.addEventListener('keydown', function(evt){
                                if(evt.key === 'Escape'){
                                    closeDropdown();
                                }
                            });
                        }
                    });
                })();
                </script>
                <?php endif; ?>
                </div>
                