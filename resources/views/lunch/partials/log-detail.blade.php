@php
    $rawData = $log->raw_data ?? [];
    $amount = $log->vnp_amount ? number_format($log->vnp_amount / 100) . 'đ' : '-';
    $txnRef = $log->vnp_txn_ref ?? ($rawData['vnp_TxnRef'] ?? '-');
    $responseCode = $log->vnp_response_code ?? ($rawData['vnp_ResponseCode'] ?? '-');
@endphp

<table class="table table-borderless">
    <tr>
        <td style="width:150px" class="text-muted">ID:</td>
        <td><strong>{{ $log->id }}</strong></td>
    </tr>
    <tr>
        <td class="text-muted">Thời gian:</td>
        <td>{{ $log->created_at->format('H:i:s d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="text-muted">Order ID:</td>
        <td><strong>#{{ $log->order_id ?? '-' }}</strong></td>
    </tr>
    <tr>
        <td class="text-muted">Cổng:</td>
        <td>{{ strtoupper($log->gateway ?? 'vnpay') }}</td>
    </tr>
    <tr>
        <td class="text-muted">Sự kiện:</td>
        <td><code>{{ $log->event_type ?? '-' }}</code></td>
    </tr>
    <tr>
        <td class="text-muted">Trạng thái:</td>
        <td>
            @php
                $statusColors = [
                    'success' => 'success',
                    'failed' => 'danger',
                    'pending' => 'warning',
                    'suspicious' => 'danger',
                    'info' => 'info',
                ];
                $statusColor = $statusColors[$log->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $statusColor }}">{{ $log->status ?? '-' }}</span>
        </td>
    </tr>
    <tr>
        <td class="text-muted">Số tiền:</td>
        <td><strong>{{ $amount }}</strong></td>
    </tr>
    <tr>
        <td class="text-muted">Mã GD (TxnRef):</td>
        <td><code>{{ $txnRef }}</code></td>
    </tr>
    <tr>
        <td class="text-muted">Response Code:</td>
        <td><code>{{ $responseCode }}</code></td>
    </tr>
    <tr>
        <td class="text-muted">Session ID:</td>
        <td><code class="small">{{ $log->session_id ?? '-' }}</code></td>
    </tr>
</table>

<hr>

<h6>Mô tả</h6>
<p class="bg-light p-3 rounded">{{ $log->description ?? 'Không có mô tả' }}</p>

<h6>Dữ liệu chi tiết (raw_data)</h6>
<pre class="bg-dark text-light p-3 rounded" style="max-height:300px; overflow:auto; font-size:12px;">{{ json_encode($rawData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
