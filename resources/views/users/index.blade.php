@extends('layout')

@section('content')
<div class="w-100 p-0 m-0" style="background:transparent;">
        <div class="d-flex justify-content-between align-items-center mb-3" style="padding: 24px 0 0 0;">
            <h5 class="m-0 fw-bold text-primary">
                <i class="bi bi-people-fill me-2"></i>DANH SÁCH NHÂN SỰ
            </h5>
            <a href="{{ route('users.create') }}" class="btn btn-primary shadow-sm fw-bold">
                <i class="bi bi-plus-lg me-1"></i> Thêm nhân viên
            </a>
        </div>
        <div class="mb-3">
    <form action="{{ route('users.index') }}" method="GET">
        <div class="row g-2 justify-content-end">
            
            @if(Auth::user()->role == 0)
            <div class="col-md-3">
                <select name="company_id" class="form-select border-primary" onchange="this.form.submit()">
                    <option value="">-- Tất cả công ty --</option>
                    @foreach($companies as $cp)
                        <option value="{{ $cp->id }}" {{ request('company_id') == $cp->id ? 'selected' : '' }}>
                            {{ $cp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md-5">
                <div class="input-group">
                    <input type="text" name="search" class="form-control border-primary" 
                           placeholder="Tên hoặc email..." 
                           value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                    
                    @if(request('search') || request('company_id'))
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary" title="Xóa tất cả bộ lọc">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>
        <div class="p-0 m-0"> <div class="table-responsive w-100">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center py-3" style="width: 70px;">STT</th>
                            <th style="width: 80px;">Avatar</th>
                            <th>Thông tin nhân viên</th>
                            <th>Công ty</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th class="text-center" style="width: 150px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        {{--  --}}
                    </tbody>
                </table>

            </div>
        </div>

        <div class="d-flex justify-content-center mt-4">
    @if(isset($users) && $users->hasPages())
    {{-- ...existing code... --}}
    </div>
    @if($users->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $users->appends(request()->query())->links('vendor.pagination.custom-bootstrap') }}
        </div>
    @endif
    @endif
</div>
    </div>
</div>

<style>
    .bg-soft-success { background-color: #e8f5e9; }
    .bg-soft-secondary { background-color: #f8f9fa; }
    .table-hover tbody tr:hover { background-color: #fcfcfc; }
    .btn-group .btn { padding: 0.25rem 0.6rem; }
    .badge { font-weight: 500; font-size: 0.75rem; }
</style>

<script>
let currentPage = 1;

function loadUsers(page = 1) {
    fetch(`/api/users?page=${page}`)
        .then(res => res.json())
        .then(response => {
            let users = response.data;
            let html = users.map((user, idx) => `
                <tr>
                    <td class="text-center fw-bold text-muted">${(response.current_page - 1) * response.per_page + idx + 1}</td>
                    <td class="text-center">
                        <img src="${user.avatar ? '/storage/' + user.avatar : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(user.name)}" width="48" height="48" class="rounded-circle shadow-sm border" style="object-fit: cover;">
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${user.name}</div>
                        <div class="small text-muted">${user.email}</div>
                    </td>
                    <td>
                        ${user.company && user.company.name ? `<span class="text-dark">${user.company.name}</span>` : `<span class="text-muted italic">Tự do</span>`}
                    </td>
                    <td>
                        ${user.role == 0 
                            ? '<span class="badge rounded-pill bg-danger px-3">Super Admin</span>'
                            : user.role == 1 
                                ? '<span class="badge rounded-pill bg-warning text-dark px-3">Quản lý</span>'
                                : '<span class="badge rounded-pill bg-info text-white px-3">Nhân viên</span>'}
                    </td>
                    <td>
                        ${user.status == 1 
                            ? '<span class="badge bg-soft-success text-success border border-success px-2"><i class="bi bi-circle-fill me-1" style="font-size: 6px;"></i> Hoạt động</span>'
                            : '<span class="badge bg-soft-secondary text-secondary border border-secondary px-2">Đã nghỉ</span>'}
                    </td>
                    <td class="text-center">
                        <div class="btn-group shadow-sm">
                            <a href="/users/${user.id}/attendance" class="btn btn-sm btn-outline-primary" title="Xem lịch sử chấm công">
                                <i class="bi bi-calendar-week"></i>
                            </a>
                            <a href="/users/${user.id}/edit" class="btn btn-sm btn-outline-warning" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="deleteUser('${user.id}')" class="btn btn-sm btn-outline-danger" title="Xóa">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            document.getElementById('user-table-body').innerHTML = html;

            // Hiển thị phân trang
            renderPagination(response);
        });
}

function renderPagination(response) {
    let paginationHtml = '';
    if (response.last_page > 1) {
        paginationHtml += `<nav><ul class="pagination">`;
        if (response.current_page > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadUsers(${response.current_page - 1}); return false;">&laquo;</a></li>`;
        }
        for (let i = 1; i <= response.last_page; i++) {
            paginationHtml += `<li class="page-item ${i === response.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadUsers(${i}); return false;">${i}</a>
            </li>`;
        }
        if (response.current_page < response.last_page) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadUsers(${response.current_page + 1}); return false;">&raquo;</a></li>`;
        }
        paginationHtml += `</ul></nav>`;
    }
    document.getElementById('pagination').innerHTML = paginationHtml;
}

// Hàm xóa user qua API
function deleteUser(userId) {
    if (confirm('Bạn có chắc chắn muốn xóa nhân viên này?')) {
        fetch('/api/users/' + userId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => {
            if (res.ok) {
                alert('Xóa thành công!');
                loadUsers(currentPage);
            } else {
                alert('Xóa thất bại!');
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
});
</script>
@endsection