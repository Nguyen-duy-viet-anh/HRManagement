@extends('layout')

@section('content')
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h5 class="m-0 font-weight-bold text-primary">Danh sách công ty</h5>
        <a href="{{ route('companies.create') }}" class="btn btn-primary btn-sm">
            + Thêm mới
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Tên công ty</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Nhân viên</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($companies as $company)
                    <tr>
                        <td>{{ $company->id }}</td>
                        <td>
                            <strong>{{ $company->name }}</strong><br>
                            <small class="text-muted">{{ $company->address }}</small>
                        </td>
                        <td>{{ $company->email }}</td>
                        <td>{{ $company->phone }}</td>
                        <td class="text-center">
                            <span class="badge bg-info">{{ $company->users_count }} người</span>
                        </td>
                        <td>
                            <a href="{{ route('companies.edit', $company->id) }}" class="btn btn-sm btn-warning">Sửa</a>
                            
                            <form action="{{ route('companies.destroy', $company->id) }}" method="POST" class="d-inline" 
                                  onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                                @csrf
                                @method('DELETE') <button class="btn btn-sm btn-danger">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="d-flex justify-content-center">
                {{ $companies->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection