<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhân sự</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    {{-- Đảm bảo file public/css/layout.css đã có code CSS tôi đưa ở bước trước --}}
    <link href="{{ asset('./resources/css/layout.css') }}" rel="stylesheet">
</head>
<body>
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            {{-- Logo --}}
            <a class="navbar-brand fw-bold text-dark d-flex align-items-center" href="{{ route('dashboard') }}">
                <i class="bi bi-buildings-fill text-primary me-2"></i> HR SYSTEM
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" 
                        href="{{ route('dashboard') }}">Tổng quan</a>
                    </li>

                    @auth
                        {{-- MENU CHO ADMIN --}}
                        @if(Auth::user()->role == 0)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('companies*') ? 'active' : '' }}" 
                                href="{{ route('companies.index') }}">Công ty</a>
                            </li>
                        @endif

                        {{-- MENU CHO QUẢN LÝ --}}
                        @if(Auth::user()->role == 1)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('companies/*/edit') ? 'active' : '' }}" 
                                href="{{ route('companies.edit', Auth::user()->company_id) }}">
                                Đơn vị
                                </a>
                            </li>
                        @endif

                        {{-- MENU CHUNG CHO ADMIN & QUẢN LÝ --}}
                        @if(Auth::user()->role == 0 || Auth::user()->role == 1)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}" 
                                href="{{ route('users.index') }}">Nhân sự</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('attendance*') ? 'active' : '' }}" 
                                href="{{ route('attendance.index') }}">Chấm công</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('salary*') ? 'active' : '' }}" 
                                href="{{ route('salary.index') }}">Bảng lương</a>
                            </li>
                            
                            {{-- NÚT GỬI THÔNG BÁO --}}
                            <li class="nav-item">
                                <a href="{{ route('notifications.create') }}" class="nav-link {{ request()->routeIs('notifications.create') ? 'active' : '' }}">
                                    <span>Gửi TB</span>
                                </a>
                            </li>
                        @endif

                        {{-- MENU CHO NHÂN VIÊN --}}
                        @if(Auth::user()->role == 2)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('my-profile*') ? 'active' : '' }}" 
                                href="{{ route('profile.show') }}">Hồ sơ</a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('attendance.history') ? 'active' : '' }}" 
                                href="{{ route('attendance.history') }}">Lịch sử chấm công</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('colleagues*') ? 'active' : '' }}" 
                                href="{{ route('colleagues.index') }}">Đồng nghiệp</a>
                            </li>
                        @endif

                    @endauth
                </ul>

                {{-- PHẦN BÊN PHẢI NAVBAR --}}
                <ul class="navbar-nav ms-auto align-items-center">
                    @auth
                        @if(Auth::user()->role != 0)
                        <li class="nav-item dropdown me-3 width: 1000px;">
                            <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell-fill fs-5 text-secondary"></i>
                                @if(Auth::user()->unreadNotifications->count() > 0)
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                                        {{ Auth::user()->unreadNotifications->count() }}
                                    </span>
                                @endif
                            </a>

                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown">
                                <li class="d-flex justify-content-between align-items-center px-3 py-2 bg-white border-bottom sticky-top">
                                    <h6 class="mb-0 fw-bold text-dark">Thông báo của bạn</h6>
                                    @if(Auth::user()->unreadNotifications->count() > 0)
                                        <a href="{{ route('notify.read') }}" class="text-decoration-none small fw-bold text-primary">
                                            Đánh dấu đã đọc hết
                                        </a>
                                    @endif
                                </li>

                                <div style="max-height: 400px; overflow-y: auto;">
                                    @forelse(Auth::user()->notifications as $notification)
                                        <li>
                                            <a class="dropdown-item p-3 notification-item border-bottom {{ $notification->read_at ? 'bg-white' : 'bg-light' }}" href="#">
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                                             style="width: 38px; height: 38px; background-color: {{ $notification->read_at ? '#e9ecef' : '#cfe2ff' }}; color: {{ $notification->read_at ? '#6c757d' : '#0d6efd' }};">
                                                            <i class="bi bi-envelope-fill"></i>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="flex-grow-1">
                                                    
                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                            <strong class="text-dark" style="font-size: 0.95rem;">
                                                                {{ $notification->data['title'] ?? 'Thông báo hệ thống' }}
                                                            </strong>
                                                            <small class="text-muted ms-2 text-nowrap" style="font-size: 0.75rem;">
                                                                {{ $notification->created_at->diffForHumans() }}
                                                            </small>
                                                        </div>
                                                        
                                                     
                                                        <p class="mb-0 text-secondary text-wrap" style="font-size: 0.9rem; line-height: 1.5;">
                                                            {{ $notification->data['content'] ?? '' }}
                                                        </p>
                                                    </div>

                                                    @if(!$notification->read_at)
                                                        <div class="ms-2 pt-1">
                                                            <span class="badge bg-primary rounded-circle p-1"></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </a>
                                        </li>
                                    @empty
                                        <li class="text-center py-5">
                                            <i class="bi bi-inbox text-muted fs-1 mb-3 d-block"></i>
                                            <span class="text-muted">Không có thông báo nào</span>
                                        </li>
                                    @endforelse
                                </div>

                                
                            </ul>
                        </li>
                        @endif
                        @php
                            $user = Auth::user();
                            $avatar = $user->avatar;
                            
                            if (filter_var($avatar, FILTER_VALIDATE_URL)) {
                                $userAvatar = $avatar;
                            } elseif ($avatar) {
                                $userAvatar = asset('storage/' . $avatar);
                            } else {
                                $userAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=f0f2f5&color=333';
                            }
                        @endphp

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center pe-0" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="text-end me-2 d-none d-lg-block">
                                    <div class="fw-bold text-dark small">{{ $user->name }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        @if($user->role == 0) Super Admin
                                        @elseif($user->role == 1) Quản lý
                                        @else Nhân viên @endif
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

    <div class="container pb-5">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>