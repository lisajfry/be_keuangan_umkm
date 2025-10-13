<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BalanceSheetSheet implements FromArray, WithHeadings, WithTitle
{
    protected $balance;

    public function __construct($balance)
    {
        $this->balance = $balance;
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['--- ASET ---', ''];
        foreach ($this->balance['asset'] as $item) {
            $rows[] = [$item['name'], $item['balance'] ?? 0];
        }

        $rows[] = ['', ''];
        $rows[] = ['--- KEWAJIBAN ---', ''];
        foreach ($this->balance['liability'] as $item) {
            $rows[] = [$item['name'], $item['balance'] ?? 0];
        }

        $rows[] = ['', ''];
        $rows[] = ['--- EKUITAS ---', ''];
        foreach ($this->balance['equity'] as $item) {
            $rows[] = [$item['name'], $item['balance'] ?? 0];
        }

        $rows[] = ['', ''];
        $rows[] = ['Total Aset', $this->balance['total_assets']];
        $rows[] = ['Total Kewajiban', $this->balance['total_liabilities']];
        $rows[] = ['Total Ekuitas', $this->balance['total_equity']];
        $rows[] = ['Seimbang', $this->balance['balanced'] ? 'Ya' : 'Tidak'];

        return $rows;
    }

    public function headings(): array
    {
        return ['Nama Akun', 'Saldo'];
    }

    public function title(): string
    {
        return 'Neraca';
    }
}
