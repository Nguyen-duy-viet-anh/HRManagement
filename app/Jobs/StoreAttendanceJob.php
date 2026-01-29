<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Có thể bỏ dòng này nếu dùng dispatchSync, nhưng cứ để cũng ko sao
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Attendance;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StoreAttendanceJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $date;
    protected $company_id;
    protected $user_ids;     
    protected $present_ids;  

    public function __construct($date, $company_id, $user_ids, $present_ids)
    {
        $this->date = $date;
        $this->company_id = $company_id;
        $this->user_ids = $user_ids;
        $this->present_ids = $present_ids;
    }

    public function handle()
    {
        // Kiểm tra dữ liệu
        if (empty($this->user_ids)) return;

        $today = Carbon::now()->format('Y-m-d');
        // Nếu chấm cho hôm nay thì lấy giờ hiện tại, quá khứ thì 8h sáng
        $currentTime = ($this->date == $today) ? Carbon::now() : '08:00:00';

        foreach ($this->user_ids as $user_id) {
            
            // Kiểm tra có mặt hay không
            $isChecked = false;
            if (is_array($this->present_ids) && isset($this->present_ids[$user_id])) {
                $isChecked = true;
            }

            $status = $isChecked ? 1 : 0;
            $checkInTime = ($status == 1) ? $currentTime : null;

            Attendance::updateOrCreate(
                [
                    'user_id' => $user_id, 
                    'date' => $this->date
                ], 
                [
                    'status' => $status,
                    'company_id' => $this->company_id,
                    
                ]
            );
        }

        // Xóa Cache
        Cache::forget("dashboard_stats_role_0_comp_"); 
        if ($this->company_id) {
            Cache::forget("dashboard_stats_role_1_comp_{$this->company_id}");
        }
    }
}