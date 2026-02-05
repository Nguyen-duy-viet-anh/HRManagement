@extends('layout')

@section('title', 'Log Thanh Toán')

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Log Thanh Toán</h4>
        <a href="{{ route('lunch.stats') }}" class="btn btn-outline-secondary">Quay lại</a>
    </div>

    {{-- Bộ lọc --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted">Cổng thanh toán</label>
                    <select name="gateway" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="vnpay" {{ ($gateway ?? '') == 'vnpay' ? 'selected' : '' }}>VNPay</option>
                        <option value="onepay" {{ ($gateway ?? '') == 'onepay' ? 'selected' : '' }}>OnePay</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Ngày</label>
                    <input type="number" name="day" class="form-control" value="{{ $day }}" placeholder="Tất cả">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Tháng</label>
                    <select name="month" class="form-select">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Năm</label>
                    <select name="year" class="form-select">
                        @for($y = date('Y'); $y >= 2024; $y--)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Lọc
                    </button>
                    <a href="{{ route('lunch.all-logs') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Nhóm log theo Order --}}
    @php
        $groupedLogs = $logs->getCollection()->groupBy('order_id');
    @endphp

    @forelse($groupedLogs as $orderId => $orderLogs)
        @php
            $firstLog = $orderLogs->first();
            $order = $firstLog->order;
            $user = $firstLog->user;
            $gateway = strtoupper($firstLog->gateway ?? 'vnpay');
            $isOnepay = ($firstLog->gateway ?? 'vnpay') === 'onepay';
            
            // Lấy số tiền - OnePay dùng amount, VNPay dùng vnp_amount
            if ($order) {
                $amount = $order->price;
            } elseif ($isOnepay) {
                $amount = $firstLog->amount ?? 0;
            } else {
                $amount = $firstLog->vnp_amount ? $firstLog->vnp_amount / 100 : 0;
            }
            
            // Xác định trạng thái đơn
            $hasSuccess = $orderLogs->where('status', 'success')->count() > 0;
            $hasFailed = $orderLogs->where('status', 'failed')->count() > 0;
            $orderStatus = $order->status ?? 'pending';
            
            if ($orderStatus === 'paid' || $hasSuccess) {
                $statusColor = 'success';
                $statusText = 'Thành công';
            } elseif ($hasFailed) {
                $statusColor = 'danger';
                $statusText = 'Thất bại';
            } else {
                $statusColor = 'warning';
                $statusText = 'Đang xử lý';
            }
        @endphp
        
        <div class="card mb-3">
            {{-- Header đơn hàng --}}
            <div class="card-header bg-white d-flex justify-content-between align-items-center" 
                 style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#order-{{ $orderId }}-{{ $loop->index }}">
                <div class="d-flex align-items-center">
                    <i class="bi bi-chevron-right me-3 toggle-icon"></i>
                    <div>
                        <strong>Đơn #{{ $orderId ?? 'N/A' }}</strong>
                        <span class="badge bg-{{ $statusColor }} ms-2">{{ $statusText }}</span>
                        <span class="badge bg-secondary ms-1">{{ $gateway }}</span>
                        <span class="text-muted ms-3">{{ $user->name ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="text-end">
                    <strong>{{ number_format($amount) }}đ</strong>
                    <small class="text-muted ms-2">{{ $orderLogs->count() }} log</small>
                </div>
            </div>
            
            {{-- Danh sách log của đơn --}}
            <div class="collapse" id="order-{{ $orderId }}-{{ $loop->index }}">
                <div class="list-group list-group-flush">
                    @foreach($orderLogs->sortBy('created_at') as $log)
                        @php
                            $isOnepay = ($log->gateway ?? 'vnpay') === 'onepay';
                            $rawData = $log->raw_data ?? [];
                            
                            // Số tiền - OnePay dùng amount, VNPay dùng vnp_amount
                            if ($isOnepay) {
                                $logAmount = $log->amount ? number_format($log->amount) . 'đ' : '';
                                $txnRef = $log->txn_ref ?? ($rawData['vpc_MerchTxnRef'] ?? '-');
                                $responseCode = $log->response_code ?? ($rawData['vpc_TxnResponseCode'] ?? '-');
                                $description = $log->message ?? 'Không có mô tả';
                                $eventType = $log->event ?? '-';
                            } else {
                                $logAmount = $log->vnp_amount ? number_format($log->vnp_amount / 100) . 'đ' : '';
                                $txnRef = $log->vnp_txn_ref ?? ($rawData['vnp_TxnRef'] ?? '-');
                                $responseCode = $log->vnp_response_code ?? ($rawData['vnp_ResponseCode'] ?? '-');
                                $description = $log->description ?? 'Không có mô tả';
                                $eventType = $log->event_type ?? '-';
                            }
                            
                            $logStatusColors = [
                                'success' => 'success',
                                'failed' => 'danger',
                                'pending' => 'warning',
                                'suspicious' => 'danger',
                                'info' => 'primary',
                            ];
                            $logStatusColor = $logStatusColors[$log->status] ?? 'secondary';
                        @endphp
                        
                        <div class="list-group-item p-0 border-start-0 border-end-0">
                            {{-- Dòng log --}}
                            <div class="d-flex align-items-center px-3 py-2 ps-5" style="cursor: pointer;" 
                                 data-bs-toggle="collapse" data-bs-target="#log-{{ $log->id }}-{{ $log->gateway ?? 'vnpay' }}">
                                <i class="bi bi-chevron-right me-2 text-muted toggle-icon small"></i>
                                <span class="badge bg-{{ $logStatusColor }} me-2" style="min-width: 70px;">{{ $log->status }}</span>
                                <div class="flex-grow-1 text-truncate">
                                    {{ $description }}
                                </div>
                                <small class="text-muted ms-2">{{ $log->created_at->format('H:i:s') }}</small>
                            </div>
                            
                            {{-- Chi tiết log --}}
                            <div class="collapse" id="log-{{ $log->id }}-{{ $log->gateway ?? 'vnpay' }}">
                                <div class="px-4 py-3 bg-light border-top ms-5">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr><td class="text-muted" style="width:120px">Sự kiện:</td><td><code>{{ $eventType }}</code></td></tr>
                                                <tr><td class="text-muted">Mã GD:</td><td><code>{{ $txnRef }}</code></td></tr>
                                                <tr><td class="text-muted">Response:</td><td><code>{{ $responseCode }}</code></td></tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tr><td class="text-muted" style="width:120px">Số tiền:</td><td><strong>{{ $logAmount ?: '-' }}</strong></td></tr>
                                                <tr><td class="text-muted">Thời gian:</td><td>{{ $log->created_at->format('H:i:s d/m/Y') }}</td></tr>
                                                <tr><td class="text-muted">Cổng:</td><td><code>{{ strtoupper($log->gateway ?? 'vnpay') }}</code></td></tr>
                                            </table>
                                        </div>
                                    </div>
                                    @if(!empty($rawData))
                                        <details class="mt-2">
                                            <summary class="text-muted small" style="cursor:pointer">Xem raw data</summary>
                                            <pre class="mt-2 p-2 bg-dark text-light rounded small" style="max-height:150px; overflow:auto; font-size:11px;">{{ json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Không có log nào
            </div>
        </div>
    @endforelse

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="d-flex justify-content-center mt-3">
            <nav>
                <ul class="pagination">
                    {{-- First Page --}}
                    <li class="page-item{{ $logs->onFirstPage() ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $logs->url(1) }}" tabindex="-1">&laquo;</a>
                    </li>
                    {{-- Previous Page --}}
                    <li class="page-item{{ $logs->onFirstPage() ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $logs->previousPageUrl() }}" tabindex="-1">&lsaquo;</a>
                    </li>
                    {{-- Page Number Window --}}
                    @php
                        $window = 2; // show 2 pages before and after
                        $start = max(1, $logs->currentPage() - $window);
                        $end = min($logs->lastPage(), $logs->currentPage() + $window);
                    @endphp
                    @for ($page = $start; $page <= $end; $page++)
                        <li class="page-item{{ $page == $logs->currentPage() ? ' active' : '' }}">
                            <a class="page-link" href="{{ $logs->url($page) }}">{{ $page }}</a>
                        </li>
                    @endfor
                    {{-- Next Page --}}
                    <li class="page-item{{ $logs->currentPage() == $logs->lastPage() ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $logs->nextPageUrl() }}">&rsaquo;</a>
                    </li>
                    {{-- Last Page --}}
                    <li class="page-item{{ $logs->currentPage() == $logs->lastPage() ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $logs->url($logs->lastPage()) }}">&raquo;</a>
                    </li>
                </ul>
            </nav>
        </div>
    @endif
</div>

<style>
.toggle-icon { transition: transform 0.2s; }
[aria-expanded="true"] .toggle-icon,
:not(.collapsed) > .toggle-icon { transform: rotate(90deg); }
</style>
@endsection
