<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
        return apiError('Sai tài khoản hoặc mật khẩu', 401);
    }

    $token = $user->createToken('API Token')->plainTextToken;

    return apiSuccess([
        'user' => $user,
        'token' => $token,
        'token_type' => 'Bearer'
    ], 'Đăng nhập thành công');
}

public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();
    return apiSuccess(null, 'Đăng xuất thành công');
}

public function me(Request $request)
{
    return apiSuccess($request->user(), 'Thông tin user');
}
}