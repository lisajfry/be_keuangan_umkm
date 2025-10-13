<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportController extends Controller
{
    /** =====================================================
     *  🔹 Helper: Filter tanggal atau bulan
     * ==================================================== */
    private function dateRange($query, Request $request)
    {
        if ($request->has('month') && !empty($request->month)) {
    // Jika formatnya 'YYYY-MM' → pisahkan tahun dan bulan
    if (preg_match('/^\d{4}-\d{2}$/', $request->month)) {
        [$year, $month] = explode('-', $request->month);
        $query->whereYear('transactions.date', $year)
              ->whereMonth('transactions.date', $month);
    } else {
        // Kalau cuma angka bulan
        $query->whereMonth('transactions.date', $request->month);
    }
}

    }

    /** =====================================================
     *  🔹 Utility: Jumlahkan saldo berdasarkan tipe akun
     * ==================================================== */
    private function sumByType($type, Request $request, $formula)
    {
        $query = DB::table('transaction_details')
            ->join('accounts', 'transaction_details.account_id', '=', 'accounts.id')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('accounts.type', $type)
            ->selectRaw("SUM($formula) as total");

        $this->dateRange($query, $request);
        return (float) ($query->value('total') ?? 0);
    }

    /** =====================================================
     *  1️⃣ Laporan Laba Rugi
     * ==================================================== */
    public function incomeStatement(Request $request)
    {
        $revenue = $this->sumByType('revenue', $request, 'credit - debit');
        $expense = $this->sumByType('expense', $request, 'debit - credit');
        $netIncome = $revenue - $expense;

        $details = DB::table('accounts')
            ->leftJoin('transaction_details', 'accounts.id', '=', 'transaction_details.account_id')
            ->leftJoin('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->select(
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(transaction_details.debit) as total_debit'),
                DB::raw('SUM(transaction_details.credit) as total_credit')
            )
            ->groupBy('accounts.name', 'accounts.type');

        $this->dateRange($details, $request);
        $details = $details->get();

        return response()->json(compact('revenue', 'expense', 'netIncome', 'details'));
    }

    /** =====================================================
     *  2️⃣ Laporan Perubahan Laba Ditahan
     * ==================================================== */
    public function retainedEarnings(Request $request)
    {
        $income = $this->incomeStatement($request)->getData()->netIncome ?? 0;

        $dividends = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.is_dividend', true)
            ->sum('transaction_details.credit');

        $beginning = 0; // bisa diambil dari tabel saldo awal kalau kamu punya
        $retained = $beginning + $income - $dividends;

        return response()->json([
            'beginning' => $beginning,
            'income' => $income,
            'dividends' => $dividends,
            'ending' => $retained
        ]);
    }

    /** =====================================================
     *  3️⃣ Neraca (Balance Sheet)
     * ==================================================== */
    public function balanceSheet(Request $request)
    {
        $types = [
            'asset' => 'debit - credit',
            'liability' => 'credit - debit',
            'equity' => 'credit - debit',
        ];

        $data = [];
        foreach ($types as $type => $formula) {
            $query = DB::table('accounts')
                ->leftJoin('transaction_details', 'accounts.id', '=', 'transaction_details.account_id')
                ->leftJoin('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
                ->where('accounts.type', $type)
                ->select('accounts.name', DB::raw("SUM($formula) as balance"))
                ->groupBy('accounts.name');

            $this->dateRange($query, $request);
            $data[$type] = $query->get();
        }

        $totals = [
            'total_assets' => $data['asset']->sum('balance'),
            'total_liabilities' => $data['liability']->sum('balance'),
            'total_equity' => $data['equity']->sum('balance'),
        ];

        $totals['balanced'] = round($totals['total_assets'], 2) === round($totals['total_liabilities'] + $totals['total_equity'], 2);

        return response()->json(array_merge($data, $totals));
    }

    /** =====================================================
     *  4️⃣ Laporan Arus Kas
     * ==================================================== */
    public function cashFlow(Request $request)
    {
        $cashAccountIds = Account::where('is_cash', true)->pluck('id')->toArray();
        if (empty($cashAccountIds)) {
            return response()->json(['message' => 'No cash account defined.'], 422);
        }

        $query = DB::table('transactions')
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->whereIn('transaction_details.account_id', $cashAccountIds)
            ->select('transactions.cash_flow_category', DB::raw('SUM(transaction_details.debit - transaction_details.credit) as cash_change'));

        $this->dateRange($query, $request);

        $rows = $query->groupBy('transactions.cash_flow_category')->get();

        $operating = $rows->firstWhere('cash_flow_category', 'operating')->cash_change ?? 0;
        $investing = $rows->firstWhere('cash_flow_category', 'investing')->cash_change ?? 0;
        $financing = $rows->firstWhere('cash_flow_category', 'financing')->cash_change ?? 0;
        $net = $operating + $investing + $financing;

        return response()->json(compact('operating', 'investing', 'financing', 'net'));
    }

    /** =====================================================
     *  5️⃣ Ringkasan Semua Laporan (Dashboard)
     * ==================================================== */
    public function summary(Request $request)
    {
        return response()->json([
            'income_statement'  => $this->incomeStatement($request)->getData(),
            'retained_earnings' => $this->retainedEarnings($request)->getData(),
            'balance_sheet'     => $this->balanceSheet($request)->getData(),
            'cash_flow'         => $this->cashFlow($request)->getData(),
        ]);
    }

    /** =====================================================
     *  6️⃣ Download Excel (semua laporan digabung)
     * ==================================================== */
   
    public function downloadExcel(Request $request)
{
    $month = $request->input('month', now()->month);
    $year  = $request->input('year', now()->year);

    // Tambahkan bulan ke dalam Request supaya fungsi lain tetap bisa akses
    $request->merge(['month' => $month, 'year' => $year]);

    // Ambil data laporan
    $incomeStatement  = $this->incomeStatement($request)->getData(true);
    $retainedEarnings = $this->retainedEarnings($request)->getData(true);
    $balanceSheet     = $this->balanceSheet($request)->getData(true);
    $cashFlow         = $this->cashFlow($request)->getData(true);

    $data = [
        'month'              => $month,
        'year'               => $year,
        'income_statement'   => $incomeStatement,
        'retained_earnings'  => $retainedEarnings,
        'balance_sheet'      => $balanceSheet,
        'cash_flow'          => $cashFlow,
    ];

    $filename = "laporan_keuangan_{$year}_{$month}.xlsx";

    return Excel::download(new ReportExport($data), $filename);
}



}
