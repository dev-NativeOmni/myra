<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassScheduleController extends Controller
{
    /**
     * Display the class schedule board.
     */
    public function index(): View
    {
        $daysOfWeek = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Ahad',
        ];

        $classrooms = Classroom::orderBy('name')->get();

        $scheduleBoard = [];
        foreach ($daysOfWeek as $dayNum => $dayName) {
            $activeClasses = $classrooms->filter(function ($c) use ($dayNum) {
                return in_array($dayNum, $c->tahfizh_days ?? [1, 2, 3, 4, 5, 6], true);
            });

            $scheduleBoard[$dayNum] = [
                'day_name' => $dayName,
                'active_classes' => $activeClasses,
            ];
        }

        return view('classrooms.schedules', compact('daysOfWeek', 'classrooms', 'scheduleBoard'));
    }

    /**
     * Update active tahfizh days for classrooms.
     */
    public function update(Request $request): RedirectResponse
    {
        $schedules = $request->input('schedules', []);
        $allClassrooms = Classroom::all();

        foreach ($allClassrooms as $classroom) {
            $days = isset($schedules[$classroom->id]) && is_array($schedules[$classroom->id])
                ? array_map('intval', $schedules[$classroom->id])
                : [];

            // Sort days ascending
            sort($days);

            $classroom->update([
                'tahfizh_days' => $days,
            ]);
        }

        return redirect()->route('class-schedules.index')
            ->with('success', 'Alhamdulillah, jadwal hari aktif halaqah kelas berhasil diperbarui.');
    }
}
