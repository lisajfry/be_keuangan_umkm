<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportExport implements FromArray, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
    }

    public function array(): array
    {
        $rows = [];

        // === HEADER LAPORAN ===
        $rows[] = ['LAPORAN KEUANGAN UMKM'];
        $rows[] = ['Nama UMKM', $this->data['nama_umkm'] ?? '-'];
        $rows[] = ['Periode', $this->getMonthName(date('m', strtotime($this->data['month']))) . ' ' . $this->data['year']];
        $rows[] = ['Tanggal Cetak', now()->format('d-m-Y')];
        $rows[] = ['']; // spasi kosong

        /**
         * ==========================
         * 1️⃣ LAPORAN LABA RUGI
         * ==========================
         */
        $rows[] = ['=== LAPORAN LABA RUGI ==='];
        $rows[] = ['Keterangan Akun', 'Nilai (Rp)'];

        foreach ($this->data['income_statement']['details'] ?? [] as $d) {
            $netValue = ($d['total_credit'] ?? 0) - ($d['total_debit'] ?? 0);
            $rows[] = [
                $d['name'] ?? '',
                $this->formatCurrency($netValue),
            ];
        }

        $rows[] = [''];
        $rows[] = ['Total Pendapatan', $this->formatCurrency(abs($this->data['income_statement']['revenue'] ?? 0))];
        $rows[] = ['Total Beban', $this->formatCurrency(abs($this->data['income_statement']['expense'] ?? 0))];
        $rows[] = ['Laba Bersih (Net Income)', $this->formatCurrency($this->data['income_statement']['netIncome'] ?? 0)];
        $rows[] = [''];

        /**
         * ==========================
         * 2️⃣ PERUBAHAN LABA DITAHAN
         * ==========================
         */
        $rows[] = ['=== LAPORAN PERUBAHAN LABA DITAHAN ==='];
        $rows[] = ['Keterangan', 'Nilai (Rp)'];
        $rows[] = ['Saldo Awal', $this->formatCurrency($this->data['retained_earnings']['beginning'] ?? 0)];
        $rows[] = ['Tambah: Laba Bersih', $this->formatCurrency($this->data['retained_earnings']['income'] ?? 0)];
        $rows[] = ['Kurang: Dividen/Prive', $this->formatCurrency($this->data['retained_earnings']['dividends'] ?? 0)];
        $rows[] = ['Saldo Akhir', $this->formatCurrency($this->data['retained_earnings']['ending'] ?? 0)];

        $rows[] = [''];

        /**
         * ==========================
         * 3️⃣ NERACA
         * ==========================
         */
        $rows[] = ['=== NERACA ==='];
        $rows[] = ['ASET', 'Nilai (Rp)', 'KEWAJIBAN & EKUITAS', 'Nilai (Rp)'];

        $assets = $this->data['balance_sheet']['assets'] ?? [];
        $liabilities = $this->data['balance_sheet']['liabilities'] ?? [];
        $equity = $this->data['balance_sheet']['equity'] ?? [];

        $max = max(count($assets), count($liabilities) + count($equity));

        for ($i = 0; $i < $max; $i++) {
            $assetName = $assets[$i]['name'] ?? '';
            $assetVal = $assets[$i]['balance'] ?? 0;

            if ($i < count($liabilities)) {
                $liabName = $liabilities[$i]['name'];
                $liabVal = $liabilities[$i]['balance'];
            } else {
                $eqIndex = $i - count($liabilities);
                $liabName = $equity[$eqIndex]['name'] ?? '';
                $liabVal = $equity[$eqIndex]['balance'] ?? 0;
            }

            $rows[] = [
                $assetName,
                $this->formatCurrency($assetVal),
                $liabName,
                $this->formatCurrency($liabVal),
            ];
        }

        $rows[] = [''];
        $rows[] = [
            'Total Aset',
            $this->formatCurrency($this->data['balance_sheet']['total_assets'] ?? 0),
            'Total Kewajiban + Ekuitas',
            $this->formatCurrency(($this->data['balance_sheet']['total_liabilities'] ?? 0) + ($this->data['balance_sheet']['total_equity'] ?? 0)),
        ];
        $rows[] = [
            'Status Neraca',
            ($this->data['balance_sheet']['balanced'] ?? false) ? '✅ Seimbang' : '❌ Tidak Seimbang',
            '', '',
        ];
        $rows[] = [''];

        /**
         * ==========================
         * 4️⃣ ARUS KAS
         * ==========================
         */
        $rows[] = ['=== LAPORAN ARUS KAS ==='];
        $rows[] = ['Aktivitas', 'Nilai (Rp)'];
        $rows[] = ['Arus Kas dari Aktivitas Operasi', $this->formatCurrency($this->data['cash_flow']['operating'] ?? 0)];
$rows[] = ['Arus Kas dari Aktivitas Investasi', $this->formatCurrency($this->data['cash_flow']['investing'] ?? 0)];
$rows[] = ['Arus Kas dari Aktivitas Pendanaan', $this->formatCurrency($this->data['cash_flow']['financing'] ?? 0)];
$rows[] = ['Kenaikan (Penurunan) Kas Bersih', $this->formatCurrency($this->data['cash_flow']['net_change_in_cash'] ?? 0)];
$rows[] = ['Saldo Kas Awal', $this->formatCurrency($this->data['cash_flow']['cash_start'] ?? 0)];
$rows[] = ['Saldo Kas Akhir', $this->formatCurrency($this->data['cash_flow']['cash_end'] ?? 0)];

        return $rows;
    }

    protected function formatCurrency($value)
    {
        if ($value === null || $value === '') return '';
        $value = (float) $value;
        return number_format($value, 2, ',', '.');
    }

    protected function getMonthName($month)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $months[(int)$month] ?? $month;
    }

   


    public function styles(Worksheet $sheet)
{
    // Judul utama laporan
    $sheet->mergeCells('A1:D1');
    $sheet->setCellValue('A1', 'LAPORAN KEUANGAN PERUSAHAAN');
    $sheet->getStyle('A1')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 16,
            'name' => 'Calibri',
            'color' => ['rgb' => '1F497D'],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
    ]);
    $sheet->getRowDimension(1)->setRowHeight(28);

    $highestRow = $sheet->getHighestRow();

    // Font & border global
    $sheet->getStyle("A1:D{$highestRow}")->applyFromArray([
        'font' => [
            'name' => 'Calibri',
            'size' => 11,
            'color' => ['rgb' => '000000'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'DDDDDD'],
            ],
        ],
    ]);

    // === SECTION HEADER (=== Laporan ...) ===
    foreach (range(1, $highestRow) as $row) {
        $val = $sheet->getCell("A{$row}")->getValue();
        if (str_starts_with($val, '===')) {
            $title = trim(str_replace('=', '', $val));
            $sheet->mergeCells("A{$row}:D{$row}");
            $sheet->setCellValue("A{$row}", strtoupper($title));

            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 13,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '305496'], // biru navy elegan
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(25);
        }
    }

    // === HEADER TABEL ===
    foreach (range(1, $highestRow) as $row) {
        $val = $sheet->getCell("A{$row}")->getValue();
        if (in_array($val, ['Keterangan Akun', 'Keterangan', 'ASET', 'Aktivitas', 'Kewajiban', 'Ekuitas'])) {
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1F497D'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9EDF5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(20);
        }
    }

    // === FORMAT ANGKA ===
    $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    $sheet->getStyle('D:D')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    $sheet->getStyle('B:B')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('D:D')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // === RATA KIRI TEKS ===
    $sheet->getStyle('A:A')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheet->getStyle('C:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

    // === BORDER GANDA UNTUK TOTAL DAN LABA BERSIH ===
    // === GAYA UNTUK BARIS PENTING ===
foreach (range(1, $highestRow) as $row) {
    $val = strtoupper((string) $sheet->getCell("A{$row}")->getValue());

    // Bold + border ganda (untuk total, laba bersih, kenaikan kas, dan saldo akhir)
    if (
        str_contains($val, 'TOTAL') ||
        str_contains($val, 'LABA BERSIH') ||
        str_contains($val, 'KENAIKAN (PENURUNAN) KAS BERSIH') ||
        str_contains($val, 'SALDO AKHIR')
    ) {
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '1F497D'],
                'size' => 12,
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['rgb' => '000000'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_DOUBLE,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    // Pastikan "Tambah: Laba Bersih" tetap normal (tidak tebal, tanpa border ganda)
    if (str_contains($val, 'TAMBAH: LABA BERSIH')) {
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray([
            'font' => [
                'bold' => false,
                'color' => ['rgb' => '000000'],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_NONE,
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_NONE,
                ],
            ],
        ]);
    }
}


    // === AUTO SIZE ===
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // === SPASI ANTAR BAGIAN ===
    $sheet->getDefaultRowDimension()->setRowHeight(18);
}


}
