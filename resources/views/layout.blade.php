<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HR System - Quản lý Nhân sự')</title>
    
    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    {{-- Custom CSS --}}
    <link href="{{ asset('css/layout.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <div class="wrapper">
        @auth
            @if(Auth::user()->role != 2)
            {{-- ========================================
                 SIDEBAR - Admin & Manager
                 ======================================== --}}
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-brand">
                    <a href="{{ route('dashboard') }}">
                        <i class="bi bi-hexagon-fill"></i>
                        <span>HR System</span>
                    </a>
                </div>
                
                <nav class="sidebar-nav">
                    <a href="{{ route('dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Tổng quan</span>
                    </a>
                    
                    @if(Auth::user()->role == 0)
                        <a href="{{ route('companies.index') }}" class="{{ request()->is('companies*') ? 'active' : '' }}">
                            <i class="bi bi-building"></i>
                            <span>Quản lý Công ty</span>
                        </a>
                    @endif

                    @if(Auth::user()->role == 1)
                        <a href="{{ route('companies.edit', Auth::user()->company_id) }}" class="{{ request()->is('companies/*/edit') ? 'active' : '' }}">
                            <i class="bi bi-building-check"></i>
                            <span>Thông tin Đơn vị</span>
                        </a>
                    @endif

                    @if(Auth::user()->role == 0 || Auth::user()->role == 1)
                        <a href="{{ route('users.index') }}" class="{{ request()->is('users*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            <span>Quản lý Nhân sự</span>
                        </a>
                        
                        <a href="{{ route('attendance.index') }}" class="{{ request()->is('attendance*') ? 'active' : '' }}">
                            <i class="bi bi-calendar2-check"></i>
                            <span>Chấm công</span>
                        </a>
                        
                        <a href="{{ route('notifications.create') }}" class="{{ request()->routeIs('notifications.create') ? 'active' : '' }}">
                            <i class="bi bi-megaphone-fill"></i>
                            <span>Gửi thông báo</span>
                        </a>
                        
                        <a href="{{ route('lunch.stats') }}" class="{{ request()->routeIs('lunch.stats') ? 'active' : '' }}">
                            <i class="bi bi-graph-up"></i>
                            <span>Thống kê cơm trưa</span>
                        </a>
                        
                        <a href="{{ route('salary.index') }}" class="{{ request()->is('salary*') ? 'active' : '' }}">
                            <i class="bi bi-wallet2"></i>
                            <span>Bảng lương</span>
                        </a>
                        
                        <a href="{{ route('lunch.config') }}" class="{{ request()->routeIs('lunch.config') ? 'active' : '' }}">
                            <i class="bi bi-gear-fill"></i>
                            <span>Cấu hình giá tiền</span>
                        </a>
                    @endif
                </nav>
                
                <div class="sidebar-footer">
                    <span>© {{ date('Y') }} HR System v1.0</span>
                </div>
            </aside>
            @endif
        @endauth

        {{-- ========================================
             MAIN CONTENT
             ======================================== --}}
        <div class="main-content {{ Auth::check() && Auth::user()->role == 2 ? 'full-width' : '' }}">
            
            {{-- HEADER --}}
            <header class="header">
                <div style="display: flex; align-items: center; gap: 16px;">
                    @auth
                        @if(Auth::user()->role != 2)
                            <button class="mobile-toggle" onclick="toggleSidebar()">
                                <i class="bi bi-list"></i>
                            </button>
                        @else
                            {{-- Logo cho Employee --}}
                            <a href="{{ route('dashboard') }}" class="header-brand">
                                <i class="bi bi-hexagon-fill"></i>
                                <span>HR System</span>
                            </a>
                            
                            {{-- Navigation cho Employee --}}
                            <nav class="header-nav" style="margin-left: 32px;">
                                <a href="{{ route('dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">
                                    <i class="bi bi-grid-1x2"></i>
                                    <span>Tổng quan</span>
                                </a>
                                <a href="{{ route('profile.show') }}" class="{{ request()->is('my-profile*') ? 'active' : '' }}">
                                    <i class="bi bi-person"></i>
                                    <span>Hồ sơ</span>
                                </a>
                                <a href="{{ route('attendance.history') }}" class="{{ request()->routeIs('attendance.history') ? 'active' : '' }}">
                                    <i class="bi bi-clock-history"></i>
                                    <span>Lịch sử</span>
                                </a>
                                <a href="{{ route('colleagues.index') }}" class="{{ request()->is('colleagues*') ? 'active' : '' }}">
                                    <i class="bi bi-people"></i>
                                    <span>Đồng nghiệp</span>
                                </a>
                                <a href="{{ route('lunch.index') }}" class="{{ request()->routeIs('lunch.*') ? 'active' : '' }}">
                                    <i class="bi bi-cup-hot"></i>
                                    <span>Đặt cơm</span>
                                </a>
                            </nav>
                        @endif
                    @endauth
                </div>

                <div class="user-menu">
                    @auth
                        {{-- Notification Bell --}}
                        @if(Auth::user()->role != 0)
                        <div class="dropdown">
                            <button class="notification-btn" onclick="toggleDropdown(this)">
                                <i class="bi bi-bell"></i>
                                @if(Auth::user()->unreadNotifications->count() > 0)
                                    <span class="notification-badge">{{ Auth::user()->unreadNotifications->count() }}</span>
                                @endif
                            </button>
                            <div class="notification-dropdown dropdown-menu-custom">
                                <div class="notification-header">
                                    <h6><i class="bi bi-bell-fill me-2"></i>Thông báo</h6>
                                    @if(Auth::user()->unreadNotifications->count() > 0)
                                        <a href="{{ route('notifications.markAllRead') }}">Đánh dấu đã đọc</a>
                                    @endif
                                </div>
                                <div class="notification-list">
                                    @forelse(Auth::user()->notifications->take(5) as $notification)
                                        <a href="#" class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
                                            <div class="notification-title">{{ $notification->data['title'] ?? 'Thông báo mới' }}</div>
                                            <p class="notification-text">{{ Str::limit($notification->data['content'] ?? '', 80) }}</p>
                                        </a>
                                    @empty
                                        <div class="notification-empty">
                                            <i class="bi bi-bell-slash"></i>
                                            Không có thông báo nào
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- User Dropdown --}}
                        @php
                            $user = Auth::user();
                            $avatar = $user->avatar;
                            if (filter_var($avatar, FILTER_VALIDATE_URL)) { 
                                $userAvatar = $avatar; 
                            } elseif ($avatar) { 
                                $userAvatar = asset('storage/' . $avatar); 
                            } else { 
                                $userAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=6366f1&color=fff&bold=true'; 
                            }
                        @endphp

                        <div class="dropdown">
                            <button class="user-dropdown-btn" onclick="toggleDropdown(this)">
                                <div class="user-info">
                                    <div class="user-name">{{ $user->name }}</div>
                                    <div class="user-role">
                                        @if($user->role == 0)
                                            <i class="bi bi-shield-fill text-danger me-1"></i>Administrator
                                        @elseif($user->role == 1)
                                            <i class="bi bi-star-fill text-warning me-1"></i>Quản lý
                                        @else
                                            <i class="bi bi-person-fill text-info me-1"></i>Nhân viên
                                        @endif
                                    </div>
                                </div>
                                <img src="{{ $userAvatar }}" class="user-avatar" alt="{{ $user->name }}">
                            </button>
                            <div class="dropdown-menu-custom">
                                @if(Auth::user()->role == 2)
                                <a href="{{ route('profile.show') }}" class="dropdown-item-custom">
                                    <i class="bi bi-person-circle"></i>
                                    <span>Hồ sơ của tôi</span>
                                </a>
                                @endif
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item-custom text-danger">
                                        <i class="bi bi-box-arrow-right"></i>
                                        <span>Đăng xuất</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            {{-- CONTENT --}}
            <div class="content">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="alert-box alert-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert-box alert-error">
                        <i class="bi bi-x-circle-fill"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert-box alert-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                
                @yield('content')
            </div>

            {{-- FOOTER
            <footer class="footer">
                <div class="footer-content">
                    <span>© {{ date('Y') }} HR System</span>
                    <span class="footer-divider">•</span>
                    <span>Phiên bản 1.0</span>
                    <span class="footer-divider">•</span>
                    <span>Powered with <i class="bi bi-heart-fill text-danger"></i></span>
                </div>
            </footer> --}}
        </div>
    </div>

    {{-- Sidebar Overlay (Mobile) --}}
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    {{-- Custom JS --}}
    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
            document.body.classList.toggle('sidebar-open');
        }
        
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
        
        // Toggle Dropdown
        function toggleDropdown(btn) {
            const menu = btn.nextElementSibling;
            document.querySelectorAll('.dropdown-menu-custom.show').forEach(el => {
                if (el !== menu) el.classList.remove('show');
            });
            menu.classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu-custom.show').forEach(el => {
                    el.classList.remove('show');
                });
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert-box').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Add CSRF token to all AJAX requests
        document.addEventListener('DOMContentLoaded', function() {
            const token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                window.csrfToken = token.getAttribute('content');
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>
