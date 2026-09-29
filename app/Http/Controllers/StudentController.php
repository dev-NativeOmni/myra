<?php

namespace App\Http\Controllers;

use App\Exports\StudentImportTemplateExport;
use App\Exports\StudentsExport;
use App\Imports\StudentsImport;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    /**
     * Display a listing of the students.
     */
    public function index(Request $request): View
    {
        $classrooms = Classroom::orderBy('name')->get();
        $query = Student::with('classroom')->latest();

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('nis', "%{$search}%");
            });
        }

        $students = $query->paginate(15)->withQueryString();

        return view('students.index', compact('students', 'classrooms'));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create(): View
    {
        $classrooms = Classroom::orderBy('name')->get();

        return view('students.create', compact('classrooms'));
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:students,nis',
            'name' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'gender' => 'required|in:L,P',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Student::create($validated);

        return redirect()->route('students.index')->with('success', 'Data santri berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the student.
     */
    public function edit(Student $student): View
    {
        $classrooms = Classroom::orderBy('name')->get();

        return view('students.edit', compact('student', 'classrooms'));
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:students,nis,'.$student->id,
            'name' => 'required|string|max:255',
            'classroom_id' => 'required|exists:classrooms,id',
            'gender' => 'required|in:L,P',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $student->update($validated);

        return redirect()->route('students.index')->with('success', 'Data santri berhasil diperbarui.');
    }

    /**
     * Display the specified student profile, progress trends, and report history.
     */
    public function show(Request $request, Student $student): View
    {
        abort_unless($request->user()->canViewClassroom($student->classroom_id), 403, 'Akses Ditolak: Anda tidak bertanggung jawab atas kelas santri ini.');

        $student->load(['classroom', 'monthlyReports.record', 'parents', 'tahfidzJournals']);
        $trends = $student->getProgressTrends();
        $reports = $student->monthlyReports()->with('record')->orderBy('report_date', 'desc')->get();
        $latestReport = $reports->first();

        return view('students.show', compact('student', 'trends', 'reports', 'latestReport'));
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('students.index')->with('success', 'Data santri berhasil dihapus.');
    }

    /**
     * Export students matching the current filters to an Excel file.
     */
    public function export(Request $request): BinaryFileResponse
    {
        $query = Student::with('classroom')->orderBy('name');

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('nis', "%{$search}%");
            });
        }

        return Excel::download(new StudentsExport($query->get()), 'Data_Santri.xlsx');
    }

    /**
     * Download a blank Excel template for importing students.
     */
    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new StudentImportTemplateExport, 'Template_Import_Santri.xlsx');
    }

    /**
     * Import students from an uploaded Excel/CSV file.
     * Existing students are matched and updated by NIS; the rest are created.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new StudentsImport;

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->withErrors(["Gagal membaca berkas: {$e->getMessage()}. Pastikan formatnya sesuai template."]);
        }

        $errors = $import->allErrorMessages();

        if ($import->created === 0 && $import->updated === 0) {
            return back()->withErrors($errors ?: ['File tidak berisi data santri yang valid untuk diimpor.']);
        }

        $summary = "Impor selesai: {$import->created} santri baru ditambahkan, {$import->updated} santri diperbarui.";

        $redirect = redirect()->route('students.index')->with('success', $summary);

        return $errors ? $redirect->withErrors($errors) : $redirect;
    }
}
