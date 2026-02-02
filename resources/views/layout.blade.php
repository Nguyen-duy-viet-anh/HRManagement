<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhân sự</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <link href="{{ asset('./resources/css/layout.css') }}" rel="stylesheet">
    <style>
        body { overflow-x: hidden; background-color: #f8f9fa; } /* Thêm màu nền nhẹ cho đẹp */
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { min-height: 100vh; width: 250px; margin-left: 0; transition: margin .25s ease-out; }
        #sidebar-wrapper .list-group { width: 250px; }
        #page-content-wrapper { width: 100%; }
        
        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -250px; }
            body.sb-sidenav-toggled #sidebar-wrapper { margin-left: 0; }
        }
        @media (min-width: 769px) {
            body.sb-sidenav-toggled #sidebar-wrapper { margin-left: -250px; }
        }
        .list-group-item.active { z-index: 2; color: #fff; background-color: #0d6efd; border-color: #0d6efd; }

        .role-2-nav .nav-link.active { font-weight: bold; color: #0d6efd !important; border-bottom: 2px solid #0d6efd; }

        .container-role-2 {
            width: 80%;       
            margin: 0 auto;       
            max-width: 1400px;    
        }
        @media (max-width: 992px) {
            .container-role-2 { width: 96%; }
        }
    </style>
</head>
<body>
    <div class="d-flex" id="wrapper">
        @auth
            @if(Auth::user()->role != 2)
            <div class="bg-white border-end" id="sidebar-wrapper">
                <div class="sidebar-heading border-bottom bg-light p-4">
                    <a class="text-decoration-none fw-bold text-dark d-flex align-items-center" href="{{ route('dashboard') }}">
                        <i class="bi bi-buildings-fill text-primary me-2"></i> HR SYSTEM
                    </a>
                </div>
                <div class="list-group list-group-flush">
                    <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Tổng quan</a>
                    
                    @if(Auth::user()->role == 0)
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->is('companies*') ? 'active' : '' }}" href="{{ route('companies.index') }}">Công ty</a>
                    @endif

                    @if(Auth::user()->role == 1)
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->is('companies/*/edit') ? 'active' : '' }}" href="{{ route('companies.edit', Auth::user()->company_id) }}">Đơn vị</a>
                    @endif

                    @if(Auth::user()->role == 0 || Auth::user()->role == 1)
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->is('users*') ? 'active' : '' }}" href="{{ route('users.index') }}">Nhân sự</a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->is('attendance*') ? 'active' : '' }}" href="{{ route('attendance.index') }}">Chấm công</a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->is('salary*') ? 'active' : '' }}" href="{{ route('salary.index') }}">Bảng lương</a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->routeIs('lunch.stats') ? 'active' : '' }}" href="{{ route('lunch.stats') }}">Báo cáo & Thống kê</a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->routeIs('lunch.config') ? 'active' : '' }}" href="{{ route('lunch.config') }}">Cấu hình mệnh giá</a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 {{ request()->routeIs('notifications.create') ? 'active' : '' }}" href="{{ route('notifications.create') }}">Gửi TB</a>
                    @endif
                </div>
            </div>
            @endif
        @endauth

        <div id="page-content-wrapper">
            
            {{-- NAVBAR --}}
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom mb-4 shadow-sm">
                <div class="{{ Auth::user() && Auth::user()->role == 2 ? 'container-role-2' : 'container-fluid' }}">
                    @auth
                        @if(Auth::user()->role != 2)
                            <button class="btn btn-outline-primary" id="sidebarToggle"><i class="bi bi-list"></i></button>
                        @else
                            <a class="navbar-brand fw-bold text-dark d-flex align-items-center" href="{{ route('dashboard') }}">
                                <i class="bi bi-buildings-fill text-primary me-2"></i> HR SYSTEM
                            </a>
                        @endif
                    @endauth

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        
                        {{-- MENU NGANG (ROLE 2) --}}
                        @auth
                            @if(Auth::user()->role == 2)
                            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-4 role-2-nav">
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Tổng quan</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->is('my-profile*') ? 'active' : '' }}" href="{{ route('profile.show') }}">Hồ sơ</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('attendance.history') ? 'active' : '' }}" href="{{ route('attendance.history') }}">Lịch sử</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->is('colleagues*') ? 'active' : '' }}" href="{{ route('colleagues.index') }}">Đồng nghiệp</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('lunch.*') ? 'active' : '' }}" href="{{ route('lunch.index') }}">Ăn trưa</a>
                                </li>
                            </ul>
                            @endif
                        @endauth

                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0 align-items-center">
                            @auth
                                @if(Auth::user()->role != 0)
                                <li class="nav-item dropdown me-3">
                                    <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-bell-fill fs-5 text-secondary"></i>
                                        @if(Auth::user()->unreadNotifications->count() > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                                                {{ Auth::user()->unreadNotifications->count() }}
                                            </span>
                                        @endif
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                                        <li class="d-flex justify-content-between align-items-center px-3 py-2 bg-white border-bottom sticky-top">
                                            <h6 class="mb-0 fw-bold text-dark">Thông báo</h6>
                                            @if(Auth::user()->unreadNotifications->count() > 0)
                                                <a href="{{ route('notifications.markAllRead') }}" class="text-decoration-none small fw-bold text-primary">Đọc hết</a>
                                            @endif
                                        </li>
                                        <div style="max-height: 400px; overflow-y: auto;">
                                            @forelse(Auth::user()->notifications as $notification)
                                                <li>
                                                    <a class="dropdown-item p-3 notification-item border-bottom {{ $notification->read_at ? 'bg-white' : 'bg-light' }}" href="#">
                                                        <div class="d-flex">
                                                            <div class="flex-grow-1">
                                                                <strong class="text-dark small">{{ $notification->data['title'] ?? 'TB Hệ thống' }}</strong>
                                                                <p class="mb-0 text-secondary text-wrap small">{{ $notification->data['content'] ?? '' }}</p>
                                                            </div>
                                                            @if(!$notification->read_at)
                                                                <div class="ms-2"><span class="badge bg-primary rounded-circle p-1"></span></div>
                                                            @endif
                                                        </div>
                                                    </a>
                                                </li>
                                            @empty
                                                <li class="text-center py-3 text-muted small">Không có thông báo</li>
                                            @endforelse
                                        </div>
                                    </ul>
                                </li>
                                @endif

                                @php
                                    $user = Auth::user();
                                    $avatar = $user->avatar;
                                    if (filter_var($avatar, FILTER_VALIDATE_URL)) { $userAvatar = $avatar; } 
                                    elseif ($avatar) { $userAvatar = asset('storage/' . $avatar); } 
                                    else { $userAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=f0f2f5&color=333'; }
                                @endphp

                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle d-flex align-items-center pe-0" href="#" role="button" data-bs-toggle="dropdown">
                                        <div class="text-end me-2 d-none d-lg-block">
                                            <div class="fw-bold text-dark small">{{ $user->name }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                {{ $user->role == 0 ? 'Admin' : ($user->role == 1 ? 'Quản lý' : 'Nhân viên') }}
                                            </div>
                                        </div>
                                        <img src="{{ $userAvatar }}" width="36" height="36" class="rounded-circle border" style="object-fit: cover;">
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end mt-2">
                                        <li>
                                            <form action="{{ route('logout') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-danger fw-bold">
                                                    <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </li>
                            @endauth
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="{{ Auth::user() && Auth::user()->role == 2 ? 'container-role-2' : 'container-fluid px-4' }} pb-5">
                
                @if(session('success'))
                    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4">
                        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', event => {
            const sidebarToggle = document.body.querySelector('#sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', event => {
                    event.preventDefault();
                    document.body.classList.toggle('sb-sidenav-toggled');
                });
            }
        });
    </script>
</body>
</html>