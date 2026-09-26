<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function array(): array
    {
        return [
            ['2026001', 'Contoh Nama Santri', '1', 'L', 'Aktif'],
        ];
    }

    public function headings(): array
    {
        return ['NIS', 'Nama Lengkap', 'Kelas', 'Jenis Kelamin', 'Status Aktif'];
    }
}
