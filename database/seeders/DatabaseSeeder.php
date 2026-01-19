<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Tăng giới hạn bộ nhớ
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        echo "🚀 Đang xóa dữ liệu cũ và tạo mới...\n";

        // 1. TẠO SUPER ADMIN
        // Sử dụng updateOrCreate để tránh lỗi nếu chạy seeder nhiều lần
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('1'), // Mật khẩu là số 1
                'role' => 0,
                'company_id' => null,
                'base_salary' => 0,
                'status' => 1
            ]
        );

        // 2. TẠO 20 CÔNG TY
        $companies = Company::factory(20)->create();

        // Chuẩn bị ngày làm việc tháng này (Trừ Chủ Nhật)
        $startDate = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        $workingDays = [];
        for ($date = $startDate->copy(); $date->lte($today); $date->addDay()) {
            if ($date->dayOfWeek != Carbon::SUNDAY) {
                $workingDays[] = $date->format('Y-m-d');
            }
        }

        // 3. VÒNG LẶP TẠO NHÂN VIÊN
        foreach ($companies as $index => $company) {
            echo "   Processing Company " . ($index + 1) . "/20: " . $company->name . "\n";

            // 3.1. Tạo 1 Quản lý (Role 1)
            User::factory()->create([
                'name' => 'Manager ' . ($index + 1),
                'email' => 'manager' . ($index + 1) . '@gmail.com',
                'password' => Hash::make('123456'),
                'role' => 1,
                'company_id' => $company->id,
                'base_salary' => 30000000,
            ]);

            // 3.2. Tạo 25 Nhân viên (Role 2)
            $employees = User::factory(25)->create([
                'role' => 2,
                'company_id' => $company->id,
            ]);

            // 3.3. Chấm công cho 25 người này
            $attendanceData = [];
            foreach ($employees as $emp) {
                foreach ($workingDays as $day) {
                    $attendanceData[] = [
                        'user_id' => $emp->id,
                        'company_id' => $company->id,
                        'date' => $day,
                        'status' => rand(1, 100) <= 90 ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            // Insert dữ liệu chấm công hàng loạt
            foreach (array_chunk($attendanceData, 500) as $chunk) {
                DB::table('attendances')->insert($chunk);
            }
        }

        echo "✅ HOÀN TẤT! Đã tạo 20 công ty và đầy đủ Admin/Nhân viên.\n";
    }
}