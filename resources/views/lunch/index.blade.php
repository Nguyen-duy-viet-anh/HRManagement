@extends('layout') 

@section('content')
<div class="container mt-5">
    <h2>Mua Phiếu Ăn Trưa</h2>
    
    @if(session('success'))
        <div class=" alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class=" alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class=" alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    <div class="row">
        <div class="col-md-6">
            <div class="card p-4">
                <form action="{{ route('lunch.order') }}" method="POST" id="paymentForm">
                    @csrf
                    
                    {{-- Chọn mệnh giá --}}
                    <label class="form-label fw-bold">Chọn mệnh giá:</label>
                    <div class="d-flex gap-3 mb-4 flex-wrap">
                        @foreach($prices as $p)
                            <div>
                                <input type="radio" class="btn-check" name="price_level" id="p{{ $p->id }}" value="{{ $p->price }}" {{ $loop->first ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="p{{ $p->id }}">{{ number_format($p->price) }}đ</label>
                            </div>
                        @endforeach
                    </div>
                    
                    @if(Auth::user()->role == 0)
                        <div class="mb-3 text-end">
                            <a href="{{ route('lunch.config') }}" class="text-decoration-none small"><i class="bi bi-gear"></i> Quản lý mệnh giá</a>
                        </div>
                    @endif

                    {{-- Chọn phương thức thanh toán --}}
                    <label class="form-label fw-bold">Chọn cổng thanh toán:</label>
                    <input type="hidden" name="payment_gateway" id="payment_gateway" value="vnpay">
                    
                    <div class="row g-3 mb-4">
                        {{-- VNPay --}}
                        <div class="col-6">
                            <div class="card payment-option selected" data-gateway="vnpay" onclick="selectGateway('vnpay')">
                                <div class="card-body text-center py-3">
                                    <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-VNPAY-QR-1.png" 
                                         alt="VNPay" class="img-fluid mb-2" style="max-height: 40px;">
                                    <div class="small fw-semibold">VNPay</div>
                                    <div class="text-muted" style="font-size: 11px;">ATM / Visa / QR</div>
                                </div>
                            </div>
                        </div>
                        
                        {{-- OnePay --}}
                        <div class="col-6">
                            <div class="card payment-option" data-gateway="onepay" onclick="selectGateway('onepay')">
                                <div class="card-body text-center py-3">
                                    <img src="https://onepay-bucket.s3-sa-east-1.amazonaws.com/files/system_config/logos/OnePay%2BNovo.png" 
                                         alt="OnePay" class="img-fluid mb-2" style="max-height: 40px;">
                                    <div class="small fw-semibold">OnePay</div>
                                    <div class="text-muted" style="font-size: 11px;">Thẻ nội địa</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark w-100" id="submitBtn">
                        <i class="bi bi-credit-card me-2"></i>
                        <span id="btnText">Thanh toán qua VNPay</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <h4>Lịch sử mua</h4>
            <ul class="list-group">
                @forelse($myOrders as $order)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        
                        <div>
                            <span class="fw-bold">{{ number_format($order->price) }}đ</span>
                            <br>
                            <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                            @if($order->payment_method)
                                <br>
                                <span class="badge bg-secondary" style="font-size: 10px;">
                                    {{ strtoupper($order->payment_method) }}
                                </span>
                            @endif
                        </div>

                        <div class="text-end">
                            @if($order->status == 'paid') 
                                <span class="badge bg-success rounded-pill">Đã thanh toán</span>
                            
                            @elseif($order->status == 'pending')
                                <span class="badge bg-warning text-dark mb-1">Chờ thanh toán</span>
                                
                                <div class="mt-1">
                                    {{-- Dropdown chọn cổng thanh toán lại --}}
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            Thanh toán ngay
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('lunch.repay', ['id' => $order->id, 'gateway' => 'vnpay']) }}">
                                                    <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-VNPAY-QR-1.png" 
                                                         alt="VNPay" style="height: 20px;" class="me-2">
                                                    VNPay
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('lunch.repay', ['id' => $order->id, 'gateway' => 'onepay']) }}">
                                                        <img src="https://onepay-bucket.s3-sa-east-1.amazonaws.com/files/system_config/logos/OnePay%2BNovo.png" 

                                                         alt="OnePay" style="height: 20px;" class="me-2">
                                                    OnePay
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            
                            @else
                                <span class="badge bg-danger">Thất bại</span>
                            @endif
                        </div>

                    </li>
                @empty
                    <li class="list-group-item text-center text-muted">
                        <i class="bi bi-inbox me-2"></i>Chưa có đơn hàng nào
                    </li>
                @endforelse
            </ul>
            
            {{-- Phân trang --}}
            <div class="mt-3">
                {{ $myOrders->links() }}
            </div>
        </div>
    </div>
</div>

<style>
    .payment-option {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #dee2e6;
    }
    .payment-option:hover {
        border-color: #0d6efd;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .payment-option.selected {
        border-color: #0d6efd;
        background-color: #f0f7ff;
    }
    .payment-option.selected::after {
        content: '✓';
        position: absolute;
        top: 8px;
        right: 8px;
        background: #0d6efd;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }
    .payment-option {
        position: relative;
    }
</style>

<script>
    function selectGateway(gateway) {
        // Cập nhật hidden input
        document.getElementById('payment_gateway').value = gateway;
        
        // Cập nhật UI - bỏ selected cũ
        document.querySelectorAll('.payment-option').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Thêm selected cho option được chọn
        document.querySelector(`.payment-option[data-gateway="${gateway}"]`).classList.add('selected');
        
        // Cập nhật text button
        const btnText = document.getElementById('btnText');
        if (gateway === 'vnpay') {
            btnText.textContent = 'Thanh toán qua VNPay';
        } else if (gateway === 'onepay') {
            btnText.textContent = 'Thanh toán qua OnePay';
        }
    }
</script>
@endsection