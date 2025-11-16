<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    

    // Login admin
    public function login(Request $request)
{
    $request->validate([
        'username' => 'required|string', // ✅
        'password' => 'required|string',
    ]);

    $admin = Admin::where('username', $request->username)->first(); // ✅

    if (! $admin || ! Hash::check($request->password, $admin->password)) {
        return response()->json(['message' => 'Username atau password salah'], 401);
    }

    $token = $admin->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login berhasil',
        'admin'   => $admin,
        'token'   => $token,
    ]);
}


    // Logout admin
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
