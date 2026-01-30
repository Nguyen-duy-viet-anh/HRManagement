<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\SystemNotice;
use Illuminate\Support\Facades\Notification;
use App\Jobs\SendSystemNotificationJob;


class NotificationController extends Controller
{
    // 1. Hiển thị form
    public function create() {
        return view('notifications.create');
    }

    // 2. Gửi thông báo
    public function send(Request $request) {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        // Gửi Job (Lưu ý: Nếu Job của bạn cần batchId thì thêm tham số thứ 3 vào đây)
        SendSystemNotificationJob::dispatch($request->title, $request->content);

        return back()->with('success', "Hệ thống đang xử lý gửi thông báo trong nền!");
    }

    // 3. Xử lý click đọc
    public function read($id) {
        $notification = auth()->user()->unreadNotifications->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            return redirect($notification->data['url'] ?? '/');
        }
        return back()->with('error', 'Thông báo không tồn tại.');
    }

    // 4. Đánh dấu tất cả đã đọc
    public function markAsRead() {
        if(auth()->check()) {
            auth()->user()->unreadNotifications->markAsRead();
        }
        return back();
    }

    // Notification history removed


// Notification history detail removed

}