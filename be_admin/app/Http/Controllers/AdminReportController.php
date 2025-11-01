<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;


class AdminReportController extends Controller
{
    /** =====================================================
     *  🔹 Filter tanggal / bulan
     * ==================================================== */
    private function dateRange($query, Request $request)
{
    // ✅ Filter berdasarkan bulan & tahun
    if ($request->filled('month') || $request->filled('year')) {
        $month = $request->input('month');
        $year = $request->input('year');

        if ($year && $month) {
            // Kalau dua-duanya dikirim
            $query->whereYear('transactions.date', $year)
                  ->whereMonth('transactions.date', $month);
        } elseif ($year) {
            // Kalau hanya tahun
            $query->whereYear('transactions.date', $year);
        } elseif ($month) {
            // Kalau hanya bulan (pakai tahun sekarang)
            $query->whereMonth('transactions.date', $month)
                  ->whereYear('transactions.date', now()->year);
        }
    }

    // ✅ Filter range tanggal (opsional)
    elseif ($request->has(['start_date', 'end_date'])) {
        $query->whereBetween('transactions.date', [$request->start_date, $request->end_date]);
    }
}


    /** =====================================================
     *  🔹 Query dasar — Bisa filter per UMKM
     * ==================================================== */
    private function baseQuery(Request $request)
    {
        $query = DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('accounts', 'transaction_details.account_id', '=', 'accounts.id');

        if ($request->filled('umkm_id')) {
            $query->where('transactions.umkm_id', $request->umkm_id);
        }

        return $query;
    }

    /** =====================================================
     *  🔹 Utility: sumByType untuk tipe akun
     * ==================================================== */
    private function sumByType($type, Request $request, $formula)
    {
        $query = $this->baseQuery($request)
            ->where('accounts.type', $type)
            ->selectRaw("SUM($formula) as total");

        $this->dateRange($query, $request);

        return (float) ($query->value('total') ?? 0);
    }

    /** =====================================================
     *  1️⃣ Laporan Laba Rugi per UMKM
     * ==================================================== */
    public function incomeStatement(Request $request)
    {
        $revenue = $this->sumByType('revenue', $request, 'credit - debit');
        $expense = $this->sumByType('expense', $request, 'debit - credit');
        $netIncome = $revenue - $expense;

        $details = $this->baseQuery($request)
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
     *  2️⃣ Neraca (Balance Sheet)
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
            $query = $this->baseQuery($request)
                ->where('accounts.type', $type)
                ->select('accounts.name', DB::raw("SUM($formula) as balance"))
                ->groupBy('accounts.name');

            $this->dateRange($query, $request);
            $data[$type] = $query->get();
        }

        $netIncome = $this->incomeStatement($request)->getData(true)['netIncome'] ?? 0;

        $data['equity']->push((object)[
            'name' => 'Laba Ditahan',
            'balance' => $netIncome,
        ]);

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
     *  3️⃣ Ringkasan Semua Laporan
     * ==================================================== */
    public function summary(Request $request)
    {
        return response()->json([
            'income_statement' => $this->incomeStatement($request)->getData(),
            'balance_sheet' => $this->balanceSheet($request)->getData(),
        ]);
    }

    /** =====================================================
     *  4️⃣ Download Excel (Admin pilih UMKM)
     * ==================================================== */
    public function downloadExcel(Request $request)
{
    $month = $request->input('month');
    $year  = $request->input('year', now()->year);

    $summaryData = $this->summaryAllUmkm($request)->getData(true);

    $data = [
        'month' => $month,
        'year' => $year,
        'summary_per_umkm' => $summaryData['summary_per_umkm'],
        'total_all' => $summaryData['total_all'],
    ];

    $filename = "laporan_semua_umkm_{$year}_{$month}.xlsx";

    return Excel::download(new ReportExport($data), $filename);
}



    public function summaryAllUmkm(Request $request)
{
    $umkmList = \App\Models\Umkm::all(['id', 'nama_umkm']);
    $results = [];

    $totalRevenue = 0;
    $totalExpense = 0;
    $totalNetIncome = 0;
    $totalAssets = 0;
    $totalLiabilities = 0;
    $totalEquity = 0;

    foreach ($umkmList as $umkm) {
        // Clone request tapi tambahkan umkm_id
        $req = clone $request;
        $req->merge(['umkm_id' => $umkm->id]);

        $income = $this->incomeStatement($req)->getData(true);
        $balance = $this->balanceSheet($req)->getData(true);

        $results[] = [
            'umkm_id' => $umkm->id,
            'nama_umkm' => $umkm->nama_umkm,
            'revenue' => $income['revenue'] ?? 0,
            'expense' => $income['expense'] ?? 0,
            'net_income' => $income['netIncome'] ?? 0,
            'total_assets' => $balance['total_assets'] ?? 0,
            'total_liabilities' => $balance['total_liabilities'] ?? 0,
            'total_equity' => $balance['total_equity'] ?? 0,
        ];

        $totalRevenue += $income['revenue'] ?? 0;
        $totalExpense += $income['expense'] ?? 0;
        $totalNetIncome += $income['netIncome'] ?? 0;
        $totalAssets += $balance['total_assets'] ?? 0;
        $totalLiabilities += $balance['total_liabilities'] ?? 0;
        $totalEquity += $balance['total_equity'] ?? 0;
    }

    return response()->json([
        'summary_per_umkm' => $results,
        'total_all' => [
            'revenue' => $totalRevenue,
            'expense' => $totalExpense,
            'net_income' => $totalNetIncome,
            'assets' => $totalAssets,
            'liabilities' => $totalLiabilities,
            'equity' => $totalEquity,
            'balanced' => round($totalAssets, 2) === round($totalLiabilities + $totalEquity, 2)
        ]
    ]);
}


public function dashboardSummary(Request $request)
{
    $umkmList = \App\Models\Umkm::all(['id', 'nama_umkm']);
    $dashboard = [];

    foreach ($umkmList as $umkm) {
        $req = clone $request;
        $req->merge(['umkm_id' => $umkm->id]);

        $income = $this->incomeStatement($req)->getData(true);
        $balance = $this->balanceSheet($req)->getData(true);

        $dashboard[] = [
            'umkm_id' => $umkm->id,
            'nama_umkm' => $umkm->nama_umkm,
            'net_income' => $income['netIncome'] ?? 0,
            'total_assets' => $balance['total_assets'] ?? 0,
            'total_liabilities' => $balance['total_liabilities'] ?? 0,
            'total_equity' => $balance['total_equity'] ?? 0,
            'balanced' => $balance['balanced'] ?? false,
        ];
    }

    return response()->json($dashboard);
}

}
