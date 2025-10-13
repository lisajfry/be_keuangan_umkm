<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with('details.account')->orderBy('date', 'desc');

        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        return $query->get();
    }

    public function show($id)
    {
        return Transaction::with('details.account')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $this->validateTransaction($request);

        $details = $request->input('details');
        $totalDebit = $this->sumAmount($details, 'debit');
        $totalCredit = $this->sumAmount($details, 'credit');

        if (!$this->isBalanced($totalDebit, $totalCredit)) {
            return response()->json(['message' => 'Transaksi tidak balance: total debit != total credit'], 422);
        }

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'date' => $request->date,
                'description' => $request->description,
                'category' => $request->category ?? 'general',
                'cash_flow_category' => $request->cash_flow_category,
                'is_dividend' => $request->is_dividend ?? false,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => auth()->id() ?? null,
            ]);

            foreach ($details as $d) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $d['account_id'],
                    'debit' => $d['debit'] ?? 0,
                    'credit' => $d['credit'] ?? 0,
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Transaksi tersimpan',
                'transaction' => $transaction->load('details.account')
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan transaksi', 'error' => $e->getMessage()], 500);
        }
    }

    private function validateTransaction(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'cash_flow_category' => ['nullable', Rule::in(['operating', 'investing', 'financing'])],
            'is_dividend' => 'nullable|boolean',
            'details' => 'required|array|min:1',
            'details.*.account_id' => 'required|exists:accounts,id',
            'details.*.debit' => 'nullable|numeric|min:0',
            'details.*.credit' => 'nullable|numeric|min:0',
        ]);
    }

    private function sumAmount($details, $type)
    {
        return collect($details)->sum(function ($d) use ($type) {
            return floatval($d[$type] ?? 0);
        });
    }

    private function isBalanced($debit, $credit)
    {
        return round($debit, 2) === round($credit, 2);
    }

    public function destroy($id)
    {
        Transaction::findOrFail($id)->delete();
        return response()->json(['message' => 'Transaksi dihapus']);
    }
}
