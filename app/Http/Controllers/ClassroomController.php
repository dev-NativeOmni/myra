<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    /**
     * Display a listing of the classrooms.
     */
    public function index(): View
    {
        $classrooms = Classroom::withCount('students')->latest()->get();

        return view('classrooms.index', compact('classrooms'));
    }

    /**
     * Store a newly created classroom.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        Classroom::create($validated);

        return redirect()->route('classrooms.index')->with('success', 'Kelas baru berhasil ditambahkan.');
    }

    /**
     * Update the specified classroom.
     */
    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $classroom->update($validated);

        return redirect()->route('classrooms.index')->with('success', 'Nama kelas berhasil diperbarui.');
    }

    /**
     * Remove the specified classroom.
     */
    public function destroy(Classroom $classroom): RedirectResponse
    {
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', 'Kelas berhasil dihapus.');
    }
}
