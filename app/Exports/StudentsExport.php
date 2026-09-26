<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Student>  $students
     */
    public function __construct(protected Collection $students) {}

    public function collection(): Collection
    {
        return $this->students;
    }

    public function headings(): array
    {
        return ['NIS', 'Nama Lengkap', 'Kelas', 'Jenis Kelamin', 'Status Aktif'];
    }

    /**
     * @param  Student  $student
     */
    public function map($student): array
    {
        return [
            $student->nis,
            $student->name,
            $student->classroom->name ?? '',
            $student->gender,
            $student->is_active ? 'Aktif' : 'Nonaktif',
        ];
    }
}
