@extends('layout')

@section('content')
<div class="container mt-4">
    <h3 class="fw-bold text-primary mb-4 text-uppercase">Nhật Ký Mua Hàng</h3>

    <div class="card shadow-sm mb-4">
        <div class="card-body bg-light">
            <form action="{{ route('lunch.stats') }}" method="GET" class="row g-2 align-items-end">
                
                <div class="col-md-2">
                    <label class="form-label fw-bold">Ngày:</label>
                    <select name="day" class="form-select">
                        <option value="">-- Cả tháng --</option>
                        @for($d=1; $d<=31; $d++)
                            <option value="{{ $d }}" {{ $d == $day ? 'selected' : '' }}>
                                Ngày {{ $d }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Tháng:</label>
                    <select name="month" class="form-select">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                                Tháng {{ $m }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-bold">Năm:</label>
                    <select name="year" class="form-select">
                        @for($y=2024; $y<=2030; $y++)
                            <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">🔍 Xem</button>
                </div>
                
                <div class="col-md-4 text-end">
                    <small class="text-muted">
                        Tổng thu 
                        @if($day) ngày {{ $day }}/{{ $month }} @else tháng {{ $month }}/{{ $year }} @endif:
                    </small>
                    <h4 class="fw-bold text-success m-0">{{ number_format($totalRevenue) }} đ</h4>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between">
            <h6 class="m-0 align-middle pt-1">Danh sách giao dịch</h6>
            <span class="badge bg-light text-dark">Tìm thấy: {{ $orders->total() }} đơn</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">#</th>
                        <th>Thời gian</th>
                        <th>Nhân viên</th>
                        <th>Chi tiết</th>
                        <th class="text-end">Số tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $key => $order)
                    <tr>
                        <td class="text-center">{{ $key + 1 }}</td>
                        <td>
                            {{ $order->created_at->format('H:i:s') }} 
                            <span class="text-muted small">({{ $order->created_at->format('d/m') }})</span>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">{{ $order->user->name ?? 'User đã xóa' }}</div>
                            <small class="text-muted">{{ $order->user->email ?? '' }}</small>
                        </td>
                        <td>
                            <small>Mã GD: {{ $order->transaction_code }}</small>
                        </td>
                        <td class="text-end fw-bold text-success">
                            {{ number_format($order->price) }} đ
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">
                            Không có giao dịch nào trong thời gian này.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="p-3">
            {{ $orders->appends(['day' => $day, 'month' => $month, 'year' => $year])->links() }}
        </div>
    </div>
</div>
@endsection