<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    /**
     * Show the form for editing the institution profile.
     */
    public function edit(): View
    {
        $institution = Institution::current();

        return view('institution.edit', compact('institution'));
    }

    /**
     * Update the institution profile.
     */
    public function update(Request $request): RedirectResponse
    {
        $institution = Institution::current();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'token' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('institutions', 'token')->ignore($institution->id)],
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
            'logo' => 'nullable|image|mimes:jpg,jpeg|max:2048',
            'stamp' => 'nullable|image|mimes:jpg,jpeg|max:2048',
            'signature' => 'nullable|image|mimes:jpg,jpeg|max:2048',
        ]);

        $validated['token'] = strtoupper($validated['token']);

        $disk = Storage::disk(config('filesystems.uploads'));

        foreach (['logo', 'stamp', 'signature'] as $field) {
            if ($request->hasFile($field)) {
                if ($institution->{"{$field}_path"}) {
                    $disk->delete($institution->{"{$field}_path"});
                }
                $validated["{$field}_path"] = $request->file($field)->store('institutions', config('filesystems.uploads'));
            }
        }

        $institution->fill($validated);
        $institution->save();

        return redirect()->route('institution.edit')->with('success', 'Profil lembaga dan branding berhasil diperbarui.');
    }
}
