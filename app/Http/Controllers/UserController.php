<?php

namespace App\Http\Controllers;

use App\Exports\UserImportTemplateExport;
use App\Exports\UsersExport;
use App\Imports\UsersImport;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): View
    {
        $users = $this->filteredUsers($request)->latest()->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    /**
     * Export the users matching the current filters to an Excel file that can be re-imported.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $users = $this->filteredUsers($request)->orderBy('role')->orderBy('name')->get();

        return Excel::download(new UsersExport($users), 'Data_Pengguna.xlsx');
    }

    /**
     * Download an Excel template for importing staff and parent accounts.
     */
    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new UserImportTemplateExport, 'Template_Import_Pengguna.xlsx');
    }

    /**
     * Import staff and parent accounts from an uploaded Excel/CSV file.
     * Existing accounts are matched and updated by username; the rest are created.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new UsersImport($request->user());

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->withErrors(["Gagal membaca berkas: {$e->getMessage()}. Pastikan formatnya sesuai template."]);
        }

        if ($import->created === 0 && $import->updated === 0) {
            return back()->withErrors($import->rowErrors ?: ['File tidak berisi data pengguna yang valid untuk diimpor.']);
        }

        $redirect = redirect()->route('users.index')
            ->with('success', "Impor selesai: {$import->created} akun baru dibuat, {$import->updated} akun diperbarui.");

        return $import->rowErrors ? $redirect->withErrors($import->rowErrors) : $redirect;
    }

    /**
     * @return Builder<User>
     */
    private function filteredUsers(Request $request): Builder
    {
        return User::with(['student', 'classrooms'])
            ->when($request->filled('role'), fn (Builder $query) => $query->where('role', $request->role))
            ->when($request->filled('search'), fn (Builder $query) => $query->where(function (Builder $query) use ($request) {
                $query->whereLike('name', "%{$request->search}%")
                    ->orWhereLike('username', "%{$request->search}%")
                    ->orWhereLike('email', "%{$request->search}%");
            }));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $students = Student::where('is_active', true)->orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $currentUser = Auth::user();

        // Admin cannot create Super Admin
        $roles = [
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_GURU => 'Guru Tahfidz',
            User::ROLE_WALI_KELAS => 'Wali Kelas',
            User::ROLE_KESANTRIAN => 'Kesantrian / Wali Asrama',
            User::ROLE_TU => 'Tata Usaha (TU)',
            User::ROLE_WALI_MURID => 'Wali Murid',
        ];

        if ($currentUser->isSuperAdmin()) {
            $roles = [User::ROLE_SUPER_ADMIN => 'Super Admin'] + $roles;
        }

        return view('users.create', compact('students', 'classrooms', 'roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $allowedRoles = Auth::user()->assignableRoles();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in($allowedRoles)],
            'student_id' => 'nullable|required_if:role,wali_murid|exists:students,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $classroomIds = $validated['classroom_ids'] ?? [];
        unset($validated['classroom_ids']);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if (in_array($user->role, User::CLASSROOM_SCOPED_ROLES, true)) {
            $user->classrooms()->sync($classroomIds);
        }

        return redirect()->route('users.index')->with('success', "Pengguna {$validated['name']} (@{$validated['username']}) berhasil ditambahkan.");
    }

    /**
     * Show the form for editing the user.
     */
    public function edit(User $user): View
    {
        $students = Student::where('is_active', true)->orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();
        $currentUser = Auth::user();

        // Non-superadmin cannot edit superadmin
        if ($user->isSuperAdmin() && ! $currentUser->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat mengubah akun Super Admin.');
        }

        $roles = [
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_GURU => 'Guru Tahfidz',
            User::ROLE_WALI_KELAS => 'Wali Kelas',
            User::ROLE_KESANTRIAN => 'Kesantrian / Wali Asrama',
            User::ROLE_TU => 'Tata Usaha (TU)',
            User::ROLE_WALI_MURID => 'Wali Murid',
        ];

        if ($currentUser->isSuperAdmin()) {
            $roles = [User::ROLE_SUPER_ADMIN => 'Super Admin'] + $roles;
        }

        return view('users.edit', compact('user', 'students', 'classrooms', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        if ($user->isSuperAdmin() && ! $currentUser->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat mengubah akun Super Admin.');
        }

        $allowedRoles = $currentUser->assignableRoles();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => ['required', Rule::in($allowedRoles)],
            'student_id' => 'nullable|required_if:role,wali_murid|exists:students,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $classroomIds = $validated['classroom_ids'] ?? [];
        unset($validated['classroom_ids']);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($validated['role'] !== User::ROLE_WALI_MURID) {
            $validated['student_id'] = null;
        }

        $user->update($validated);

        $user->classrooms()->sync(
            in_array($user->role, User::CLASSROOM_SCOPED_ROLES, true) ? $classroomIds : []
        );

        return redirect()->route('users.index')->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->isSuperAdmin() && ! Auth::user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat menghapus akun Super Admin.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil dihapus.');
    }
}
