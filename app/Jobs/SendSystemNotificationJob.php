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