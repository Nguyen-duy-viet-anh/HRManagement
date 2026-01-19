@extends('layout')

@section('content')
<div class="card shadow">
    <div class="card-header bg-info text-white">
        <h5 class="m-0">🤝 DANH SÁCH ĐỒNG NGHIỆP</h5>
    </div>
    <div class="card-body">
        
        <div class="alert alert-light border">
            Đây là danh sách nhân sự thuộc công ty: 
            <strong>{{ Auth::user()->company->name ?? 'N/A' }}</strong>
        </div>

        @if(isset($colleagues) && count($colleagues) > 0)
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark text-center">
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th>Họ và Tên</th>
                        <th>Email liên hệ</th>
                        <th>Vai trò</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($colleagues as $key => $colleague)
                    <tr>
                        <td class="text-center">{{ $key + 1 }}</td>
                        <td>
                            @if($colleague->id == Auth::id())
                                <span class="fw-bold text-primary">{{ $colleague->name }} (Tôi)</span>
                            @else
                                {{ $colleague->name }}
                            @endif
                        </td>
                        <td>
                            <a href="mailto:{{ $colleague->email }}" class="text-decoration-none">
                                {{ $colleague->email }}
                            </a>
                        </td>
                        <td class="text-center">
                            @if($colleague->role == 1)
                                <span class="badge bg-danger">Quản lý</span>
                            @else
                                <span class="badge bg-secondary">Nhân viên</span>
                            @endif
                        </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $colleagues->links() }}
        </div>

        @else
            <div class="alert alert-warning text-center">
                Bạn chưa có đồng nghiệp nào trong công ty này.
            </div>
        @endif
    </div>
</div>
@endsection