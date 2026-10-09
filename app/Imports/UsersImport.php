<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Imports staff and parent (wali murid) accounts; a parent may list several
 * students (siblings) as comma-separated NIS values. Rows are matched by username:
 * existing accounts are updated, new usernames are created. Each row follows the
 * same rules as the user form, including who may grant or change Super Admin.
 */
class UsersImport implements ToCollection, WithCustomValueBinder, WithHeadingRow
{
    use Importable;

    public int $created = 0;

    public int $updated = 0;

    /** @var list<string> */
    public array $rowErrors = [];

    /** @var array<string, int> */
    protected array $classroomIdsByName;

    /** @var array<string, int> */
    protected array $studentIdsByNis;

    public function __construct(protected User $importer)
    {
        $this->classroomIdsByName = Classroom::pluck('id', 'name')->all();
        $this->studentIdsByNis = Student::pluck('id', 'nis')->all();
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $values = $row->map(fn ($value) => trim((string) $value))->all();

            if (implode('', $values) === '') {
                continue;
            }

            $error = $this->importRow($values);

            if ($error !== null) {
                // +2: the heading row is row 1 and collection indexes start at 0.
                $this->rowErrors[] = 'Baris '.($index + 2).": {$error}";
            }
        }
    }

    /**
     * @param  array<string, string>  $row
     * @return string|null an error message, or null when the row was saved
     */
    protected function importRow(array $row): ?string
    {
        $username = $row['username'] ?? '';
        $role = $row['peran'] ?? '';
        // Usernames are unique across the whole platform, so look beyond the active institution.
        $user = User::withoutGlobalScopes()->where('username', $username)->first();

        if ($user && ($user->isSuperAdmin() || $user->institution_id !== $this->importer->institution_id)) {
            return "Username @{$username} sudah dipakai akun lain di luar lembaga Anda. Gunakan username lain.";
        }

        if ($user && ! $this->importer->canManage($user)) {
            return "Akun @{$username} adalah Admin lain dan hanya dapat diubah oleh Super Admin.";
        }

        if ($user?->is($this->importer) && $role !== $user->role) {
            return 'Peran akun Anda sendiri tidak dapat diubah lewat impor.';
        }

        $validator = Validator::make($row, [
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'peran' => ['required', Rule::in($this->importer->assignableRoles())],
            'nis_santri' => ['nullable', 'required_if:peran,'.User::ROLE_WALI_MURID],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ], [
            'nama_lengkap.required' => 'Nama Lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai akun lain.',
            'peran.required' => 'Peran wajib diisi.',
            'peran.in' => "Peran '{$role}' tidak dikenal atau tidak boleh Anda berikan.",
            'nis_santri.required_if' => 'NIS Santri wajib diisi untuk peran wali_murid.',
            'password.required' => 'Password wajib diisi untuk akun baru.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        if ($validator->fails()) {
            return implode(' ', $validator->errors()->all());
        }

        $isClassroomScoped = in_array($role, User::CLASSROOM_SCOPED_ROLES, true);
        $classroomNames = $this->splitList($row['kelas'] ?? '');
        $unknownClassrooms = array_diff($classroomNames, array_keys($this->classroomIdsByName));

        if ($isClassroomScoped && $unknownClassrooms !== []) {
            return "Kelas '".implode("', '", $unknownClassrooms)."' tidak ditemukan. Pastikan nama kelas sama persis dengan Data Kelas.";
        }

        $isParent = $role === User::ROLE_WALI_MURID;
        $studentNisList = $this->splitList($row['nis_santri'] ?? '');
        $unknownNis = array_diff($studentNisList, array_map('strval', array_keys($this->studentIdsByNis)));

        if ($isParent && $unknownNis !== []) {
            return "NIS Santri '".implode("', '", $unknownNis)."' tidak ditemukan.";
        }

        $isNew = $user === null;

        DB::transaction(function () use ($user, $row, $role, $isClassroomScoped, $classroomNames, $isParent, $studentNisList): void {
            $user ??= new User;
            $user->fill([
                'name' => $row['nama_lengkap'],
                'username' => $row['username'],
                'email' => ($row['email'] ?? '') ?: null,
                'role' => $role,
            ]);

            if (($row['password'] ?? '') !== '') {
                $user->password = $row['password'];
            }

            $user->save();

            $user->classrooms()->sync(
                $isClassroomScoped ? array_map(fn ($name) => $this->classroomIdsByName[$name], $classroomNames) : []
            );
            $user->children()->sync(
                $isParent ? array_map(fn ($nis) => $this->studentIdsByNis[$nis], $studentNisList) : []
            );
        });

        $isNew ? $this->created++ : $this->updated++;

        return null;
    }

    /**
     * Split a comma-separated cell ("1, 2") into trimmed, non-empty values.
     *
     * @return list<string>
     */
    protected function splitList(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)), fn ($item) => $item !== '')));
    }

    /**
     * Read every cell as a plain string so values like NIS "0101" or
     * all-digit passwords keep their leading zeros.
     */
    public function bindValue(Cell $cell, $value): bool
    {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

        return true;
    }
}
