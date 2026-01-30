<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotice extends Notification
{
    use Queueable;

    public string $title;
    public string $content;

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    public function via($notifiable)
    {
        return ['database'];
        // return ['database', 'mail'];

    }


    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('[HR SYSTEM] ' . $this->title) 
                    ->greeting('Xin chào ' . $notifiable->name . ',')
                    ->line('Bạn vừa nhận được một thông báo mới từ hệ thống:') 
                    ->line(' Nội dung : ' . $this->content)
                    ->action('Xem chi tiết', url('/')) 
                    ->line('Cảm ơn bạn đã xem thông báo này!');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'time' => now()
        ];
    }
}