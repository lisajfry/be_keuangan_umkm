<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function index(Request $request)
{
    try {
        $query = Transaction::with('details.account')->orderBy('date', 'desc');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('umkm_id')) {
            $query->whereNotNull('created_by')
                  ->where('created_by', $request->umkm_id);
        } else {
            $user = auth()->user();
            if ($user->role !== 'admin') {
                $query->where('created_by', $user->id);
            }
        }

        $perPage = $request->get('per_page', 10);
        $transactions = $query->paginate($perPage);

        return response()->json($transactions, 200);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Gagal mengambil transaksi',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
