@extends('layout')

@section('content')
<div class="card shadow">
    <div class="card-header bg-success text-white">
        <h5 class="m-0">BẢNG TÍNH LƯƠNG</h5>
    </div>
    <div class="card-body">
        
        <form id="form-salary" action="{{ route('salary.index') }}" method="GET" class="row mb-4 p-3 bg-light border rounded">
            
            <div class="col-md-4">
                <label class="fw-bold">Chọn Tháng:</label>
                <input type="month" name="month" class="form-control" value="{{ $month ?? date('Y-m') }}">
            </div>

            <div class="col-md-4">
                <label class="fw-bold">Chọn Công ty:</label>
                <select name="company_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Chọn công ty --</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (isset($company_id) && $company_id == $company->id) ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="button" 
                        onclick="document.getElementById('form-salary').submit();" 
                        class="btn btn-success flex-grow-1">
                    Xem bảng lương
                </button>
                
                @if(isset($users) && count($users) > 0)
                    <a href="{{ route('salary.export', ['month' => $month ?? date('Y-m'), 'company_id' => $company_id]) }}" 
                       class="btn btn-warning fw-bold text-dark">
                       Xuất Excel
                    </a>
                @endif
            </div>
        </form>

        @if(isset($users) && count($users) > 0)
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark text-center">
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Lương CB</th>
                        <th>Ngày công</th>
                        <th>Thực lĩnh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td class="text-center">{{ $user->id }}</td>
                        <td>
                            <strong>{{ $user->name }}</strong><br>
                            <small>{{ $user->email }}</small>
                        </td>
                        <td class="text-end">{{ number_format($user->base_salary) }}</td>
                        <td class="text-center fw-bold">{{ $user->work_days }}</td>
                        <td class="text-end fw-bold text-success">{{ number_format($user->total_salary) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            @if(request('company_id'))
                <div class="alert alert-warning text-center">Công ty này chưa chấm công tháng này!</div>
            @else
                <div class="alert alert-info text-center">Hãy chọn <b>Tháng</b> và <b>Công ty</b> để xem lương.</div>
            @endif
        @endif
    </div>
</div>
@endsection