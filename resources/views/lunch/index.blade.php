@extends('layout') 

@section('content')
<div class="container mt-5">
    <h2>Mua Phiếu Ăn Trưa</h2>
    
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-danger">{{ session('error') }}</div>
    @endif
    <div class="row">
        <div class="col-md-6">
            <div class="card p-4">
                <form action="{{ route('lunch.order') }}" method="POST">
                    @csrf
                    <label class="form-label fw-bold">Chọn mệnh giá:</label>
                    <div class="d-flex gap-3 mb-3 flex-wrap">
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

                    <button type="submit" class="btn btn-dark w-100">Thanh toán VNPay</button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <h4>Lịch sử mua</h4>
            <ul class="list-group">
    @foreach($myOrders as $order)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            
            <div>
                <span class="fw-bold">{{ number_format($order->price) }}đ</span>
                <br>
                <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
            </div>

            <div>
                @if($order->status == 'paid') 
                    <span class="badge bg-success rounded-pill">Đã thanh toán</span>
                
                @elseif($order->status == 'pending')
                    <span class="badge bg-warning text-dark mb-1">Chờ thanh toán</span>
                    
                    <div class="mt-1">
                        <a href="{{ route('lunch.repay', $order->id) }}" class="btn btn-sm btn-primary">
                            Thanh toán ngay
                        </a>
                    </div>
                
                @else
                    <span class="badge bg-danger">Thất bại</span>
                @endif
            </div>

        </li>
    @endforeach
</ul>
        </div>
    </div>
</div>
@endsection