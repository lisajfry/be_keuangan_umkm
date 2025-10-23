<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;

class ReportController extends Controller
{
    /** =====================================================
     *  🔹 Helper: Ambil ID UMKM yang sedang login
     * ==================================================== */
    private function currentUmkmId()
    {
        return Auth::guard('umkm')->id();
    }

    /** =====================================================
     *  🔹 Helper: Filter tanggal / bulan
     * ==================================================== */
    private function dateRange($query, Request $request)
    {
        if ($request->has('month') && !empty($request->month)) {
            if (preg_match('/^\d{4}-\d{2}$/', $request->month)) {
                [$year, $month] = explode('-', $request->month);
                $query->whereYear('transactions.date', $year)
                      ->whereMonth('transactions.date', $month);
            } else {
                $query->whereMonth('transactions.date', $request->month);
            }
        } elseif ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('transactions.date', [$request->start_date, $request->end_date]);
        }
    }

    /** =====================================================
     *  🔹 Query dasar untuk semua laporan
     * ==================================================== */
    private function baseQuery()
    {
        return DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('accounts', 'transaction_details.account_id', '=', 'accounts.id')
            ->where('transactions.umkm_id', $this->currentUmkmId()); // ✅ hanya data UMKM login
    }

    /** =====================================================
     *  🔹 Utility: Jumlahkan saldo berdasarkan tipe akun
     * ==================================================== */
    private function sumByType($type, Request $request, $formula)
    {
        $query = $this->baseQuery()
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

        $details = $this->baseQuery()
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

        $dividends = $this->baseQuery()
            ->where('transactions.is_dividend', true)
            ->sum('transaction_details.credit');

        $beginning = 0; // jika ada saldo awal, ambil dari tabel lain
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
            $query = $this->baseQuery()
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

        $totals['balanced'] = round($totals['total_assets'], 2)
            === round($totals['total_liabilities'] + $totals['total_equity'], 2);

        return response()->json(array_merge($data, $totals));
    }

    /** =====================================================
     *  4️⃣ Laporan Arus Kas
     * ==================================================== */
    public function cashFlow(Request $request)
{
    // Ambil semua akun kas global
    $cashAccountIds = Account::where('is_cash', true)
        ->pluck('id')
        ->toArray();

    if (empty($cashAccountIds)) {
        return response()->json(['message' => 'Tidak ada akun kas yang terdaftar.'], 422);
    }

    $query = $this->baseQuery()
        ->whereIn('transaction_details.account_id', $cashAccountIds)
        ->select(
            'transactions.cash_flow_category',
            DB::raw('SUM(transaction_details.debit - transaction_details.credit) as cash_change')
        );

    $this->dateRange($query, $request);

    $rows = $query->groupBy('transactions.cash_flow_category')->get();

    $operating = $rows->firstWhere('cash_flow_category', 'operating')->cash_change ?? 0;
    $investing = $rows->firstWhere('cash_flow_category', 'investing')->cash_change ?? 0;
    $financing = $rows->firstWhere('cash_flow_category', 'financing')->cash_change ?? 0;
    $net = $operating + $investing + $financing;

    return response()->json(compact('operating', 'investing', 'financing', 'net'));
}


    /** =====================================================
     *  5️⃣ Ringkasan Semua Laporan
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
     *  6️⃣ Download Excel (per UMKM login)
     * ==================================================== */
    public function downloadExcel(Request $request)
{
    $month = $request->input('month', now()->month);
    $year  = $request->input('year', now()->year);

    $request->merge(['month' => $month, 'year' => $year]);

    // Ambil UMKM login
    $umkm = auth('umkm')->user();

    $data = [
        'month'              => $month,
        'year'               => $year,
        'nama_umkm'          => $umkm->nama_umkm ?? 'UMKM Tidak Diketahui',
        'income_statement'   => $this->incomeStatement($request)->getData(true),
        'retained_earnings'  => $this->retainedEarnings($request)->getData(true),
        'balance_sheet'      => $this->balanceSheet($request)->getData(true),
        'cash_flow'          => $this->cashFlow($request)->getData(true),
    ];

    $filename = "laporan_keuangan_{$year}{$month}_umkm{$umkm->nama_umkm}.xlsx";

    return Excel::download(new ReportExport($data), $filename);
}

}