<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashFlowSheet implements FromArray, WithHeadings, WithTitle
{
    protected $cashFlow;

    public function __construct($cashFlow)
    {
        $this->cashFlow = $cashFlow;
    }

    public function array(): array
    {
        return [
            ['Arus Kas dari Aktivitas Operasi', $this->cashFlow['operating']],
            ['Arus Kas dari Aktivitas Investasi', $this->cashFlow['investing']],
            ['Arus Kas dari Aktivitas Pendanaan', $this->cashFlow['financing']],
            ['Kenaikan (Penurunan) Kas Bersih', $this->cashFlow['net']],
        ];
    }

    public function headings(): array
    {
        return ['Keterangan', 'Nilai'];
    }

    public function title(): string
    {
        return 'Arus Kas';
    }
}
