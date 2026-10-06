<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\ModuleField;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlatformInstitutionController extends Controller
{
    /**
     * Display a listing of all institutions.
     */
    public function index(): View
    {
        $institutions = collect();

        try {
            $institutions = TenantContext::withoutScope(function () {
                return Institution::withCount(['users', 'classrooms', 'students', 'monthlyReports'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            });
        } catch (\Throwable $e) {
            try {
                $institutions = TenantContext::withoutScope(function () {
                    return Institution::orderBy('created_at', 'desc')->get();
                });
            } catch (\Throwable $e2) {
                $institutions = collect();
            }
        }

        $currentTenant = null;
        try {
            $currentTenant = TenantContext::getTenant();
        } catch (\Throwable $e) {
            // Graceful fallback
        }

        return view('platform.institutions.index', compact('institutions', 'currentTenant'));
    }

    /**
     * Store a newly created institution.
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
        ]);

        // Auto-generate token if not provided
        if (empty($validated['token'])) {
            $baseSlug = strtoupper(Str::slug($validated['name'], ''));
            $token = substr($baseSlug, 0, 8).'-'.strtoupper(Str::random(4));
            while (Institution::where('token', $token)->exists()) {
                $token = substr($baseSlug, 0, 8).'-'.strtoupper(Str::random(4));
            }
            $validated['token'] = $token;
        } else {
            $validated['token'] = strtoupper(trim($validated['token']));
        }

        $validated['accent_color'] = $validated['accent_color'] ?: '#059669';
        $validated['is_active'] = true;

        $institution = Institution::create($validated);

        // Seed default assessment fields for this institution
        TenantContext::setTenant($institution);
        ModuleField::seedDefaultFields();

        return redirect()->route('platform.institutions.index')
            ->with('success', "Lembaga baru '{$institution->name}' dengan token akses '{$institution->token}' berhasil dibuat.");
    }

    /**
     * Update the specified institution.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $institution = TenantContext::withoutScope(fn () => Institution::findOrFail($id));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token' => 'required|string|max:50|unique:institutions,token,'.$institution->id,
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

    /**
     * Toggle active status of an institution.
     */
    public function toggle($id): RedirectResponse
    {
        $institution = TenantContext::withoutScope(fn () => Institution::findOrFail($id));
        $institution->update(['is_active' => ! $institution->is_active]);

        $statusText = $institution->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('platform.institutions.index')
            ->with('success', "Status lembaga '{$institution->name}' berhasil {$statusText}.");
    }

    /**
     * Switch context / Impersonate institution.
     */
    public function switchTenant($id): RedirectResponse
    {
        $institution = TenantContext::withoutScope(fn () => Institution::findOrFail($id));
        TenantContext::setTenant($institution);

        return redirect()->route('dashboard')
            ->with('success', "Berhasil beralih ke konteks lembaga: {$institution->name} (Token: {$institution->token}).");
    }
}
