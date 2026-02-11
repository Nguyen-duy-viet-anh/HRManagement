<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;
use App\Notifications\SystemNotice;
use App\Jobs\SendSystemNotificationJob;

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

    // POST /api/notifications/create
    public function create(Request $request)
    {
        $data = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'uuid|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Đếm số users sẽ nhận notification (để trả về response)
        if (empty($data['user_ids'])) {
            $userCount = User::where('status', 1)->count();
            $sentToAll = true;
        } else {
            $userCount = User::whereIn('id', $data['user_ids'])->count();
            $sentToAll = false;
        }

        if ($userCount === 0) {
            return apiError('Không tìm thấy user nào', 404);
        }

        // Dispatch Job để gửi notification trong background
        SendSystemNotificationJob::dispatch(
            $data['title'],
            $data['message'],
            $data['user_ids'] ?? null
        );

        return apiSuccess([
            'sent_count' => $userCount,
            'sent_to_all' => $sentToAll,
            'status' => 'queued'
        ], 'Đã đưa thông báo vào hàng đợi, sẽ gửi trong giây lát', 201);
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
