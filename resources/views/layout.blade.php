<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhân sự</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-uppercase" href="{{ route('dashboard') }}">HR System</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('dashboard') ? 'active fw-bold' : '' }}" 
                        href="{{ route('dashboard') }}">Trang chủ</a>
                    </li>

                    @auth
                        @if(Auth::user()->role == 0)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('companies*') ? 'active fw-bold' : '' }}" 
                                href="{{ route('companies.index') }}">Quản lý Công ty</a>
                            </li>
                        @endif

                        @if(Auth::user()->role == 1)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('companies/*/edit') ? 'active fw-bold' : '' }}" 
                                href="{{ route('companies.edit', Auth::user()->company_id) }}">
                                Thông tin Công ty
                                </a>
                            </li>
                        @endif

                        @if(Auth::user()->role == 0 || Auth::user()->role == 1)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('users*') ? 'active fw-bold' : '' }}" 
                                href="{{ route('users.index') }}">Quản lý Nhân sự</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('attendance*') ? 'active fw-bold' : '' }}" 
                                href="{{ route('attendance.index') }}">Chấm công</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('salary*') ? 'active fw-bold' : '' }}" 
                                href="{{ route('salary.index') }}">Tính lương</a>
                            </li>
                        @endif

                        @if(Auth::user()->role == 2)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('my-profile*') ? 'active fw-bold' : '' }}" 
                                href="{{ route('profile.show') }}">Hồ sơ cá nhân</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('colleagues*') ? 'active fw-bold' : '' }}" 
                                href="{{ route('colleagues.index') }}">Danh sách đồng nghiệp</a>
                            </li>
                        @endif
                    @endauth
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    @auth
                        <li class="nav-item me-3 text-light">
                            <span class="opacity-75">Xin chào,</span> 
                            <span class="fw-bold">{{ Auth::user()->name }}</span>
                            
                            @if(Auth::user()->role == 0)
                                <span class="badge bg-danger border border-light ms-1">Admin</span>
                            @elseif(Auth::user()->role == 1)
                                <span class="badge bg-warning text-dark border border-light ms-1">Quản lý</span>
                            @else
                                <span class="badge bg-info text-dark border border-light ms-1">Nhân viên</span>
                            @endif
                        </li>

                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-light btn-sm fw-bold text-primary">
                                    Đăng xuất
                                </button>
                            </form>
                        </li>
                    @else
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="btn btn-light btn-sm fw-bold text-primary">Đăng nhập</a>
                        </li>
                    @endauth
                </ul>

            </div>
        </div>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>