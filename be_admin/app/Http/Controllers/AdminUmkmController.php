<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminUmkmController extends Controller
{
    public function proxyTransactions(Request $request)
    {
        $umkmBase = 'http://127.0.0.1:8001/api';

        try {
            // Tentukan URL berdasarkan parameter
            $targetUrl = $umkmBase . '/transactions';

            // Kirim request ke backend UMKM
            $response = Http::timeout(10)->get($targetUrl, [
                'umkm_id' => $request->input('umkm_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'page' => $request->input('page', 1),
                'per_page' => $request->input('per_page', 10),
            ]);

            // Kalau backend UMKM gagal merespons
            if ($response->failed()) {
                Log::error('Gagal ambil transaksi dari backend UMKM', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $targetUrl,
                ]);

                return response()->json([
                    'message' => 'Gagal mengambil data dari backend UMKM',
                    'status' => $response->status(),
                    'error' => json_decode($response->body(), true),
                ], 500);
            }

            return response()->json($response->json());

        } catch (\Exception $e) {
            // Kalau koneksi ke backend UMKM gagal total
            Log::error('Error koneksi ke backend UMKM', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Tidak bisa terhubung ke backend UMKM',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
