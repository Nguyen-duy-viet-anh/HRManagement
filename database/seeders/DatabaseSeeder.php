<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Cấu hình giới hạn tài nguyên
        ini_set('memory_limit', '-1'); // Không giới hạn RAM
        set_time_limit(0);             // Không giới hạn thời gian chạy
        DB::connection()->disableQueryLog(); // Tắt log để tiết kiệm bộ nhớ

        echo " Đang chuẩn bị dữ liệu 500.000 bản ghi...\n";

        // 2. TẠO SUPER ADMIN
        $hashedPassword = Hash::make('123456'); 
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Super Admin',
                'password' => $hashedPassword,
                'role' => 0,
                'base_salary' => 0,
                'status' => 1
            ]
        );

        // 3. TẠO 20 CÔNG TY
        $companies = Company::factory(20)->create();
        $companyIds = $companies->pluck('id')->toArray();

        // 4. CHUẨN BỊ THÔNG SỐ
        $totalUsers = 500000;
        $chunkSize = 2500;
        
        $today = Carbon::now()->format('Y-m-d');
        
        $bar = $this->command->getOutput()->createProgressBar($totalUsers);
        $bar->start();

        // 5. VÒNG LẶP CHÈN DỮ LIỆU SIÊU TỐC
        for ($i = 0; $i < $totalUsers; $i += $chunkSize) {
            $userData = [];
            $attendanceData = [];

            for ($j = 0; $j < $chunkSize; $j++) {
                $userId = (string) Str::uuid();
                $compIndex = array_rand($companyIds);
                $compId = $companyIds[$compIndex];

                // Dữ liệu User
                $userData[] = [
                    'id' => $userId,
                    'name' => "Nhân viên " . ($i + $j + 1),
                    'email' => "user" . ($i + $j + 1) . "@hr-system.com",
                    'password' => $hashedPassword,
                    'role' => 2,
                    'company_id' => $compId,
                    'base_salary' => rand(7000000, 20000000),
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Chấm công cho NGÀY HÔM NAY (Tránh tạo quá nhiều gây sập DB)
                $attendanceData[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'company_id' => $compId,
                    'date' => $today,
                    'status' => rand(1, 100) <= 90 ? 1 : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Chèn hàng loạt vào Database
            DB::table('users')->insert($userData);
            DB::table('attendances')->insert($attendanceData);

            $bar->advance($chunkSize);
        }

        $bar->finish();
        echo "\n HOÀN TẤT! Đã tạo 500.000 nhân sự và dữ liệu chấm công ngày hôm nay.\n";
    }
}