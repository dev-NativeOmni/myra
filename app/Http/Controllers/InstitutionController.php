<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    /**
     * Show the form for editing the institution profile.
     */
    public function edit(): View
    {
        $institution = Institution::first() ?? new Institution([
            'name' => 'PONDOK PESANTREN CONTOH',
            'city' => 'KOTA CONTOH',
            'director_name' => 'Ust. Fulan, S.Pd.',
            'director_title' => 'Direktur Pesantren',
            'accent_color' => '#059669',
            'term_student' => Institution::DEFAULT_TERMS['student'],
            'term_teacher' => Institution::DEFAULT_TERMS['teacher'],
            'term_class' => Institution::DEFAULT_TERMS['class'],
        ]);

        return view('institution.edit', compact('institution'));
    }

    /**
     * Update the institution profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sub_title' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'director_name' => 'required|string|max:255',
            'director_title' => 'required|string|max:100',
            'accent_color' => 'required|string|max:20',
            'term_student' => 'required|in:Santri,Siswa',
            'term_teacher' => 'required|in:Guru,Musyrif',
            'term_class' => 'required|in:Kelas,Halaqah',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'stamp' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'signature' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $institution = Institution::first() ?? new Institution;

        if ($request->hasFile('logo')) {
            if ($institution->logo_path) {
                Storage::disk('public')->delete($institution->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('institutions', 'public');
        }

        if ($request->hasFile('stamp')) {
            if ($institution->stamp_path) {
                Storage::disk('public')->delete($institution->stamp_path);
            }
            $validated['stamp_path'] = $request->file('stamp')->store('institutions', 'public');
        }

        if ($request->hasFile('signature')) {
            if ($institution->signature_path) {
                Storage::disk('public')->delete($institution->signature_path);
            }
            $validated['signature_path'] = $request->file('signature')->store('institutions', 'public');
        }

        $institution->fill($validated);
        $institution->save();

        return redirect()->route('institution.edit')->with('success', 'Profil lembaga dan branding berhasil diperbarui.');
    }
}
