<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;
use Carbon\Carbon;

class AddMoreEmployeesSeeder extends Seeder
{
    public function run()
    {
        ini_set('memory_limit', '512M');
        
        echo "🚀 Đang thêm 25 nhân viên cho MỖI công ty...\n";

        // 1. Lấy tất cả công ty đang có
        $companies = Company::all();
        
        if ($companies->isEmpty()) {
            echo "⚠️ Không tìm thấy công ty nào. Hãy chạy DatabaseSeeder trước!\n";
            return;
        }

        // 2. Chuẩn bị ngày làm việc (Trừ Chủ Nhật)
        $startDate = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        $workingDays = [];
        
        for ($date = $startDate->copy(); $date->lte($today); $date->addDay()) {
            if ($date->dayOfWeek != Carbon::SUNDAY) {
                $workingDays[] = $date->format('Y-m-d');
            }
        }

        // 3. Vòng lặp thêm người
        foreach ($companies as $company) {
            echo "   + Đang bổ sung cho: " . $company->name . "...\n";

            // Tạo thêm 25 nhân viên mới
            $newEmployees = User::factory(25)->create([
                'role' => 2,
                'company_id' => $company->id,
            ]);

            // Chấm công cho 25 người mới này
            $attendanceData = [];
            foreach ($newEmployees as $emp) {
                foreach ($workingDays as $day) {
                    $status = rand(1, 100) <= 90 ? 1 : 0;
                    $attendanceData[] = [
                        'user_id' => $emp->id,
                        'date' => $day,
                        'status' => $status,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Insert nhanh
            foreach (array_chunk($attendanceData, 1000) as $chunk) {
                Attendance::insert($chunk);
            }
        }

        echo "✅ ĐÃ XONG! Mỗi công ty đã có thêm 25 nhân viên.\n";
    }
}