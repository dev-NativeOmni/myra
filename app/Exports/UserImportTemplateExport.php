<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public const HEADINGS = ['Nama Lengkap', 'Username', 'Email', 'Peran', 'Kelas', 'NIS Santri', 'Password'];

    public function array(): array
    {
        return [
            ['Ustadz Contoh', 'ustadz_contoh', 'ustadz@example.com', 'guru', '1, 2', '', 'GantiPassword123'],
            ['Ustadzah Contoh', 'wali_kelas_1', '', 'wali_kelas', '1', '', 'GantiPassword123'],
            ['Bapak Wali Contoh', 'wali_2026001', '', 'wali_murid', '', '2026001', 'GantiPassword123'],
        ];
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }
}
