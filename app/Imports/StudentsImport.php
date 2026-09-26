<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Throwable;

class StudentsImport implements SkipsOnError, SkipsOnFailure, ToModel, WithCustomValueBinder, WithHeadingRow, WithValidation
{
    use Importable, SkipsFailures;

    public int $created = 0;

    public int $updated = 0;

    /** @var list<string> */
    public array $rowErrors = [];

    /** @var array<string, int> classroom name => id */
    protected array $classroomIdsByName;

    public function __construct()
    {
        $this->classroomIdsByName = Classroom::pluck('id', 'name')->all();
    }

    public function model(array $row): ?Student
    {
        $nis = trim((string) ($row['nis'] ?? ''));
        $classroomName = trim((string) ($row['kelas'] ?? ''));
        $classroomId = $this->classroomIdsByName[$classroomName] ?? null;

        if (! $classroomId) {
            $this->rowErrors[] = "NIS {$nis}: Kelas '{$classroomName}' tidak ditemukan. Pastikan nama kelas sama persis dengan Data Kelas.";

            return null;
        }

        $gender = $this->normalizeGender((string) ($row['jenis_kelamin'] ?? ''));
        if (! $gender) {
            $this->rowErrors[] = "NIS {$nis}: Jenis Kelamin harus diisi L atau P.";

            return null;
        }

        $student = Student::where('nis', $nis)->first() ?? new Student;
        $isNew = ! $student->exists;

        $student->nis = $nis;
        $student->name = trim((string) ($row['nama_lengkap'] ?? ''));
        $student->classroom_id = $classroomId;
        $student->gender = $gender;
        $student->is_active = $this->normalizeActiveStatus((string) ($row['status_aktif'] ?? 'Aktif'));

        $isNew ? $this->created++ : $this->updated++;

        return $student;
    }

    public function rules(): array
    {
        return [
            'nis' => ['required', 'string', 'max:50'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'kelas' => ['required', 'string'],
            'jenis_kelamin' => ['required', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi.',
            'nama_lengkap.required' => 'Nama Lengkap wajib diisi.',
            'kelas.required' => 'Kelas wajib diisi.',
            'jenis_kelamin.required' => 'Jenis Kelamin wajib diisi.',
        ];
    }

    public function onError(Throwable $e): void
    {
        $this->rowErrors[] = 'Terjadi kesalahan saat menyimpan salah satu baris: '.$e->getMessage();
    }

    /**
     * Force every cell to be read as a plain string. Without this, PhpSpreadsheet
     * auto-detects numeric-looking values (e.g. NIS "0101") and casts them to
     * int/float, silently stripping leading zeros.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }

    /**
     * @return list<string> combined messages from row-level validation failures and business rule errors
     */
    public function allErrorMessages(): array
    {
        $failureMessages = $this->failures()
            ->flatMap(fn ($failure) => collect($failure->errors())->map(
                fn ($message) => "Baris {$failure->row()}: {$message}"
            ))
            ->all();

        return array_merge($failureMessages, $this->rowErrors);
    }

    protected function normalizeGender(string $value): ?string
    {
        $value = strtoupper(trim($value));

        return match (true) {
            in_array($value, ['L', 'LAKI-LAKI', 'LAKI LAKI'], true) => 'L',
            in_array($value, ['P', 'PEREMPUAN'], true) => 'P',
            default => null,
        };
    }

    protected function normalizeActiveStatus(string $value): bool
    {
        $value = strtolower(trim($value));

        return ! in_array($value, ['tidak aktif', 'nonaktif', 'non-aktif', 'tidak', 'no', '0'], true);
    }
}
