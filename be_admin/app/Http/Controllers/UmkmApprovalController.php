<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Umkm;

class UmkmApprovalController extends Controller
{
    // Admin memvalidasi UMKM
    public function approve($id)
    {
        // Ambil admin yang login pakai guard 'admin'
        $admin = auth('admin')->user();

        if (!$admin) {
            return response()->json(['message' => 'Unauthorized - Admin belum login'], 401);
        }

        // Cari UMKM berdasarkan ID
        $umkm = Umkm::find($id);

        if (!$umkm) {
            return response()->json(['message' => 'UMKM tidak ditemukan'], 404);
        }

        if ($umkm->is_approved) {
            return response()->json(['message' => 'UMKM sudah divalidasi sebelumnya'], 400);
        }

        // Update status dan catat admin_id
        $umkm->update([
            'is_approved' => true,
            'admin_id' => $admin->id,
        ]);

        return response()->json([
            'message' => 'UMKM berhasil divalidasi oleh admin ' . $admin->name,
            'umkm' => $umkm,
        ]);
    }
}
