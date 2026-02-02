<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LunchOrder;
use App\Models\LunchPrice;
use Illuminate\Support\Facades\Auth;

class LunchController extends Controller
{
    // 1. Danh sách & Giao diện mua
    public function index()
    {
        $myOrders = LunchOrder::where('user_id', Auth::id())
                              ->orderBy('created_at', 'desc')
                              ->paginate(20);
        
        $prices = LunchPrice::orderBy('price', 'asc')->get();
        return view('lunch.index', compact('myOrders', 'prices'));
    }

    // 2. Tạo đơn hàng MỚI
    public function order(Request $request)
    {
        $request->validate([
            'price_level' => 'required|integer|exists:lunch_prices,price',
        ]);

        $order = LunchOrder::create([
            'user_id' => Auth::id(),
            'price' => $request->price_level,
            'description' => "Mua suat an " . number_format($request->price_level),
            'status' => 'pending'
        ]);

        return $this->createVnpayUrl($order, $request->payment_method);
    }

    public function repay($id)
    {
        $order = LunchOrder::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$order || $order->status != 'pending') {
            return redirect()->route('lunch.index')->with('error', 'Đơn hàng không hợp lệ.');
        }

        return $this->createVnpayUrl($order, 'VNBANK'); 
    }

    public function vnpayReturn(Request $request)
    {
        $vnp_HashSecret = env('VNP_HASH_SECRET'); 
        // $vnp_HashSecret = 'TKW7SK1HSP0VKDRM0YOUQVWCW0DTTFL8';

        $inputData = array();
        foreach ($request->all() as $key => $value) {
            // Chỉ lấy các tham số bắt đầu bằng "vnp_"
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        
        // 3. Xóa bỏ chữ ký (vnp_SecureHash) khỏi mảng dữ liệu để tính toán lại
        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash']);
        
        // 4. Sắp xếp
        ksort($inputData);
        
        // 5. Tạo chuỗi hash 
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        // 6. Mã hóa
        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
        
        // 7. KIỂM TRA & DEBUG
        if ($secureHash == $vnp_SecureHash) {
            $parts = explode('_', $request->vnp_TxnRef);
            $orderId = $parts[0];
            $order = LunchOrder::find($orderId);

            if ($order) {
                if ($request->vnp_ResponseCode == '00') {
                    $order->update([
                        'status' => 'paid', 
                        'transaction_code' => $request->vnp_TransactionNo
                    ]);
                    return redirect()->route('lunch.index')->with('success', 'Thanh toán thành công.');
                } else {
                    $order->update(['status' => 'failed']);
                    return redirect()->route('lunch.index')->with('error', 'Giao dịch bị lỗi hoặc bị hủy.');
                }
            } else {
                return redirect()->route('lunch.index')->with('error', 'Không tìm thấy đơn hàng.');
            }

        } else {
            echo "<h1>Lỗi: Sai chữ ký (Checksum Failed)</h1>";
            echo "<div style='font-family:monospace'>";
            echo "<b>Secret Key đang dùng:</b> " . $vnp_HashSecret . "<br><br>";
            echo "<b>1. Hash VNPay gửi về (vnp_SecureHash):</b> <br>" . $vnp_SecureHash . "<br><br>";
            echo "<b>2. Hash Web mình tính ra:</b> <br>" . $secureHash . "<br><br>";
            echo "<b>3. Chuỗi dữ liệu gốc (hashData):</b> <br>" . $hashData;
            echo "</div>";
            die();
        }
    }

    // 5. Thống kê
    public function stats(Request $request)
    {
        $day = $request->input('day');
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));
        // $prices = $request->input('prices', []);

        $query = \App\Models\LunchOrder::with('user')
            ->where('status', 'paid')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        if ($day) {
            $query->whereDay('created_at', $day);
        }
        // if (!empty($prices)) {
        //     $query->whereIn('price', $prices);
        // }


        $totalRevenue = $query->sum('price');
        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('lunch.stats', compact('orders', 'totalRevenue', 'day', 'month', 'year'));
    }
//
    private function createVnpayUrl($order, $paymentMethod = 'VNBANK')
    {
        $vnp_Url = env('VNP_URL');
        $vnp_HashSecret = env('VNP_HASH_SECRET');
        $vnp_TmnCode = env('VNP_TMN_CODE');

        // 1. Luôn tạo mã mới để tránh trùng lặp khi bấm lại
        $vnp_TxnRef = $order->id . "_" . date('YmdHis'); 

        $vnp_OrderInfo = "Thanh_toan_don_" . $order->id;

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => (int)$order->price * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => "127.0.0.1",
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => env('VNP_RETURN_URL'),
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        if ($paymentMethod == 'VNPAYQR') {
            $inputData['vnp_BankCode'] = 'VNPAYQR';
        } elseif ($paymentMethod == 'VNBANK') {
            $inputData['vnp_BankCode'] = 'VNBANK';
        }

        // 3. Sắp xếp
        ksort($inputData);
        $query = http_build_query($inputData);
        $vnp_Url = $vnp_Url . "?" . $query;
        
        // 4. Tạo Hash
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash = hash_hmac('sha512', $query, $vnp_HashSecret);
            $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
        }

        // 5. Redirect kèm Header chống Cache
        return redirect($vnp_Url)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
    }

    // --- QUẢN LÝ CẤU HÌNH GIÁ ---
    public function config()
    {
        if (Auth::user()->role != 0) abort(403, 'Chỉ Admin mới được cấu hình giá.');
        
        $prices = LunchPrice::orderBy('price', 'asc')->get();
        return view('lunch.config', compact('prices'));
    }

    public function storePrice(Request $request)
    {
        if (Auth::user()->role != 0) abort(403);
        
        $request->validate([
            'price' => 'required|integer|min:1000|unique:lunch_prices,price'
        ]);

        LunchPrice::create(['price' => $request->price]);

        return back()->with('success', 'Đã thêm mức giá mới.');
    }

    public function deletePrice($id)
    {
        if (Auth::user()->role != 0) abort(403);
        
        LunchPrice::destroy($id);
        return back()->with('success', 'Đã xóa mức giá.');
    }
}