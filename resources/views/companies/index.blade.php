@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
    <h5 class="m-0 fw-bold text-primary">
        <i class="bi bi-building me-2"></i>DANH SÁCH CÔNG TY
    </h5>
    
    <div class="d-flex gap-2">
        <form action="{{ route('companies.index') }}" method="GET" class="d-flex">
            <div class="input-group">
                <input type="text" name="search" class="form-control" 
                       placeholder="Tìm tên, email..." value="{{ request('search') }}">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
                        @if(request('search'))
                            <a href="{{ route('companies.index') }}" class="btn btn-link text-decoration-none">Xóa</a>
                        @endif
                    </form>
                    
                    <a href="{{ route('companies.create') }}" class="btn btn-primary shadow-sm fw-bold">
                        <i class="bi bi-plus-lg me-1"></i> Thêm mới
                    </a>
                </div>
            </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 70px;">STT</th>
                            <th>Thông tin Công ty</th>
                            <th>Liên hệ</th>
                            <th class="text-center">Quy mô</th>
                            <th class="text-center" style="width: 150px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                        <tr>
                            <td class="text-center fw-bold text-muted">
                                {{ ($companies->currentPage() - 1) * $companies->perPage() + $loop->iteration }}
                            </td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3 bg-soft-primary text-primary rounded d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                        <i class="bi bi-patch-check"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $company->name }}</div>
                                        <div class="small text-muted text-truncate" style="max-width: 250px;">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $company->address }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="small"><i class="bi bi-envelope me-2 text-muted"></i>{{ $company->email }}</div>
                                <div class="small"><i class="bi bi-telephone me-2 text-muted"></i>{{ $company->phone }}</div>
                            </td>

                            <td class="text-center">
                                <span class="badge rounded-pill bg-soft-info text-info px-3 border border-info">
                                    <i class="bi bi-people me-1"></i> {{ $company->users_count ?? 0 }} nhân sự
                                </span>
                            </td>

                            <td class="text-center">
                                <div class="btn-group shadow-sm">
                                    <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-outline-warning" title="Sửa thông tin">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <form action="{{ route('companies.destroy', $company->id) }}" method="POST" 
                                          onsubmit="return confirm('Xóa công ty sẽ xóa toàn bộ nhân viên thuộc công ty đó. Bạn chắc chắn chứ?');" class="d-inline">
                                        @csrf 
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa công ty">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white py-3 border-top-0">
            <div class="d-flex justify-content-center">
                {{ $companies->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<style>
    .bg-soft-primary { background-color: #e7f1ff; }
    .bg-soft-info { background-color: #e0f7fa; }
    .text-info { color: #00acc1 !important; }
    .table-hover tbody tr:hover { background-color: #fcfcfc; transition: 0.2s; }
    .btn-group .btn { padding: 0.25rem 0.65rem; }
</style>
@endsection