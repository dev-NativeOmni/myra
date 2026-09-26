<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): View
    {
        $query = User::with(['student', 'classrooms'])->latest();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('username', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
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
        $allowedRoles = [
            User::ROLE_ADMIN,
            User::ROLE_GURU,
            User::ROLE_WALI_KELAS,
            User::ROLE_KESANTRIAN,
            User::ROLE_TU,
            User::ROLE_WALI_MURID,
        ];

        if (Auth::user()->isSuperAdmin()) {
            $allowedRoles[] = User::ROLE_SUPER_ADMIN;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
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

        $allowedRoles = [
            User::ROLE_ADMIN,
            User::ROLE_GURU,
            User::ROLE_WALI_KELAS,
            User::ROLE_KESANTRIAN,
            User::ROLE_TU,
            User::ROLE_WALI_MURID,
        ];

        if ($currentUser->isSuperAdmin()) {
            $allowedRoles[] = User::ROLE_SUPER_ADMIN;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
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
