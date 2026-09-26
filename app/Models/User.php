<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Role Constants
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_GURU = 'guru';

    public const ROLE_WALI_KELAS = 'wali_kelas';

    public const ROLE_KESANTRIAN = 'kesantrian';

    public const ROLE_TU = 'tu';

    public const ROLE_WALI_MURID = 'wali_murid';

    /**
     * Roles whose area of responsibility is limited to specific assigned classrooms.
     * Roles not in this list (admin, super_admin, tu) act lembaga-wide.
     */
    public const CLASSROOM_SCOPED_ROLES = [
        self::ROLE_GURU,
        self::ROLE_WALI_KELAS,
        self::ROLE_KESANTRIAN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'student_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the student associated with the parent (Wali Murid).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Classrooms this user is assigned responsibility for (guru, wali kelas, kesantrian).
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class);
    }

    /**
     * Check if user has a specific role or array of roles.
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles, true);
        }

        return $this->role === $roles;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isGuru(): bool
    {
        return $this->role === self::ROLE_GURU;
    }

    public function isWaliKelas(): bool
    {
        return $this->role === self::ROLE_WALI_KELAS;
    }

    public function isKesantrian(): bool
    {
        return $this->role === self::ROLE_KESANTRIAN;
    }

    public function isTu(): bool
    {
        return $this->role === self::ROLE_TU;
    }

    public function isWaliMurid(): bool
    {
        return $this->role === self::ROLE_WALI_MURID;
    }

    /**
     * Get the evaluation modules this role is allowed to edit.
     * Super Admin and Admin can edit every module; each staff role
     * is limited to its own area of responsibility (view-only for the rest).
     *
     * @return list<string>
     */
    public function editableModules(): array
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN => [
                ModuleField::MODULE_TAHFIDZ,
                ModuleField::MODULE_KESANTRIAN,
                ModuleField::MODULE_AKADEMIK,
                ModuleField::MODULE_ADMINISTRASI,
            ],
            self::ROLE_GURU => [ModuleField::MODULE_TAHFIDZ],
            self::ROLE_KESANTRIAN => [ModuleField::MODULE_KESANTRIAN],
            self::ROLE_WALI_KELAS => [ModuleField::MODULE_AKADEMIK],
            self::ROLE_TU => [ModuleField::MODULE_ADMINISTRASI],
            default => [],
        };
    }

    /**
     * Check if this user's role may edit the given evaluation module.
     */
    public function canEditModule(string $module): bool
    {
        return in_array($module, $this->editableModules(), true);
    }

    /**
     * Check if this user's role is limited to specific assigned classrooms
     * rather than acting lembaga-wide.
     */
    public function isClassroomScoped(): bool
    {
        return in_array($this->role, self::CLASSROOM_SCOPED_ROLES, true);
    }

    /**
     * Get the IDs of classrooms this user is responsible for, or null if the
     * role is lembaga-wide (unrestricted) rather than classroom-scoped.
     *
     * @return list<int>|null
     */
    public function responsibleClassroomIds(): ?array
    {
        if (! $this->isClassroomScoped()) {
            return null;
        }

        return $this->classrooms()->pluck('classrooms.id')->all();
    }

    /**
     * Check if this user may edit data belonging to the given classroom.
     * Lembaga-wide roles (admin, super_admin, tu) may always edit;
     * classroom-scoped roles may only edit their assigned classrooms.
     */
    public function canEditClassroom(?int $classroomId): bool
    {
        $responsibleIds = $this->responsibleClassroomIds();

        if ($responsibleIds === null) {
            return true;
        }

        return $classroomId !== null && in_array($classroomId, $responsibleIds, true);
    }

    /**
     * Get formatted role label.
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_GURU => Institution::term('teacher').' Tahfidz',
            self::ROLE_WALI_KELAS => 'Wali Kelas',
            self::ROLE_KESANTRIAN => 'Kesantrian',
            self::ROLE_TU => 'Tata Usaha (TU)',
            self::ROLE_WALI_MURID => 'Wali Murid',
            default => ucfirst(str_replace('_', ' ', $this->role)),
        };
    }
}
