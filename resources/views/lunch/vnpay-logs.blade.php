@extends('layout')

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        @if(isset($user))
            <div class="d-flex align-items-center">
                <a href="{{ route('lunch.all-logs') }}" class="btn btn-outline-secondary btn-sm me-3">
                    ← Quay lại
                </a>
                <img src="{{ $user->avatar_url }}" class="rounded-circle me-2" width="40" height="40" style="object-fit: cover;">
                <div>
                    <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
                    <small class="text-muted">{{ $user->email }}</small>
                </div>
            </div>
        @else
            <h4 class="fw-bold mb-0">Quản lý Log VNPay</h4>
            <a href="{{ route('lunch.stats') }}" class="btn btn-outline-secondary btn-sm">
                ← Quay lại Thống kê
            </a>
        @endif
    </div>

    <!-- Bộ lọc -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                @if(isset($user))
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                @endif
                <div class="col-auto">
                    <label class="form-label small mb-0">Ngày</label>
                    <input type="number" name="day" class="form-control form-control-sm" min="1" max="31" 
                           value="{{ $day }}" placeholder="Tất cả">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Tháng</label>
                    <select name="month" class="form-select form-select-sm">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Năm</label>
                    <select name="year" class="form-select form-select-sm">
                        @for($y = date('Y'); $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Trạng thái</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        <option value="success" {{ ($status ?? '') == 'success' ? 'selected' : '' }}>Thành công</option>
                        <option value="failed" {{ ($status ?? '') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                    </select>
                </div>
                @if(!isset($user))
                <div class="col-auto">
                    <label class="form-label small mb-0">Tìm kiếm</label>
                    <input type="text" name="search" class="form-control form-control-sm" 
                           value="{{ $search ?? '' }}" placeholder="TxnRef, Mã GD, Order ID, Tên...">
                </div>
                @endif
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary btn-sm">Lọc</button>
                    <a href="{{ isset($user) ? route('lunch.user-logs', $user->id) : route('lunch.all-logs') }}" 
                       class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-secondary alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Bảng log gộp theo Order -->
    @php
        // Gộp logs theo order_id
        $groupedLogs = $logs->getCollection()->groupBy('order_id');
    @endphp

    <div class="card">
        <div class="card-body p-0">
            @forelse($groupedLogs as $orderId => $orderLogs)
                @php
                    $firstLog = $orderLogs->sortBy('created_at')->first();
                    $order = $firstLog->order;
                    $lastLog = $orderLogs->sortByDesc('created_at')->first();
                    $finalStatus = $order ? $order->status : ($lastLog->status ?? 'unknown');
                    
                    // Lấy thông tin thanh toán từ log có đầy đủ thông tin nhất
                    $paymentLog = $orderLogs->whereNotNull('vnp_transaction_no')->first() ?? $orderLogs->whereNotNull('vnp_txn_ref')->first();
                @endphp
                <div class="border-bottom">
                    <!-- Header của Order (click để mở rộng) -->
                    <div class="d-flex align-items-center p-3 bg-light" 
                         style="cursor: pointer;" 
                         data-bs-toggle="collapse" 
                         data-bs-target="#order-{{ $orderId ?? 'null' }}">
                        <div class="me-3">
                            <span class="fw-bold">
                                @if($orderId)
                                    Đơn hàng #{{ $orderId }}
                                @else
                                    Không có Order
                                @endif
                            </span>
                            <span class="ms-2 small text-muted">
                                ({{ $orderLogs->count() }} bước)
                            </span>
                        </div>
                        
                        @if(!isset($user) && $firstLog->user)
                            <div class="me-3 small">
                                <a href="{{ route('lunch.user-logs', $firstLog->user_id) }}" class="text-decoration-none">
                                    {{ $firstLog->user->name }}
                                </a>
                            </div>
                        @endif
                        
                        <div class="me-3 small text-muted">
                            {{ $firstLog->created_at->format('d/m/Y H:i') }}
                        </div>
                        
                        @if($paymentLog && $paymentLog->vnp_amount)
                            <div class="me-3 fw-bold">
                                {{ number_format($paymentLog->real_amount) }}đ
                            </div>
                        @endif

                        @if($paymentLog && $paymentLog->vnp_bank_code)
                            <div class="me-3 small">
                                {{ $paymentLog->vnp_bank_code }}
                            </div>
                        @endif
                        
                        <div class="ms-auto">
                            @if($finalStatus == 'paid' || $finalStatus == 'success')
                                <span class="badge bg-secondary">Thành công</span>
                            @elseif($finalStatus == 'failed')
                                <span class="badge bg-dark">Thất bại</span>
                            @elseif($finalStatus == 'pending')
                                <span class="badge bg-light text-dark border">Đang xử lý</span>
                            @else
                                <span class="badge bg-light text-dark border">{{ $finalStatus }}</span>
                            @endif
                        </div>
                        
                        <div class="ms-2">
                            <i class="bi bi-chevron-down"></i>
                        </div>
                    </div>
                    
                    <!-- Chi tiết các bước (collapse) -->
                    <div class="collapse" id="order-{{ $orderId ?? 'null' }}">
                        <div class="p-3 bg-white">
                            <table class="table table-sm mb-0 small">
                                <thead>
                                    <tr class="text-muted">
                                        <th style="width: 100px;">Thời gian</th>
                                        <th style="width: 180px;">Sự kiện</th>
                                        <th>Chi tiết</th>
                                        <th style="width: 150px;">TxnRef / Mã GD</th>
                                        <th style="width: 80px;">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orderLogs->sortBy('created_at') as $log)
                                    <tr>
                                        <td class="text-muted">
                                            {{ $log->created_at->format('H:i:s') }}
                                        </td>
                                        <td>{{ $log->event_name }}</td>
                                        <td>
                                            <div>{{ Str::limit($log->description, 80) }}</div>
                                            @if($log->vnp_response_code && $log->vnp_response_code != '00')
                                                <small class="text-muted">
                                                    Mã phản hồi: {{ $log->vnp_response_code }} ({{ $log->response_description }})
                                                </small>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if($log->vnp_txn_ref)
                                                <code>{{ Str::limit($log->vnp_txn_ref, 18) }}</code>
                                            @endif
                                            @if($log->vnp_transaction_no)
                                                <br><span class="text-muted">GD: {{ $log->vnp_transaction_no }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->status == 'success')
                                                Thành công
                                            @elseif($log->status == 'failed')
                                                Thất bại
                                            @elseif($log->status == 'pending')
                                                Đang xử lý
                                            @else
                                                {{ $log->status ?? '-' }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    Không có log nào.
                </div>
            @endforelse
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white">
            {{ $logs->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
