<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class SummaryAllExport implements FromView, WithTitle
{
    protected $data;
    protected $sheetName;

    public function __construct($data, $sheetName = 'Summary Semua UMKM')
    {
        $this->data = $data;
        $this->sheetName = $sheetName;
    }

    public function view(): View
    {
        return view('exports.summary-all', [
            'data' => $this->data,
        ]);
    }

    public function title(): string
    {
        return $this->sheetName;
    }
}
