<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ReportExport implements 
    FromArray, 
    WithHeadings, 
    WithTitle, 
    ShouldAutoSize, 
    WithStyles, 
    WithCustomStartCell, 
    WithColumnFormatting
{
    protected $data;
    protected $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /** =====================================================
     *  🔹 Data isi tabel
     * ==================================================== */
    public function array(): array
    {
        $rows = [];

        foreach ($this->data['summary_per_umkm'] ?? [] as $umkm) {
            $rows[] = [
                $umkm['nama_umkm'] ?? '-',
                $umkm['revenue'] ?? 0,
                $umkm['expense'] ?? 0,
                $umkm['net_income'] ?? 0,
                $umkm['total_assets'] ?? 0,
                $umkm['total_liabilities'] ?? 0,
                $umkm['total_equity'] ?? 0,
            ];
        }

        if (!empty($this->data['total_all'])) {
            $t = $this->data['total_all'];
            $rows[] = [
                'TOTAL SEMUA UMKM',
                $t['revenue'] ?? 0,
                $t['expense'] ?? 0,
                $t['net_income'] ?? 0,
                $t['assets'] ?? 0,
                $t['liabilities'] ?? 0,
                $t['equity'] ?? 0,
            ];
        }

        return $rows;
    }

    /** =====================================================
     *  🔹 Header kolom
     * ==================================================== */
    public function headings(): array
    {
        return [
            'Nama UMKM',
            'Pendapatan',
            'Beban',
            'Laba Bersih',
            'Aset',
            'Kewajiban',
            'Ekuitas',
        ];
    }

    /** =====================================================
     *  🔹 Mulai dari sel A3 (karena baris 1–2 untuk judul)
     * ==================================================== */
    public function startCell(): string
    {
        return 'A3';
    }

    /** =====================================================
     *  🔹 Nama sheet (tab di Excel)
     * ==================================================== */
    public function title(): string
    {
        return "Laporan {$this->data['month']}/{$this->data['year']}";
    }

    /** =====================================================
     *  🔹 Format kolom (angka ke format mata uang Rupiah)
     * ==================================================== */

    const FORMAT_CURRENCY_IDR_SIMPLE = '"Rp"#,##0';


    public function columnFormats(): array
    {
        return [
            'B' => self::FORMAT_CURRENCY_IDR_SIMPLE,
            'C' => self::FORMAT_CURRENCY_IDR_SIMPLE,
            'D' => self::FORMAT_CURRENCY_IDR_SIMPLE,
            'E' => self::FORMAT_CURRENCY_IDR_SIMPLE,
            'F' => self::FORMAT_CURRENCY_IDR_SIMPLE,
            'G' => self::FORMAT_CURRENCY_IDR_SIMPLE,
        ];
    }



    /** =====================================================
     *  🔹 Styling (judul + header bold)
     * ==================================================== */
    public function styles(Worksheet $sheet)
    {
        $monthName = $this->monthNames[intval($this->data['month'])] ?? 'Semua Bulan';
        $title = "Laporan Keuangan Semua UMKM — {$monthName} {$this->data['year']}";

        // 🔹 Merge & buat judul besar di atas
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

        // 🔹 Header tabel (A3:G3)
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getAlignment()->setHorizontal('center');

        // 🔹 Bold baris total (baris terakhir)
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle("A{$lastRow}:G{$lastRow}")->getFont()->setBold(true);
    }
}
