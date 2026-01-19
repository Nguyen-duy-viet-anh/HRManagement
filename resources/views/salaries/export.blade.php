<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<table border="1">
    <thead>
        <tr>
            <td colspan="5" align="center" style="font-size: 20px; font-weight: bold;">
                BẢNG LƯƠNG THÁNG {{ date('m/Y', strtotime($month)) }}
            </td>
        </tr>
        <tr>
            <th style="background-color: #ffff00;">ID</th>
            <th style="background-color: #ffff00;">Họ và tên</th>
            <th style="background-color: #ffff00;">Email</th>
            <th style="background-color: #ffff00;">Ngày công</th>
            <th style="background-color: #ffff00;">Thực lĩnh (VNĐ)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr>
            <td>{{ $user->id }}</td>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td align="center">{{ $user->work_days }}</td>
            <td align="right">{{ $user->total_salary }}</td>
        </tr>
        @endforeach
        <tr>
            <td colspan="4" align="right"><strong>TỔNG CHI:</strong></td>
            <td align="right"><strong>{{ $users->sum('total_salary') }}</strong></td>
        </tr>
    </tbody>
</table>