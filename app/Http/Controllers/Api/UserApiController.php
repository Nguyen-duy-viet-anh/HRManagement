<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    public function index() {
        // return response()->json(User::all()
        return response()->json(User::limit(100)->get());
    }
    public function show($id) {
        return response()->json(User::findOrFail($id));
    }
    public function store(Request $request) {
        $user = User::create($request->all());
        return response()->json($user, 201);
    }
    public function update(Request $request, $id) {
        $user = User::findOrFail($id);
        $user->update($request->all());
        return response()->json($user);
    }
    public function destroy($id) {
        User::destroy($id);
        return response()->json(null, 204);
    }
}