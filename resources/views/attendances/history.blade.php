@extends('layout')

@section('content')
<div class="container py-4">
    <div class="card shadow border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-primary mb-0">
                <i class="bi bi-clock-history me-2"></i>
                Lịch sử đi làm: {{ $targetUser->name }}
            </h5>
            
            {{-- Nút Quay lại thông minh --}}
            @if(Auth::user()->role == 0 || (Auth::user()->role == 1 && Auth::id() != $targetUser->id))
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Về danh sách nhân viên
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Về trang chủ
                </a>
            @endif
        </div>

        <div class="card-body p-0">
            @if($attendances->count() > 0)
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Ngày</th>
                            <th class="text-center">Giờ vào</th>
                            <th class="text-center">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
    @foreach($attendances as $item)
    <tr>
        {{-- CỘT 1: CHỈ LẤY NGÀY (d/m/Y) --}}
        <td class="ps-4">
            <div class="fw-bold text-dark">
                {{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}
            </div>
            {{-- Thêm thứ cho đẹp (Tùy chọn) --}}
            <div class="small text-muted">
                {{ \Carbon\Carbon::parse($item->date)->format('l') }}
            </div>
        </td>

        {{-- CỘT 2: CHỈ LẤY GIỜ (H:i) --}}
                    <td class="text-center">
                        @if($item->check_in_time)
                            <span class="fw-bold text-primary font-monospace fs-5">
                                {{ \Carbon\Carbon::parse($item->check_in_time)->format('H:i') }}
                            </span>
                        @else
                            <span class="text-muted small fst-italic">--:--</span>
                        @endif
                    </td>

                    {{-- CỘT 3: TRẠNG THÁI --}}
                    <td class="text-center">
                        @if($item->status == 1 || $item->is_present)
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                                Có mặt
                            </span>
                        @else
                            <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">
                                Vắng
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
                </table>
            {{-- ...existing code... --}}
        </div>
        @if($attendances->hasPages())
            <nav class="d-flex justify-content-center mt-3">
                <ul class="pagination">
                    <li class="page-item{{ $attendances->onFirstPage() ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $attendances->url(1) }}" tabindex="-1">&laquo;</a>
                    </li>
                    <li class="page-item{{ $attendances->onFirstPage() ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $attendances->previousPageUrl() }}" tabindex="-1">&lsaquo;</a>
                    </li>
                    @php
                        $window = 2;
                        $start = max(1, $attendances->currentPage() - $window);
                        $end = min($attendances->lastPage(), $attendances->currentPage() + $window);
                    @endphp
                    @for ($page = $start; $page <= $end; $page++)
                        <li class="page-item{{ $page == $attendances->currentPage() ? ' active' : '' }}">
                            <a class="page-link" href="{{ $attendances->url($page) }}">{{ $page }}</a>
                        </li>
                    @endfor
                    <li class="page-item{{ $attendances->currentPage() == $attendances->lastPage() ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $attendances->nextPageUrl() }}">&rsaquo;</a>
                    </li>
                    <li class="page-item{{ $attendances->currentPage() == $attendances->lastPage() ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $attendances->url($attendances->lastPage()) }}">&raquo;</a>
                    </li>
                </ul>
            </nav>
        @endif
            @else
                <div class="p-5 text-center text-muted">Chưa có dữ liệu chấm công.</div>
            @endif
        </div>
    </div>
</div>
@endsection