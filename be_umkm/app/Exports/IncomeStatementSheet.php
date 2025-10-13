<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class IncomeStatementSheet implements FromArray, WithHeadings, WithTitle
{
    protected $income;

    public function __construct($income)
    {
        $this->income = $income;
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->income['details'] as $item) {
            $rows[] = [
                $item['name'],
                strtoupper($item['type']),
                $item['total_debit'] ?? 0,
                $item['total_credit'] ?? 0,
            ];
        }

        $rows[] = ['', '', '', ''];
        $rows[] = ['Total Pendapatan', '', $this->income['revenue'], ''];
        $rows[] = ['Total Beban', '', $this->income['expense'], ''];
        $rows[] = ['Laba Bersih', '', $this->income['netIncome'], ''];

        return $rows;
    }

    public function headings(): array
    {
        return ['Nama Akun', 'Jenis', 'Debit', 'Kredit'];
    }

    public function title(): string
    {
        return 'Laporan Laba Rugi';
    }
}
