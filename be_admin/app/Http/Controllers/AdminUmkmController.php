<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminUmkmController extends Controller
{
    private $umkmBaseUrl = 'http://127.0.0.1:8001/api';

    /**
     * Ambil semua data UMKM dari backend UMKM
     */
    public function listAll()
    {
        try {
            $response = Http::get("{$this->umkmBaseUrl}/umkms");

            if ($response->failed()) {
                Log::error('Gagal ambil data UMKM dari backend UMKM', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['message' => 'Gagal ambil data UMKM'], 500);
            }

            return response()->json($response->json(), 200);
        } catch (\Throwable $e) {
            Log::error('Kesalahan saat ambil UMKM', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Kesalahan internal server'], 500);
        }
    }

    /**
     * Ambil detail UMKM tertentu
     */
    public function show($id)
    {
        try {
            $response = Http::get("{$this->umkmBaseUrl}/umkms/{$id}");

            if ($response->failed()) {
                return response()->json(['message' => 'Data UMKM tidak ditemukan'], 404);
            }

            return response()->json($response->json(), 200);
        } catch (\Throwable $e) {
            Log::error('Kesalahan ambil detail UMKM', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Kesalahan internal server'], 500);
        }
    }

    /**
     * Approve UMKM (validasi dari admin)
     */
    public function approve($id)
    {
        try {
            $response = Http::post("{$this->umkmBaseUrl}/umkms/{$id}/approve");

            if ($response->failed()) {
                return response()->json(['message' => 'Gagal menyetujui UMKM'], 500);
            }

            return response()->json(['message' => 'UMKM berhasil disetujui'], 200);
        } catch (\Throwable $e) {
            Log::error('Kesalahan approve UMKM', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Kesalahan internal server'], 500);
        }
    }

    /**
     * Reject UMKM (ditolak oleh admin)
     */
    public function reject($id)
    {
        try {
            $response = Http::post("{$this->umkmBaseUrl}/umkms/{$id}/reject");

            if ($response->failed()) {
                return response()->json(['message' => 'Gagal menolak UMKM'], 500);
            }

            return response()->json(['message' => 'UMKM berhasil ditolak'], 200);
        } catch (\Throwable $e) {
            Log::error('Kesalahan reject UMKM', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Kesalahan internal server'], 500);
        }
    }
}
