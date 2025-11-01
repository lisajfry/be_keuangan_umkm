<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('details.account')
            ->where('umkm_id', auth()->user()->id)
            ->orderBy('date', 'desc');

        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return $query->get();
    }

    public function show($id)
    {
        return Transaction::with('details.account')
            ->where('umkm_id', auth()->user()->id)
            ->findOrFail($id);
    }

    public function store(Request $request)
    {
        $umkm = Auth::guard('umkm')->user();
        if (!$umkm) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
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

        // ===============================
        // OTOMATIS TENTUKAN CASH FLOW CATEGORY
        // ===============================
        $cashFlowCategory = $this->detectCashFlowCategory($validated['details'], $validated['description'] ?? '');

        // Simpan transaksi utama
       $transaction = Transaction::create([
    'umkm_id' => $umkm->id,
    'date' => $validated['date'],
    'description' => $validated['description'] ?? null,
    'category' => $validated['category'] ?? null,
    'cash_flow_category' => $cashFlowCategory,
]);


        // Simpan detail transaksi
        foreach ($validated['details'] as $detail) {
            $transaction->details()->create([
                'account_id' => $detail['account_id'],
                'debit' => $detail['debit'] ?? 0,
                'credit' => $detail['credit'] ?? 0,
            ]);
        }

        return response()->json(['message' => '✅ Transaksi berhasil disimpan']);
    }

    private function detectCashFlowCategory(array $details, string $description): ?string
    {
        $desc = strtolower($description);

        foreach ($details as $detail) {
            $acc = Account::find($detail['account_id']);
            if (!$acc) continue;

            $name = strtolower($acc->name);
            $type = strtolower($acc->type);

            // Jika akun kas, skip (karena kas hanya media keluar/masuk uang)
            if ($acc->is_cash) continue;

            // Berdasarkan tipe akun
            switch ($type) {
                case 'revenue':
                case 'expense':
                    return 'operating';
                case 'asset':
                    return 'investing';
                case 'equity':
                case 'liability':
                    return 'financing';
            }

            // Berdasarkan deskripsi (fallback)
            if (str_contains($desc, 'penjualan') || str_contains($desc, 'gaji')) {
                return 'operating';
            } elseif (str_contains($desc, 'peralatan') || str_contains($desc, 'aset')) {
                return 'investing';
            } elseif (str_contains($desc, 'modal') || str_contains($desc, 'pinjaman') || str_contains($desc, 'dividen')) {
                return 'financing';
            }
        }

        // Default kalau tidak terdeteksi
        return 'operating';
    }

    public function destroy($id)
    {
        $transaction = Transaction::where('umkm_id', auth()->user()->id)->findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => '🗑 Transaksi dihapus']);
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
