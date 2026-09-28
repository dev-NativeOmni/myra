<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exports user accounts in the same column layout the import expects, so an
 * exported file can be edited and re-imported. Passwords are never exported;
 * the Password column is left blank, which keeps existing passwords on re-import.
 */
class UsersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, User>  $users
     */
    public function __construct(protected Collection $users) {}

    public function collection(): Collection
    {
        return $this->users;
    }

    public function headings(): array
    {
        return UserImportTemplateExport::HEADINGS;
    }

    /**
     * @param  User  $user
     * @return list<string|null>
     */
    public function map($user): array
    {
        return [
            $user->name,
            $user->username,
            $user->email,
            $user->role,
            $user->classrooms->pluck('name')->implode(', '),
            $user->student?->nis,
            null,
        ];
    }
}
