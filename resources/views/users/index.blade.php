{{-- filepath: resources/views/users/index.blade.php --}}
@extends('layout')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="m-0 fw-bold text-primary">
                <i class="bi bi-people-fill me-2"></i>DANH SÁCH NHÂN SỰ
            </h5>
            <a href="{{ route('users.create') }}" class="btn btn-primary shadow-sm fw-bold">
                <i class="bi bi-plus-lg me-1"></i> Thêm nhân viên
            </a>
        </div>
        <div class="card-body bg-light border-bottom">
            <form id="user-filter-form" action="{{ route('users.index') }}" method="GET" autocomplete="off">
                <div class="row g-2 justify-content-end">
                    @if(Auth::user()->role == 0)
                    <div class="col-md-3">
                        <select name="company_id" class="form-select border-primary">
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
        <div class="card-body p-0">
            <div class="table-responsive">
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
                        {{-- dữ liệu --}}
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex justify-content-center mt-4">
            <nav>
                <ul class="pagination" id="pagination"></ul>
            </nav>
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
const tbody = document.getElementById('user-table-body');
const pagination = document.getElementById('pagination');
const form = document.getElementById('user-filter-form');

function fetchUsers(page = 1) {
    const params = new URLSearchParams(new FormData(form));
    params.set('page', page);

    fetch(`/api/users?${params}`)
        .then(r => r.json())
        .then(res => {
            renderTable(res);
            renderPagination(res);
        });
}

function renderTable(res) {
    tbody.innerHTML = res.data.map((u, i) => {
        const stt = (res.current_page - 1) * res.per_page + i + 1;
        const avatar = u.avatar
            ? (u.avatar.startsWith('http') ? u.avatar : `/storage/${u.avatar}`)
            : `https://ui-avatars.com/api/?name=${encodeURIComponent(u.name)}`;

        return `
        <tr>
            <td class="text-center fw-bold text-muted">${stt}</td>
            <td class="text-center">
                <img src="${avatar}" width="48" height="48" class="rounded-circle border">
            </td>
            <td>
                <div class="fw-bold">${u.name}</div>
                <div class="small text-muted">${u.email}</div>
            </td>
            <td>${u.company?.name ?? '<span class="text-muted">Tự do</span>'}</td>
            <td>
                <span class="badge ${
                    u.role == 0 ? 'bg-danger' :
                    u.role == 1 ? 'bg-warning text-dark' :
                    'bg-info'
                }">
                    ${u.role == 0 ? 'Super Admin' : u.role == 1 ? 'Quản lý' : 'Nhân viên'}
                </span>
            </td>
            <td>
                <span class="badge ${u.status ? 'bg-success' : 'bg-secondary'}">
                    ${u.status ? 'Hoạt động' : 'Đã nghỉ'}
                </span>
            </td>
            <td class="text-center">
                <div class="btn-group">
                    <a href="/users/${u.id}/attendance" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-calendar-week"></i>
                    </a>
                    <a href="/users/${u.id}/edit" class="btn btn-sm btn-outline-warning">
                        <i class="bi bi-pencil"></i>
                    </a>

                    <form method="POST" action="/users/${u.id}"
                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?')">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </td>

        </tr>`;
    }).join('');
}

function renderPagination({ current_page, last_page }) {
    let pages = [1, current_page - 1, current_page, current_page + 1, last_page]
        .filter(p => p > 0 && p <= last_page);

    pages = [...new Set(pages)].sort((a, b) => a - b);

    let html = '';
    let prev = 0;

    pages.forEach(p => {
        if (p - prev > 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `
        <li class="page-item ${p === current_page ? 'active' : ''}">
            <a href="#" class="page-link" data-page="${p}">${p}</a>
        </li>`;
        prev = p;
    });

    pagination.innerHTML = html;

    pagination.querySelectorAll('a').forEach(a => {
        a.onclick = e => {
            e.preventDefault();
            fetchUsers(a.dataset.page);
        };
    });
}

// EVENTS
form.onsubmit = e => {
    e.preventDefault();
    fetchUsers(1);
};

form.querySelectorAll('select').forEach(s => {
    s.onchange = () => fetchUsers(1);
});

document.addEventListener('DOMContentLoaded', () => fetchUsers());
</script>

@endsection