<?php

namespace App\Http\Controllers;

use App\Models\ImpersonationLog;
use App\Models\Institution;
use App\Models\ModuleField;
use App\Models\Setting;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform overview for the single Super Admin: monitors every institution,
 * registers new ones (with their first Admin) and can deactivate them. Everything
 * about an institution itself (profile, branding, token, data) is managed by its
 * own Admin; support inside an institution goes through ImpersonationController.
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

        $platformLogoUrl = Setting::platformLogoUrl();

        return view('platform.institutions.index', compact('institutions', 'supportSessions', 'platformLogoUrl'));
    }

    /**
     * Update the generic Myra platform logo.
     */
    public function updateLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp,svg|max:2048',
        ]);

        $disk = Storage::disk(config('filesystems.uploads'));
        $oldPath = Setting::getGlobal('platform_logo_path');
        if ($oldPath && $disk->exists($oldPath)) {
            $disk->delete($oldPath);
        }

        $path = $request->file('logo')->store('platform', config('filesystems.uploads'));
        Setting::setGlobal('platform_logo_path', $path);

        return redirect()->route('platform.institutions.index')
            ->with('success', 'Logo umum platform Myra berhasil diperbarui.');
    }

    /**
     * Remove the custom platform logo and reset to default.
     */
    public function deleteLogo(): RedirectResponse
    {
        $disk = Storage::disk(config('filesystems.uploads'));
        $oldPath = Setting::getGlobal('platform_logo_path');
        if ($oldPath && $disk->exists($oldPath)) {
            $disk->delete($oldPath);
        }

        Setting::setGlobal('platform_logo_path', null);

        return redirect()->route('platform.institutions.index')
            ->with('success', 'Logo umum platform Myra berhasil dihapus (kembali ke default).');
    }

    /**
     * Create an institution together with its first Admin account, so it can be
     * used (and supported) right away.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'nullable|string|max:50|alpha_dash|unique:institutions,token',
            'city' => 'required|string|max:100',
            'admin_name' => 'required|string|max:255',
            'admin_username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users', 'username')],
            'admin_password' => 'required|string|min:8',
        ]);

        $institution = DB::transaction(function () use ($validated) {
            $institution = Institution::create([
                'name' => $validated['name'],
                'token' => $validated['token'] ? strtoupper(trim($validated['token'])) : $this->generateToken($validated['name']),
                'city' => $validated['city'],
                // Completed by the institution's own Admin in Profil Lembaga.
                'director_name' => 'Belum diatur',
                'director_title' => 'Pimpinan Lembaga',
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

    public function toggle(Institution $institution): RedirectResponse
    {
        $institution->update(['is_active' => ! $institution->is_active]);

        $statusText = $institution->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('platform.institutions.index')
            ->with('success', "Status lembaga '{$institution->name}' berhasil {$statusText}.");
    }

    /**
     * Seed dummy demo accounts and sample data for the selected institution.
     */
    public function seedDummy(Institution $institution): RedirectResponse
    {
        $institution->seedDemoData();

        return redirect()->route('platform.institutions.index')
            ->with('success', "Akun demo & data santri contoh untuk lembaga '{$institution->name}' berhasil dibuat/diperbarui.");
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
