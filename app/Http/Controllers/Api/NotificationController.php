<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    // GET /api/notifications/list
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Lấy thông báo, phân trang
        $notifications = $user->notifications()->paginate(20);

        return apiSuccess($notifications, 'Danh sách thông báo');
    }

    // POST /api/notifications/read
    public function markAsRead(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid'
        ]);

        $user = $request->user();
        $notification = $user->notifications()->where('id', $request->id)->first();

        if (!$notification) {
            return apiError('Không tìm thấy thông báo', 404);
        }

        $notification->markAsRead();

        return apiSuccess($notification, 'Đã đánh dấu đã đọc');
    }

    // POST /api/notifications/read-all
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return apiSuccess(null, 'Đã đánh dấu tất cả là đã đọc');
    }

    // POST /api/notifications/delete
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid'
        ]);

        $user = $request->user();
        $notification = $user->notifications()->where('id', $request->id)->first();

        if (!$notification) {
            return apiError('Không tìm thấy thông báo', 404);
        }

        $notification->delete();

        return apiSuccess(null, 'Xóa thông báo thành công');
    }
    
    // GET /api/notifications/unread-count
    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();
        return apiSuccess(['count' => $count], 'Số lượng thông báo chưa đọc');
    }
}
