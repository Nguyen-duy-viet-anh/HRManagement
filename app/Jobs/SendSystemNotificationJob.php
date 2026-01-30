<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Notifications\SystemNotice;
use Illuminate\Support\Facades\Notification;

class SendSystemNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $title;
    public $content;
    
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct($title, $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // --- TEST ---
        // $testEmails = ['vietanhnguyenduy1@gmail.com', 'nguyenduyvietanh.work@gmail.com' , 'bibeo9x1233@gmail.com'];
        
        // $users = User::whereIn('email', $testEmails)->get();
        
        // if ($users->count() > 0) {
        //     try {
        //         Notification::send($users, new SystemNotice($this->title, $this->content));
        //         echo " -> [TEST] Đã gửi xong cho: " . $users->count() . " tài khoản test.\n";
        //     } catch (\Exception $e) {
        //         echo " -> [LỖI GỬI MAIL]: " . $e->getMessage() . "\n";
        //         throw $e; // Ném lỗi ra để Laravel ghi nhận Fail
        //     }
        // } else {
        //     echo " -> [TEST] Không tìm thấy email test nào trong Database.\n";
        // }

        // --- ALL ---
        
        User::where('role', '!=', 0)->orderBy('id')->chunk(100, function ($users)  {
            Notification::send($users, new SystemNotice($this->title, $this->content));
        });
    }
}