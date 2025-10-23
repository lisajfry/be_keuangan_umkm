<?php

namespace App\Http\Controllers;

use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UmkmAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'nama_umkm' => 'required|string|max:255',
            'alamat' => 'nullable|string',
            'nib' => 'required|string|unique:umkms,nib',
            'pirt' => 'nullable|string|max:255',
            'no_hp' => 'nullable|string|max:15',
            'kategori_umkm' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $umkm = Umkm::create([
            'nama_umkm' => $request->nama_umkm,
            'alamat' => $request->alamat,
            'nib' => $request->nib,
            'pirt' => $request->pirt,
            'no_hp' => $request->no_hp,
            'kategori_umkm' => $request->kategori_umkm,
            'password' => Hash::make($request->password),
            'is_approved' => false, // default belum disetujui admin
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil, tunggu verifikasi admin.',
            'data' => $umkm
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'nib' => 'required|string',
            'password' => 'required|string',
        ]);

        $umkm = Umkm::where('nib', $request->nib)->first();

        if (!$umkm || !Hash::check($request->password, $umkm->password)) {
            return response()->json(['message' => 'NIB atau password salah.'], 401);
        }

        if (!$umkm->is_approved) {
            return response()->json(['message' => 'Akun Anda belum divalidasi oleh admin.'], 403);
        }

        // Misal kalau pakai sanctum
        $token = $umkm->createToken('umkm_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'data' => $umkm
        ]);
    }

    public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logout berhasil']);
}

}
