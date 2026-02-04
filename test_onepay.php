<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Thông số sandbox OnePay
$SECURE_SECRET = '6D0870CDE5F24F34F3915FB0045120DB';

echo "=== TEST THUẬT TOÁN HASH ONEPAY ===\n\n";

/**
 * Tạo chữ ký OnePay theo chuẩn
 * @param array $params
 * @param string $secretKey (hex string)
 * @return string
 */
function createOnepayHash(array $params, string $secretKey): string {
    // 1. Chỉ lấy tham số vpc_ và user_
    $hashData = [];
    foreach ($params as $key => $value) {
        if ((str_starts_with($key, 'vpc_') || str_starts_with($key, 'user_')) 
            && $key !== 'vpc_SecureHash' 
            && $value !== '' && $value !== null) {
            $hashData[$key] = $value;
        }
    }
    
    // 2. Sắp xếp theo alphabet
    ksort($hashData);
    
    // 3. Tạo chuỗi hash (không URL encode giá trị)
    $rawData = urldecode(http_build_query($hashData));
    
    // 4. Hash với HMAC SHA256, key phải được chuyển từ hex sang binary
    $binaryKey = pack('H*', $secretKey);
    $hash = hash_hmac('sha256', $rawData, $binaryKey);
    
    // 5. Uppercase
    return strtoupper($hash);
}

// === TEST 1: Tạo URL thanh toán mới ===
echo "=== TẠO URL THANH TOÁN MỚI ===\n\n";

$txnRef = 'HR_' . date('Ymd_His') . '_' . rand(1000, 9999);
$params = [
    'vpc_Version' => '2',
    'vpc_Command' => 'pay',
    'vpc_Currency' => 'VND',
    'vpc_AccessCode' => '6BEB2546',
    'vpc_Merchant' => 'TESTMERCHANT',
    'vpc_Locale' => 'vn',
    'vpc_ReturnURL' => 'http://localhost:8000/onepay/return',
    'vpc_MerchTxnRef' => $txnRef,
    'vpc_OrderInfo' => 'Lunch_Order_Test',
    'vpc_Amount' => '2500000', // 25,000 VND * 100
    'vpc_TicketNo' => '127.0.0.1',
];

// Tạo hash
$hash = createOnepayHash($params, $SECURE_SECRET);
$params['vpc_SecureHash'] = $hash;

// Tạo URL (để OnePay URL encode khi gửi)
$url = "https://mtf.onepay.vn/paygate/vpcpay.op?" . http_build_query($params);

echo "Transaction Ref: $txnRef\n";
echo "Amount: 25,000 VND\n";
echo "Hash: $hash\n\n";
echo "URL:\n$url\n\n";

// === TEST 2: Với các tham số thẻ nội địa ===
echo "=== URL CHỈ THẺ NỘI ĐỊA (DOMESTIC) ===\n\n";

$txnRef2 = 'HR_' . date('Ymd_His') . '_' . rand(1000, 9999);
$params2 = [
    'vpc_Version' => '2',
    'vpc_Command' => 'pay',
    'vpc_Currency' => 'VND',
    'vpc_AccessCode' => '6BEB2546',
    'vpc_Merchant' => 'TESTMERCHANT',
    'vpc_Locale' => 'vn',
    'vpc_ReturnURL' => 'http://localhost:8000/onepay/return',
    'vpc_MerchTxnRef' => $txnRef2,
    'vpc_OrderInfo' => 'Lunch_Order_Domestic',
    'vpc_Amount' => '5000000', // 50,000 VND * 100
    'vpc_TicketNo' => '127.0.0.1',
    'vpc_CardList' => 'DOMESTIC', // Chỉ thẻ nội địa
];

$hash2 = createOnepayHash($params2, $SECURE_SECRET);
$params2['vpc_SecureHash'] = $hash2;
$url2 = "https://mtf.onepay.vn/paygate/vpcpay.op?" . http_build_query($params2);

echo "Transaction Ref: $txnRef2\n";
echo "Amount: 50,000 VND\n";
echo "Hash: $hash2\n\n";
echo "URL:\n$url2\n\n";

echo "=== HƯỚNG DẪN TEST ===\n";
echo "1. Copy URL phía trên và dán vào browser\n";
echo "2. Nếu OnePay hiển thị form nhập thẻ => Hash ĐÚNG!\n";
echo "3. Nếu trang trắng hoặc lỗi => Cần kiểm tra lại\n\n";
echo "Thẻ test nội địa:\n";
echo "- Số thẻ: 9704000000000012\n";
echo "- Ngày HH: 07/15\n";
echo "- Tên: NGUYEN VAN A\n";
echo "- OTP: 123456\n";



