@extends('layout')

@section('content')
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="m-0">CHẤM CÔNG HÀNG NGÀY</h5>
    </div>
    <div class="card-body">
        
        <form id="filter-form" action="{{ route('attendance.index') }}" method="GET" class="row mb-4 p-3 bg-light border rounded">
            <div class="col-md-4">
                <label class="fw-bold">Chọn Ngày:</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
            </div>
            <div class="col-md-4">
                <label class="fw-bold">Chọn Công ty:</label>
                <select name="company_id" class="form-select" onchange="document.getElementById('filter-form').submit();">
                    <option value="">-- Chọn công ty --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ $company_id == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Lọc danh sách</button>
            </div>
        </form>

        @if(isset($users) && count($users) > 0)
        <form action="{{ route('attendance.store') }}" method="POST">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <input type="hidden" name="company_id" value="{{ $company_id }}">
            <input type="hidden" name="page" value="{{ request('page', 1) }}">

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>ID</th>
                            <th>Nhân viên</th>
                            <th style="width: 150px;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="text-center">{{ $user->id }}</td>
                            <td>
                                <strong>{{ $user->name }}</strong><br>
                                <small class="text-muted">{{ $user->email }}</small>
                                
                                <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                            </td>
                            <td class="text-center">
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" style="transform: scale(1.5);"
                                           name="present[{{ $user->id }}]" 
                                           value="1"
                                           {{ $user->is_present ? 'checked' : '' }}>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-grid gap-2 col-6 mx-auto mt-3">
                <button type="submit" class="btn btn-success fw-bold py-2">
                    LƯU CHẤM CÔNG TRANG NÀY
                </button>
            </div>

            @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="d-flex justify-content-center mt-3">
                {{ $users->appends(['date' => $date, 'company_id' => $company_id])->links() }}
            </div>
            @endif
            
            <div class="alert alert-warning mt-3 text-center">
                Lưu ý: Hãy bấm nút <b>LƯU</b> trước khi chuyển sang trang khác!
            </div>

        </form>
        @else
            @if($company_id)
                <div class="alert alert-warning text-center">Công ty này chưa có nhân viên nào.</div>
            @else
                <div class="alert alert-info text-center">Vui lòng chọn <b>Công ty</b> để hiển thị danh sách nhân viên.</div>
            @endif
        @endif
    </div>
</div>
@endsection