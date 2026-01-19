@extends('layout')

@section('content')
<div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="m-0 text-primary">Danh sách Nhân sự</h5>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">+ Thêm nhân viên</a>
    </div>
    <div class="card-body">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Avatar</th>
                    <th>Họ tên</th>
                    <th>Công ty</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="text-center">
                        @if($user->avatar)
                            <img src="{{ asset('storage/' . $user->avatar) }}" width="50" class="rounded-circle">
                        @else
                            <span class="badge bg-secondary">No IMG</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $user->name }}</strong><br>
                        <small class="text-muted">{{ $user->email }}</small>
                    </td>
                    <td>
                        {{ $user->company ? $user->company->name : 'Tự do' }}
                    </td>
                    <td>
                        @if($user->role == 0) <span class="badge bg-danger">Super Admin</span>
                        @elseif($user->role == 1) <span class="badge bg-warning text-dark">Admin Công ty</span>
                        @else <span class="badge bg-info">Nhân viên</span>
                        @endif
                    </td>
                    <td>
                        @if($user->status == 1) <span class="text-success">Hoạt động</span>
                        @else <span class="text-muted">Đã nghỉ</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" 
                              onsubmit="return confirm('Xóa nhân viên này?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Xóa</button>
                            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-warning">
                            Sửa
                        </a>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection