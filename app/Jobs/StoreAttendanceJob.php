<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Attendance;
use Illuminate\Support\Facades\Cache;

class StoreAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $company_id;
    protected $user_ids;
    protected $present_ids;

    /**
     * Nhận dữ liệu đầu vào từ Controller
     */
    public function __construct($date, $company_id, $user_ids, $present_ids)
    {
        $this->date = $date;
        $this->company_id = $company_id;
        $this->user_ids = $user_ids;
        $this->present_ids = $present_ids;
    }

    /**
     * Thực thi logic chấm công (Nặng) ở đây
     */
    public function handle(): void
    {
        // 1. Chạy vòng lặp lưu DB
        foreach ($this->user_ids as $user_id) {
            // Kiểm tra xem user có được tick chọn không
            $status = isset($this->present_ids[$user_id]) ? 1 : 0;

            Attendance::updateOrCreate(
                [
                    'user_id' => $user_id, 
                    'date' => $this->date
                ], 
                [
                    'status' => $status,
                    'company_id' => $this->company_id 
                ]
            );
        }

        // 2. Xóa Cache sau khi xử lý xong
        $this->clearDashboardCache($this->company_id);
    }

    private function clearDashboardCache($companyId)
    {
        Cache::forget("dashboard_stats_role_0_comp_"); 
        if ($companyId) {
            Cache::forget("dashboard_stats_role_1_comp_{$companyId}");
        }
    }
}