<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'umkm_id' => $umkm->id,
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

    public function destroy($id)
    {
        $transaction = Transaction::where('umkm_id', auth()->user()->id)->findOrFail($id);
        $transaction->delete();

        return response()->json(['message' => '🗑 Transaksi dihapus']);
    }

   
    public function adminIndex(Request $request)
{
    if (!auth('sanctum')->check()) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $transactions = Transaction::with('umkm')->latest()->get();

    return response()->json([
        'status' => 'success',
        'data' => $transactions,
    ]);
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