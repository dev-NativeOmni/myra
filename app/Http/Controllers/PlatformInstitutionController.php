<?php

namespace App\Http\Controllers;

use App\Models\ImpersonationLog;
use App\Models\Institution;
use App\Models\ModuleField;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform overview for the single Super Admin: monitors every institution and
 * manages institution records, but never edits an institution's own data. Support
 * inside an institution goes through ImpersonationController.
 */
class PlatformInstitutionController extends Controller
{
    public function index(): View
    {
        $institutions = TenantContext::withoutScope(fn () => Institution::query()
            ->withCount(['users', 'classrooms', 'students', 'monthlyReports'])
            ->withMax('monthlyReports', 'updated_at')
            ->with('admins')
            ->orderByDesc('created_at')
            ->get());

        $supportSessions = ImpersonationLog::with(['impersonatedUser', 'institution'])
            ->latest('started_at')
            ->take(10)
            ->get();

        return view('platform.institutions.index', compact('institutions', 'supportSessions'));
    }

    /**
     * Create an institution together with its first Admin account, so it can be
     * used (and supported) right away.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'nullable|string|max:50|unique:institutions,token',
            'city' => 'required|string|max:100',
            'director_name' => 'required|string|max:255',
            'director_title' => 'required|string|max:100',
            'accent_color' => 'nullable|string|max:20',
            'admin_name' => 'required|string|max:255',
            'admin_username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')],
            'admin_password' => 'required|string|min:8',
        ]);

        $institution = DB::transaction(function () use ($validated) {
            $institution = Institution::create([
                'name' => $validated['name'],
                'token' => $validated['token'] ? strtoupper(trim($validated['token'])) : $this->generateToken($validated['name']),
                'city' => $validated['city'],
                'director_name' => $validated['director_name'],
                'director_title' => $validated['director_title'],
                'accent_color' => $validated['accent_color'] ?: '#059669',
                'is_active' => true,
            ]);

            User::create([
                'institution_id' => $institution->id,
                'name' => $validated['admin_name'],
                'username' => $validated['admin_username'],
                'password' => $validated['admin_password'],
                'role' => User::ROLE_ADMIN,
            ]);

            // Default assessment fields are created for the active tenant.
            TenantContext::setTenant($institution);
            try {
                ModuleField::seedDefaultFields();
            } finally {
                TenantContext::clear();
            }

            return $institution;
        });

        return redirect()->route('platform.institutions.index')
            ->with('success', "Lembaga '{$institution->name}' (token {$institution->token}) dibuat dengan admin @{$validated['admin_username']}.");
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token' => ['required', 'string', 'max:50', Rule::unique('institutions', 'token')->ignore($institution->id)],
            'city' => 'required|string|max:100',
            'director_name' => 'required|string|max:255',
            'director_title' => 'required|string|max:100',
            'accent_color' => 'required|string|max:20',
        ]);

        $validated['token'] = strtoupper(trim($validated['token']));
        $institution->update($validated);

        return redirect()->route('platform.institutions.index')
            ->with('success', "Data lembaga '{$institution->name}' berhasil diperbarui.");
    }

    public function toggle(Institution $institution): RedirectResponse
    {
        $institution->update(['is_active' => ! $institution->is_active]);

        $statusText = $institution->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('platform.institutions.index')
            ->with('success', "Status lembaga '{$institution->name}' berhasil {$statusText}.");
    }

    private function generateToken(string $name): string
    {
        $prefix = substr(strtoupper(Str::slug($name, '')), 0, 8);

        do {
            $token = $prefix.'-'.strtoupper(Str::random(4));
        } while (Institution::where('token', $token)->exists());

        return $token;
    }
}
