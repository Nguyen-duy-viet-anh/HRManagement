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
    public $userIds; // Mảng user IDs, null = gửi cho tất cả
    
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct($title, $content, $userIds = null)
    {
        $this->title = $title;
        $this->content = $content;
        $this->userIds = $userIds;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Nếu có userIds cụ thể -> gửi cho users đó
        if (!empty($this->userIds)) {
            $users = User::whereIn('id', $this->userIds)->get();
            
            if ($users->isEmpty()) {
                echo " -> [INFO] Không tìm thấy user nào với IDs đã cho.\n";
                return;
            }

            Notification::send($users, new SystemNotice($this->title, $this->content));
            echo " -> [SUCCESS] Đã gửi notification cho " . $users->count() . " users.\n";
            return;
        }

        // Logic cũ: Gửi cho tất cả users (trừ admin)
        $totalUsers = User::where('role', '!=', 0)->count();

        if ($totalUsers == 0) {
            echo " -> [INFO] Không có nhân viên nào để gửi.\n";
            return;
        }

        $processed = 0;

        User::where('role', '!=', 0)
            ->orderBy('id')
            ->chunk(100, function ($users) use (&$processed, $totalUsers) {
                // Gửi thông báo
                Notification::send($users, new SystemNotice($this->title, $this->content));
            });
    }
}