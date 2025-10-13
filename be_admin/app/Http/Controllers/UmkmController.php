<?php

namespace App\Http\Controllers;

use App\Models\Umkm;
use Illuminate\Http\Request;

class UmkmController extends Controller
{
    // List semua UMKM beserta admin yang menambahkan
    public function index()
    {
        return response()->json(
            Umkm::with('admin')->get()
        );
    }

    // List UMKM yang belum divalidasi admin
    public function pending()
    {
        $pendingUmkm = Umkm::with('admin')
            ->where('is_approved', false)
            ->get();

        return response()->json([
            'message' => 'Daftar UMKM yang belum divalidasi',
            'data' => $pendingUmkm
        ]);
    }

    // Tambah UMKM baru
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_umkm' => 'required|string',
            'alamat' => 'nullable|string',
            'nib' => 'required|string|unique:umkms',
            'pirt' => 'nullable|string',
            'no_hp' => 'nullable|string',
            'kategori_umkm' => 'nullable|string',
        ]);

        // sementara admin_id kita pakai fix 1 dulu
        $data['admin_id'] = 1;
        $data['password'] = bcrypt('password123'); // default password
        $data['is_approved'] = false; // default belum disetujui admin

        $umkm = Umkm::create($data);

        return response()->json([
            'message' => 'UMKM berhasil didaftarkan, menunggu validasi admin.',
            'umkm' => $umkm
        ], 201);
    }

    // Detail UMKM
    public function show($id)
    {
        return response()->json(
            Umkm::with('admin')->findOrFail($id)
        );
    }

    // Update UMKM
    public function update(Request $request, $id)
    {
        $umkm = Umkm::findOrFail($id);
        $umkm->update($request->all());

        return response()->json([
            'message' => 'Data UMKM berhasil diperbarui',
            'data' => $umkm
        ]);
    }

    // Hapus UMKM
    public function destroy($id)
    {
        $umkm = Umkm::findOrFail($id);
        $umkm->delete();

        return response()->json(['message' => 'UMKM berhasil dihapus'], 204);
    }

    // Admin menyetujui (validasi) pendaftaran UMKM
    public function approve($id)
    {
        $umkm = Umkm::findOrFail($id);

        if ($umkm->is_approved) {
            return response()->json(['message' => 'UMKM sudah divalidasi sebelumnya.'], 400);
        }

        $umkm->is_approved = true;
        $umkm->save();

        return response()->json([
            'message' => 'UMKM telah berhasil divalidasi oleh admin.',
            'data' => $umkm
        ]);
    }

    // Admin menolak pendaftaran UMKM
    public function reject($id)
    {
        $umkm = Umkm::findOrFail($id);

        if ($umkm->is_approved) {
            return response()->json(['message' => 'UMKM sudah disetujui, tidak bisa ditolak.'], 400);
        }


        return response()->json(['message' => 'Pendaftaran UMKM telah ditolak .']);
    }
}
