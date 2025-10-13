<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class RetainedEarningsSheet implements FromArray, WithHeadings, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return [
            ['Laba Bersih', $this->data['income']],
            ['Dividen', $this->data['dividends']],
            ['Saldo Laba Ditahan', $this->data['retained']],
        ];
    }

    public function headings(): array
    {
        return ['Keterangan', 'Nilai'];
    }

    public function title(): string
    {
        return 'Perubahan Modal';
    }
}
