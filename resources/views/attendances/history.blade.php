@extends('layout')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        
        {{-- 1. Card Tổng quan & Bộ lọc --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('attendance.history') }}" class="row align-items-end">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <h5 class="fw-bold text-primary mb-1">LỊCH SỬ CHẤM CÔNG</h5>
                        <p class="text-muted small mb-0">
                            Tổng ngày công tháng {{ $month }}/{{ $year }}: 
                            <span class="fw-bold text-success fs-5">{{ $totalWorkDays }}</span> ngày
                        </p>
                    </div>

                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Tháng</label>
                        <select name="month" class="form-select" onchange="this.form.submit()">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>Tháng {{ $m }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted">Năm</label>
                        <select name="year" class="form-select" onchange="this.form.submit()">
                            @for($y = 2026; $y <= 2030; $y++)
                                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </form>
            </div>
        </div>

        {{-- 2. Danh sách chi tiết --}}
        <div class="card shadow border-0">
            <div class="card-body p-0">
                @if($attendances->count() > 0)
                    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th class="ps-4 py-3">Ngày đã đi làm</th>
                <th>Giờ chấm công</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $item)
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                            <i class="bi bi-check-lg fw-bold"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</div>
                            <small class="text-muted">
                                Thứ {{ \Carbon\Carbon::parse($item->date)->dayOfWeek == 0 ? 'CN' : \Carbon\Carbon::parse($item->date)->dayOfWeek + 1 }}
                            </small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="fw-bold text-primary" style="font-family: monospace; font-size: 1.1em;">
                        {{ $item->created_at->setTimezone('Asia/Ho_Chi_Minh')->format('H:i:s') }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-calendar-x fs-1 text-muted opacity-25"></i>
                        <p class="text-muted mt-2">Chưa có dữ liệu chấm công trong tháng này.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection