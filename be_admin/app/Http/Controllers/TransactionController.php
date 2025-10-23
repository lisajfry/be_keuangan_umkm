<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    // ✅ Tampilkan semua transaksi dari semua UMKM
   // ✅ app/Http/Controllers/TransactionController.php
public function index(Request $request)
{
    $query = Transaction::with(['details.account', 'umkm'])
        ->orderBy('date', 'desc');

    // 🔹 Filter berdasarkan UMKM
    if ($request->has('umkm_id') && !empty($request->umkm_id)) {
        $query->where('umkm_id', $request->umkm_id);
    }

    // 🔹 Filter berdasarkan bulan & tahun
    if ($request->has('month') && $request->has('year')) {
        $query->whereMonth('date', $request->month)
              ->whereYear('date', $request->year);
    }

    return $query->get();
}


    // ✅ Detail transaksi berdasarkan ID (bisa semua UMKM)
    public function show($id)
    {
        return Transaction::with(['details.account', 'umkm'])->findOrFail($id);
    }

    // ✅ Simpan transaksi (masukkan umkm_id dari request)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'umkm_id' => 'required|integer|exists:umkms,id',
            'date' => 'required|date',
            'description' => 'required|string',
            'category' => 'required|string',
            'cash_flow_category' => ['required', Rule::in(['operating', 'investing', 'financing'])],
            'is_dividend' => 'nullable|boolean',
            'details' => 'required|array|min:1',
            'details.*.account_id' => 'required|integer|exists:accounts,id',
            'details.*.debit' => 'nullable|numeric|min:0',
            'details.*.credit' => 'nullable|numeric|min:0',
        ]);

        $totalDebit = $this->sumAmount($validated['details'], 'debit');
        $totalCredit = $this->sumAmount($validated['details'], 'credit');

        if (!$this->isBalanced($totalDebit, $totalCredit)) {
            return response()->json(['message' => '❌ Transaksi tidak seimbang antara debit dan kredit.'], 422);
        }

        $transaction = Transaction::create([
            'umkm_id' => $validated['umkm_id'],
            'date' => $validated['date'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'cash_flow_category' => $validated['cash_flow_category'],
            'is_dividend' => $request->boolean('is_dividend', false),
        ]);

        foreach ($validated['details'] as $detail) {
            $transaction->details()->create([
                'account_id' => $detail['account_id'],
                'debit' => $detail['debit'] ?? 0,
                'credit' => $detail['credit'] ?? 0,
            ]);
        }

        return response()->json(['message' => '✅ Transaksi berhasil disimpan']);
    }

    // ✅ Hapus transaksi berdasarkan ID
    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => '🗑️ Transaksi dihapus']);
    }

    // ===== Helper =====
    private function sumAmount($details, $type)
    {
        return collect($details)->sum(fn($d) => floatval($d[$type] ?? 0));
    }

    private function isBalanced($debit, $credit)
    {
        return round($debit, 2) === round($credit, 2);
    }
}
