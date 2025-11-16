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
     *  🔹 Helper: Ambil ID UMKM login
     * ==================================================== */
    private function currentUmkmId()
    {
        return Auth::guard('umkm')->id();
    }

    /** =====================================================
     *  🔹 Filter tanggal fleksibel (periodic / cumulative)
     * ==================================================== */
    private function applyDateRange($query, Request $request, $type = 'periodic')
    {
        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->month);
            $startOfMonth = date('Y-m-01', strtotime("$year-$month-01"));
            $endOfMonth   = date('Y-m-t', strtotime($startOfMonth));

            if ($type === 'cumulative') {
                // Untuk neraca (saldo sampai akhir bulan)
                $query->where('transactions.date', '<=', $endOfMonth);
            } else {
                // Untuk laba rugi (mutasi hanya periode itu)
                $query->whereBetween('transactions.date', [$startOfMonth, $endOfMonth]);
            }

        } elseif ($request->filled(['start_date', 'end_date'])) {
            $query->whereBetween('transactions.date', [$request->start_date, $request->end_date]);
        }
    }

    /** =====================================================
     *  🔹 Query dasar transaksi UMKM login
     * ==================================================== */
    private function baseQuery()
    {
        return DB::table('transaction_details')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->join('accounts', 'transaction_details.account_id', '=', 'accounts.id')
            ->where('transactions.umkm_id', $this->currentUmkmId());
    }

    /** =====================================================
     *  1️⃣ Laporan Laba Rugi (periodik)
     * ==================================================== */
    public function incomeStatement(Request $request)
{
    $details = $this->baseQuery()
        ->whereIn('accounts.type', ['revenue', 'expense'])
        ->select(
            'accounts.name',
            'accounts.type',
            DB::raw('SUM(transaction_details.debit) as total_debit'),
            DB::raw('SUM(transaction_details.credit) as total_credit')
        )
        ->groupBy('accounts.name', 'accounts.type');

    $this->applyDateRange($details, $request, 'periodic');
    $details = $details->get();

    $revenue = $details->where('type', 'revenue')->sum(fn($a) => $a->total_credit - $a->total_debit);
    $expense = $details->where('type', 'expense')->sum(fn($a) => $a->total_debit - $a->total_credit);
    $netIncome = $revenue - $expense;

    return response()->json(compact('revenue', 'expense', 'netIncome', 'details'));
}


    /** =====================================================
     *  2️⃣ Laporan Perubahan Laba Ditahan (fix)
     * ==================================================== */
   public function retainedEarnings(Request $request)
{
    // ✅ 1. Tentukan periode
    if ($request->filled('month')) {
        [$year, $month] = explode('-', $request->month);
        $startDate = date('Y-m-01', strtotime("$year-$month-01"));
        $endDate   = date('Y-m-t', strtotime($startDate));
    } elseif ($request->filled(['start_date', 'end_date'])) {
        $startDate = $request->start_date;
        $endDate   = $request->end_date;
    } else {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate   = now()->endOfMonth()->toDateString();
    }

    /*
    ===========================================
    ✅ 2. HITUNG SALDO AWAL (BEFORE PERIOD)
    ===========================================
    */

    // Laba rugi kumulatif sebelum periode
    $prevIncomeQuery = $this->baseQuery()
        ->whereIn('accounts.type', ['revenue', 'expense'])
        ->select(
            'accounts.type',
            DB::raw('SUM(transaction_details.debit) as debit'),
            DB::raw('SUM(transaction_details.credit) as credit')
        )
        ->where('transactions.date', '<', $startDate)
        ->groupBy('accounts.type')
        ->get();

    $prevRevenue = $prevIncomeQuery->where('type', 'revenue')->sum(fn($a) => $a->credit - $a->debit);
    $prevExpense = $prevIncomeQuery->where('type', 'expense')->sum(fn($a) => $a->debit - $a->credit);
    $prevNetIncome = $prevRevenue - $prevExpense;

    // Laba Ditahan historis
    $prevRetained = $this->baseQuery()
        ->where('accounts.name', 'LIKE', '%Laba Ditahan%')
        ->selectRaw('SUM(transaction_details.credit - transaction_details.debit) as total')
        ->where('transactions.date', '<', $startDate)
        ->value('total') ?? 0;

    // Prive/dividen historis
    $prevDividends = $this->baseQuery()
        ->where(function ($q) {
            $q->where('accounts.name', 'LIKE', '%Prive%')
              ->orWhere('accounts.name', 'LIKE', '%Dividen%');
        })
        ->selectRaw('SUM(transaction_details.debit - transaction_details.credit) as total')
        ->where('transactions.date', '<', $startDate)
        ->value('total') ?? 0;

    // ✅ Beginning = retained + net income before period - prive sebelum periode
    $beginning = $prevRetained + $prevNetIncome - $prevDividends;


    /*
    ===========================================
    ✅ 3. LABA PERIODE SEKARANG
    ===========================================
    */
    $incomeQuery = $this->baseQuery()
        ->whereIn('accounts.type', ['revenue', 'expense'])
        ->select(
            'accounts.type',
            DB::raw('SUM(transaction_details.debit) as debit'),
            DB::raw('SUM(transaction_details.credit) as credit')
        )
        ->whereBetween('transactions.date', [$startDate, $endDate])
        ->groupBy('accounts.type')
        ->get();

    $revenue = $incomeQuery->where('type', 'revenue')->sum(fn($a) => $a->credit - $a->debit);
    $expense = $incomeQuery->where('type', 'expense')->sum(fn($a) => $a->debit - $a->credit);
    $income = $revenue - $expense;


    /*
    ===========================================
    ✅ 4. DIVIDEN / PRIVE PERIODE
    ===========================================
    */
    $dividends = $this->baseQuery()
        ->where(function ($q) {
            $q->where('accounts.name', 'LIKE', '%Prive%')
              ->orWhere('accounts.name', 'LIKE', '%Dividen%');
        })
        ->selectRaw('SUM(transaction_details.debit - transaction_details.credit) as total')
        ->whereBetween('transactions.date', [$startDate, $endDate])
        ->value('total') ?? 0;


    /*
    ===========================================
    ✅ 5. SALDO AKHIR
    ===========================================
    */
    $ending = $beginning + $income - $dividends;

    return response()->json([
        'beginning'  => round($beginning, 2),
        'income'     => round($income, 2),
        'dividends'  => round($dividends, 2),
        'ending'     => round($ending, 2),
        'period' => [
            'start' => $startDate,
            'end'   => $endDate,
        ]
    ]);
}



    /** =====================================================
     *  3️⃣ Neraca (Balance Sheet) - FIXED & auto balance
     * ==================================================== */
   public function balanceSheet(Request $request)
{
    $umkmId = auth()->user()->id ?? $request->umkm_id ?? 6; // fallback buat tinker
    $month = $request->month ?? now()->format('Y-m');
    $start = date('Y-m-01', strtotime($month));
    $end = date('Y-m-t', strtotime($month));

    // Ambil saldo kumulatif per akun sampai akhir periode
    $accounts = \App\Models\Account::select(
        'accounts.id',
        'accounts.name',
        'accounts.type',
        'accounts.normal_balance',
        DB::raw('
            COALESCE(SUM(
                CASE 
                    WHEN accounts.normal_balance = "debit" THEN td.debit - td.credit
                    ELSE td.credit - td.debit
                END
            ), 0) as balance
        ')
    )
        ->leftJoin('transaction_details as td', 'accounts.id', '=', 'td.account_id')
        ->leftJoin('transactions as t', 't.id', '=', 'td.transaction_id')
        ->where(function ($q) use ($umkmId) {
            $q->where('t.umkm_id', $umkmId)
              ->orWhereNull('t.umkm_id');
        })
        ->whereDate('t.date', '<=', $end)
        ->groupBy('accounts.id', 'accounts.name', 'accounts.type', 'accounts.normal_balance')
        ->get();

    // Kelompokkan per tipe akun
    $assets = $accounts->where('type', 'asset')->values();
    $liabilities = $accounts->where('type', 'liability')->values();
    $equity = $accounts->where('type', 'equity')->reject(function ($a) {
        return in_array(strtolower($a->name), ['prive', 'dividen']);
    })->values();

    // Ambil Laba Ditahan dari fungsi laba rugi
    $retained = $this->retainedEarnings($request)->getData(true);
    $endingRetained = $retained['ending'] ?? 0;

    // Cek apakah akun "Laba Ditahan" sudah ada di transaksi
$existingRetained = $equity->first(fn($e) => stripos($e->name, 'laba ditahan') !== false);

if ($existingRetained) {
    // Jangan tumpuk saldo laba ditahan yang sudah diakui sebelumnya
    $existingRetained->balance = $endingRetained;
} else {
    // Kalau belum ada akun Laba Ditahan, tambahkan manual
    $equity->push((object)[
        'name' => 'Laba Ditahan',
        'balance' => $endingRetained
    ]);
}


    // Hitung total
    $totalAssets = $assets->sum('balance');
    $totalLiabilities = $liabilities->sum('balance');
    $totalEquity = $equity->sum('balance');
    $balanced = abs(($totalAssets) - ($totalLiabilities + $totalEquity)) < 0.01;

    return response()->json([
        'assets' => $assets->map(fn($a) => ['name' => $a->name, 'balance' => round($a->balance, 2)]),
        'liabilities' => $liabilities->map(fn($a) => ['name' => $a->name, 'balance' => round($a->balance, 2)]),
        'equity' => $equity->map(fn($a) => ['name' => $a->name, 'balance' => round($a->balance, 2)]),
        'total_assets' => round($totalAssets, 2),
        'total_liabilities' => round($totalLiabilities, 2),
        'total_equity' => round($totalEquity, 2),
        'balanced' => $balanced,
        'period' => [
            'start' => $start,
            'end' => $end
        ],
    ]);
}


    /** =====================================================
     *  4️⃣ Laporan Arus Kas (sederhana)
     * ==================================================== */
   public function cashFlow(Request $request)
{
    [$year, $month] = explode('-', $request->month ?? now()->format('Y-m'));
    $startDate = date('Y-m-01', strtotime("$year-$month-01"));
    $endDate   = date('Y-m-t', strtotime($startDate));

    $umkmId = Auth::guard('umkm')->id();

    // 🔹 1️⃣ Ambil Laba Bersih dari laporan laba rugi
    $incomeStatement = $this->incomeStatement($request)->getData(true);
    $netIncome = $incomeStatement['netIncome'] ?? 0;

    // 🔹 2️⃣ Hitung Kas Awal (akun asset mengandung "Kas")
    $cashStart = $this->baseQuery()
        ->where('accounts.type', 'asset')
        ->where('accounts.name', 'LIKE', '%Kas%')
        ->where('transactions.date', '<', $startDate)
        ->selectRaw('SUM(transaction_details.debit - transaction_details.credit) as total')
        ->value('total') ?? 0;

    // 🔹 3️⃣ Hitung Kas Akhir
    $cashEnd = $this->baseQuery()
        ->where('accounts.type', 'asset')
        ->where('accounts.name', 'LIKE', '%Kas%')
        ->where('transactions.date', '<=', $endDate)
        ->selectRaw('SUM(transaction_details.debit - transaction_details.credit) as total')
        ->value('total') ?? 0;

    // 🔹 4️⃣ Hitung Penyusutan (non-kas)
    $depreciation = $this->baseQuery()
        ->where('accounts.name', 'LIKE', '%Penyusutan%')
        ->whereBetween('transactions.date', [$startDate, $endDate])
        ->selectRaw('SUM(transaction_details.debit - transaction_details.credit) as total')
        ->value('total') ?? 0;

    // 🔹 5️⃣ Penyesuaian Operasi (perubahan aset & kewajiban lancar)
    $operatingAdjustments = 0;

    // 👉 Perubahan Piutang (kenaikan piutang → mengurangi kas)
    $receivablesChange = $this->baseQuery()
        ->where('accounts.name', 'LIKE', '%Piutang%')
        ->selectRaw('
            SUM(CASE 
                WHEN transactions.date BETWEEN ? AND ? 
                THEN transaction_details.debit - transaction_details.credit 
                ELSE 0 END
            ) as change_amount
        ', [$startDate, $endDate])
        ->value('change_amount') ?? 0;
    $operatingAdjustments -= $receivablesChange;

    // 👉 Perubahan Utang Usaha (kenaikan utang → menambah kas)
    $payablesChange = $this->baseQuery()
        ->where('accounts.name', 'LIKE', '%Utang Usaha%')
        ->selectRaw('
            SUM(CASE 
                WHEN transactions.date BETWEEN ? AND ? 
                THEN transaction_details.credit - transaction_details.debit 
                ELSE 0 END
            ) as change_amount
        ', [$startDate, $endDate])
        ->value('change_amount') ?? 0;
    $operatingAdjustments += $payablesChange;

    // 👉 Total arus kas operasi
    $operating = $netIncome + $depreciation + $operatingAdjustments;

    // 🔹 6️⃣ Aktivitas Investasi (pembelian/penjualan aset tetap)
    $investing = $this->baseQuery()
        ->where('accounts.type', 'asset')
        ->where(function ($q) {
            $q->where('accounts.name', 'LIKE', '%Peralatan%')
              ->orWhere('accounts.name', 'LIKE', '%Kendaraan%')
              ->orWhere('accounts.name', 'LIKE', '%Tanah%');
        })
        ->whereBetween('transactions.date', [$startDate, $endDate])
        ->selectRaw('SUM(transaction_details.debit - transaction_details.credit) as total')
        ->value('total') ?? 0;
    // biasanya negatif (karena pembelian aset)

    // 🔹 7️⃣ Aktivitas Pendanaan (modal, prive, pinjaman)
    $financing = $this->baseQuery()
        ->where(function ($q) {
            $q->where('accounts.name', 'LIKE', '%Modal%')
              ->orWhere('accounts.name', 'LIKE', '%Prive%')
              ->orWhere('accounts.name', 'LIKE', '%Dividen%')
              ->orWhere('accounts.name', 'LIKE', '%Pinjaman%');
        })
        ->whereBetween('transactions.date', [$startDate, $endDate])
        ->selectRaw('SUM(transaction_details.credit - transaction_details.debit) as total')
        ->value('total') ?? 0;

    // 🔹 8️⃣ Kenaikan/Penurunan Kas
    $netChange = $cashEnd - $cashStart;

    // 🔹 9️⃣ Return hasil
    return response()->json([
        'period' => [
            'start' => $startDate,
            'end'   => $endDate,
        ],
        'cash_start' => round($cashStart, 2),
        'operating'  => round($operating, 2),
        'investing'  => round($investing, 2),
        'financing'  => round($financing, 2),
        'net_change_in_cash' => round($netChange, 2),
        'cash_end'   => round($cashEnd, 2),
        'details' => [
            'net_income' => round($netIncome, 2),
            'depreciation' => round($depreciation, 2),
            'operating_adjustments' => round($operatingAdjustments, 2),
        ],
    ]);
}


/**
 * Helper ambil saldo akun kumulatif sampai tanggal tertentu
 */
private function getBalancesUntil($endDate, $mode = 'before_end')
{
    $rows = DB::table('transaction_details as td')
        ->join('transactions as t', 't.id', '=', 'td.transaction_id')
        ->join('accounts as a', 'a.id', '=', 'td.account_id')
        ->where('t.umkm_id', $this->currentUmkmId())
        ->whereDate('t.date', '<=', $endDate)
        ->select(
            'a.id',
            'a.normal_balance',
            DB::raw('SUM(td.debit) as debit'),
            DB::raw('SUM(td.credit) as credit')
        )
        ->groupBy('a.id', 'a.normal_balance')
        ->get();

    $balances = [];
    foreach ($rows as $r) {
        $balances[$r->id] = $r->normal_balance === 'debit'
            ? ($r->debit - $r->credit)
            : ($r->credit - $r->debit);
    }
    return $balances;
}


    /** =====================================================
     *  5️⃣ Ringkasan (Summary)
     * ==================================================== */
    public function summary(Request $request)
{
    $income = $this->incomeStatement($request)->getData(true);
    $retained = $this->retainedEarnings($request)->getData(true);
    $balance = $this->balanceSheet($request)->getData(true);
    $cashflow = $this->cashFlow($request)->getData(true);
    $monthly = $this->monthlyIncomeExpense($request)->getData(true);

    return response()->json([
        'income_statement' => [
            'revenue' => $income['revenue'] ?? 0,
            'expense' => $income['expense'] ?? 0,
            'netIncome' => $income['netIncome'] ?? 0,
            'details' => $income['details'] ?? [], // ✅ tambahkan ini
            
        ],
        'retained_earnings' => [
            'beginning' => $retained['beginning'] ?? 0,
            'income' => $retained['income'] ?? 0,
            'dividends' => $retained['dividends'] ?? 0,
            'ending' => $retained['ending'] ?? 0,
        ],
        'balance_sheet' => [
            'assets' => $balance['assets'] ?? [], // ✅ tambahkan
            'liabilities' => $balance['liabilities'] ?? [],
            'equity' => $balance['equity'] ?? [],
            'total_assets' => $balance['total_assets'] ?? 0,
            'total_liabilities' => $balance['total_liabilities'] ?? 0,
            'total_equity' => $balance['total_equity'] ?? 0,
            'balanced' => $balance['balanced'] ?? false,
        ],
         'cash_flow' => [
            'cash_start' => $cashflow['cash_start'] ?? 0,
            'cash_end' => $cashflow['cash_end'] ?? 0,
            'net_change_in_cash' => $cashflow['net_change_in_cash'] ?? 0,
            'operating' => $cashflow['operating'] ?? 0,
            'investing' => $cashflow['investing'] ?? 0,
            'financing' => $cashflow['financing'] ?? 0,
            'details' => $cashflow['details'] ?? [],
        
        ],
        'monthly_income_expense' => $monthly['monthly'] ?? [],
    ]);
}


/** =====================================================
 *  🔹 Grafik Pendapatan & Pengeluaran (per bulan)
 * ==================================================== */
public function monthlyIncomeExpense(Request $request)
{
    $umkmId = $this->currentUmkmId();
    $year = $request->input('year', now()->year);

    // Ambil total pendapatan & pengeluaran per bulan
    $monthly = DB::table('transaction_details')
        ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
        ->join('accounts', 'transaction_details.account_id', '=', 'accounts.id')
        ->where('transactions.umkm_id', $umkmId)
        ->whereYear('transactions.date', $year)
        ->whereIn('accounts.type', ['revenue', 'expense'])
        ->selectRaw('
            MONTH(transactions.date) as month_num,
            DATE_FORMAT(transactions.date, "%b") as month,
            SUM(CASE WHEN accounts.type = "revenue" THEN transaction_details.credit - transaction_details.debit ELSE 0 END) as revenue,
            SUM(CASE WHEN accounts.type = "expense" THEN transaction_details.debit - transaction_details.credit ELSE 0 END) as expense
        ')
        ->groupBy('month_num', 'month')
        ->orderBy('month_num')
        ->get();

    return response()->json([
        'year' => $year,
        'monthly' => $monthly
    ]);
}

public function downloadExcel(Request $request)
{
    // ✅ Gunakan default format 'YYYY-MM'
    $month = $request->input('month');
    $year  = $request->input('year');

    if (!$month || !$year) {
        $year = now()->format('Y');
        $month = now()->format('m');
    }

    // Gabungkan supaya formatnya konsisten seperti '2025-11'
    $request->merge([
        'month' => "$year-$month"
    ]);

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
