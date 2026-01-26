<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhân sự</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Tinh chỉnh nhỏ cho giao diện sạch sẽ hơn */
        body {
            background-color: #f5f7f9; /* Màu nền xám nhẹ dịu mắt */
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e1e4e8; /* Viền mỏng thay vì đổ bóng đậm */
        }
        .nav-link {
            color: #6c757d !important; /* Màu chữ menu xám */
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: color 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: #0d6efd !important; /* Hover hoặc Active màu xanh */
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    {{-- Navbar trắng, sạch sẽ --}}
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            {{-- Logo đơn giản --}}
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
                        @if(Auth::user()->role == 0)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('companies*') ? 'active' : '' }}" 
                                href="{{ route('companies.index') }}">Công ty</a>
                            </li>
                        @endif

                        @if(Auth::user()->role == 1)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('companies/*/edit') ? 'active' : '' }}" 
                                href="{{ route('companies.edit', Auth::user()->company_id) }}">
                                Đơn vị
                                </a>
                            </li>
                        @endif

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
                        @endif

                        @if(Auth::user()->role == 2)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('my-profile*') ? 'active fw-bold' : '' }}" 
                                href="{{ route('profile.show') }}">Hồ sơ cá nhân</a>
                            </li>
                            
                            {{-- MENU MỚI THÊM --}}
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('attendance.history') ? 'active fw-bold' : '' }}" 
                                href="{{ route('attendance.history') }}">Lịch sử chấm công</a>
                            </li>
                            {{-- KẾT THÚC MENU MỚI --}}

                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('colleagues*') ? 'active fw-bold' : '' }}" 
                                href="{{ route('colleagues.index') }}">Danh sách đồng nghiệp</a>
                            </li>
                        @endif
                    @endauth
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    @auth
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

                        {{-- Dropdown User tối giản --}}
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
                    @else
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold">Đăng nhập</a>
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
        
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>