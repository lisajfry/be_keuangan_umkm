<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MultiReportExport implements WithMultipleSheets
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new ReportSheetExport('Laporan Laba Rugi', $this->data['income_statement']['details'] ?? []),
            new ReportSheetExport('Laba Ditahan', [
                ['Income' => $this->data['retained_earnings']['income'] ?? 0],
                ['Dividends' => $this->data['retained_earnings']['dividends'] ?? 0],
                ['Retained' => $this->data['retained_earnings']['retained'] ?? 0],
            ]),
            new ReportSheetExport('Neraca - Aset', $this->data['balance_sheet']['asset'] ?? []),
            new ReportSheetExport('Neraca - Kewajiban', $this->data['balance_sheet']['liability'] ?? []),
            new ReportSheetExport('Neraca - Ekuitas', $this->data['balance_sheet']['equity'] ?? []),
            new ReportSheetExport('Arus Kas', [
                ['Operating' => $this->data['cash_flow']['operating'] ?? 0],
                ['Investing' => $this->data['cash_flow']['investing'] ?? 0],
                ['Financing' => $this->data['cash_flow']['financing'] ?? 0],
                ['Net' => $this->data['cash_flow']['net'] ?? 0],
            ]),
        ];
    }
}
