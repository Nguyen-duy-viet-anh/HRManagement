<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - HR System</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4f46e5 100%);
            position: relative;
            overflow: hidden;
        }
        
        /* Animated background shapes */
        .bg-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            animation: float 20s infinite ease-in-out;
        }
        
        .shape-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            animation-delay: -5s;
        }
        
        .shape-3 {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 30%;
            animation-delay: -10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, 30px) scale(1.1); }
        }
        
        /* Left side - Branding */
        .brand-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            color: #fff;
            position: relative;
            z-index: 1;
        }
        
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 40px;
        }
        
        .brand-logo i {
            font-size: 56px;
            background: linear-gradient(135deg, #818cf8, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .brand-logo span {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -1px;
        }
        
        .brand-tagline {
            font-size: 18px;
            opacity: 0.8;
            text-align: center;
            max-width: 400px;
            line-height: 1.6;
        }
        
        .brand-features {
            margin-top: 60px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 16px;
            opacity: 0.9;
        }
        
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .feature-text h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .feature-text p {
            font-size: 13px;
            opacity: 0.7;
            margin: 0;
        }
        
        /* Right side - Login form */
        .login-side {
            width: 520px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            position: relative;
            z-index: 1;
        }
        
        .login-container {
            width: 100%;
            max-width: 380px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #6b7280;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
            transition: color 0.2s;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.2s;
            background: #f9fafb;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        .form-control:focus + i,
        .input-wrapper:focus-within i {
            color: var(--primary);
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .login-footer p {
            font-size: 13px;
            color: #9ca3af;
        }
        
        /* Demo accounts */
        .demo-accounts {
            margin-top: 24px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 12px;
            border: 1px dashed #e5e7eb;
        }
        
        .demo-accounts h5 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .demo-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 13px;
            color: #374151;
        }
        
        .demo-item:not(:last-child) {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .demo-role {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .demo-role i {
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .brand-side {
                display: none;
            }
            
            .login-side {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 24px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    {{-- Background shapes --}}
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    {{-- Left side - Branding --}}
    <div class="brand-side">
        <div class="brand-logo">
            <i class="bi bi-hexagon-fill"></i>
            <span>HR System</span>
        </div>
        <p class="brand-tagline">
            Hệ thống quản lý nhân sự hiện đại, tối ưu hóa quy trình làm việc và nâng cao hiệu suất doanh nghiệp
        </p>
        
        <div class="brand-features">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="feature-text">
                    <h4>Quản lý Nhân sự</h4>
                    <p>Theo dõi thông tin nhân viên dễ dàng</p>
                </div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="feature-text">
                    <h4>Chấm công Thông minh</h4>
                    <p>Tự động hóa quy trình chấm công</p>
                </div>
            </div>
            
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div class="feature-text">
                    <h4>Báo cáo Chi tiết</h4>
                    <p>Thống kê và phân tích dữ liệu</p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Right side - Login form --}}
    <div class="login-side">
        <div class="login-container">
            <div class="login-header">
                <h1>Chào mừng trở lại!</h1>
                <p>Đăng nhập để tiếp tục công việc</p>
            </div>
            
            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            
            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-control" 
                               value="{{ old('email', 'admin@gmail.com') }}" 
                               placeholder="Nhập địa chỉ email" required>
                        <i class="bi bi-envelope"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mật khẩu</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-control" 
                               value="123456"
                               placeholder="Nhập mật khẩu" required>
                        <i class="bi bi-lock"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right"></i>
                    Đăng nhập
                </button>
            </form>
            
            <div class="demo-accounts">
                <h5><i class="bi bi-info-circle me-1"></i> Tài khoản Demo</h5>
                <div class="demo-item">
                    <span class="demo-role"><i class="bi bi-shield-fill text-danger"></i> Admin</span>
                    <span>admin@gmail.com</span>
                </div>
                <div class="demo-item">
                    <span class="demo-role"><i class="bi bi-star-fill text-warning"></i> Quản lý</span>
                    <span>manager@gmail.com</span>
                </div>
                <div class="demo-item">
                    <span class="demo-role"><i class="bi bi-person-fill text-info"></i> Nhân viên</span>
                    <span>user@gmail.com</span>
                </div>
                <div class="demo-item" style="border: none; padding-top: 8px;">
                    <span class="text-muted"><i class="bi bi-key me-1"></i> Mật khẩu:</span>
                    <span class="fw-bold">123456</span>
                </div>
            </div>
            
            <div class="login-footer">
                <p>© {{ date('Y') }} HR System - Phiên bản 1.0</p>
            </div>
        </div>
    </div>
</body>
</html>
