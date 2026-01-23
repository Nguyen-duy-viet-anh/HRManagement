<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Company; // Thêm dòng này

class AddMoreEmployeesSeeder extends Seeder
{
    public function run()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        DB::connection()->disableQueryLog();

        // 1. LẤY ID CỦA MỘT CÔNG TY ĐANG TỒN TẠI
        $firstCompany = Company::first();

        // Nếu chưa có công ty nào, hãy tạo nhanh 1 cái để có ID
        if (!$firstCompany) {
            $companyId = DB::table('companies')->insertGetId([
                'name' => 'Công ty Tổng',
                'email' => 'admin@company.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $companyId = $firstCompany->id;
        }

        $total = 500000;
        $chunkSize = 5000;
        $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

        $this->command->info("🚀 Đang nạp 500.000 nhân viên vào Công ty ID: $companyId");
        $bar = $this->command->getOutput()->createProgressBar($total);

        for ($i = 0; $i < $total; $i += $chunkSize) {
            $users = [];
            for ($j = 0; $j < $chunkSize; $j++) {
                $users[] = [
                    'id' => (string) Str::uuid(),
                    'name' => "NV " . ($i + $j + 1),
                    'email' => "emp" . ($i + $j + 1) . "_" . Str::random(3) . "@hr.com",
                    'password' => $password,
                    'role' => 2,
                    'company_id' => $companyId, // Dùng ID thật đã lấy ở trên
                    'base_salary' => rand(8000000, 15000000),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('users')->insert($users);
            $bar->advance($chunkSize);
        }

        $bar->finish();
        $this->command->info("\n Đã nạp thành công!");
    }
}