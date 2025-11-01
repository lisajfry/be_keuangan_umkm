<?php

namespace App\Exports;

use App\Models\Umkm;
use App\Http\Controllers\Admin\AdminReportController;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AdminSummaryExport implements WithMultipleSheets
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        $sheets = [];
        $controller = new AdminReportController();

        // ==========================================================
        // 🔹 1️⃣ Tambah Sheet Pembuka: "Summary Semua UMKM"
        // ==========================================================
        $summaryAll = $controller->summaryAllUmkm($this->request)->getData(true);

        $sheets[] = new \App\Exports\SummaryAllExport($summaryAll, 'Summary Semua UMKM');

        // ==========================================================
        // 🔹 2️⃣ Loop Setiap UMKM: laporan per UMKM
        // ==========================================================
        foreach (Umkm::all() as $umkm) {
            $req = clone $this->request;
            $req->merge(['umkm_id' => $umkm->id]);

            $data = [
                'month' => $req->input('month', now()->month),
                'year' => $req->input('year', now()->year),
                'nama_umkm' => $umkm->nama_umkm,
                'income_statement' => $controller->incomeStatement($req)->getData(true),
                'balance_sheet' => $controller->balanceSheet($req)->getData(true),
            ];

            $sheets[] = new \App\Exports\ReportExport($data, $umkm->nama_umkm);
        }

        return $sheets;
    }
}
