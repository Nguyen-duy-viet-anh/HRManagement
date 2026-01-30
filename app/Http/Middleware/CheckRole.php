<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  mixed ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 2. Lấy role hiện tại của user
        $userRole = (int) Auth::user()->role;

        
        $allowedRoles = [];
        foreach ($roles as $role) {
            $allowedRoles[] = (int) trim($role); 
        }

        // 5. Kiểm tra
        if (in_array($userRole, $allowedRoles)) {
            return $next($request);
        }

        // 6. Nếu không khớp -> Báo lỗi 403
        abort(403, 'Bạn không có quyền truy cập (Role của bạn: ' . $userRole . ')');
    }
}