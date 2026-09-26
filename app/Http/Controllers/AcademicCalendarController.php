<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicCalendarController extends Controller
{
    /**
     * Display the monthly academic calendar.
     */
    public function index(Request $request): View
    {
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('m'));

        if ($month < 1 || $month > 12) {
            $month = (int) date('m');
        }

        $currentDate = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $currentDate->daysInMonth;
        $firstDayOfWeek = (int) $currentDate->copy()->startOfMonth()->dayOfWeekIso; // 1 = Senin, 7 = Ahad

        $prevMonthDate = $currentDate->copy()->subMonth();
        $prevYear = (int) $prevMonthDate->format('Y');
        $prevMonth = (int) $prevMonthDate->format('m');

        $nextMonthDate = $currentDate->copy()->addMonth();
        $nextYear = (int) $nextMonthDate->format('Y');
        $nextMonth = (int) $nextMonthDate->format('m');

        $classrooms = Classroom::orderBy('name')->get();
        $holidays = Setting::getNationalHolidays($year);

        $classHolidaysRaw = Setting::get("class_holidays_{$year}");
        $classHolidays = $classHolidaysRaw ? json_decode($classHolidaysRaw, true) : [];

        // Build calendar cells array (leading blanks + month days)
        $calendarDays = [];

        // Padding before day 1
        for ($i = 1; $i < $firstDayOfWeek; $i++) {
            $calendarDays[] = [
                'type' => 'padding',
            ];
        }

        // Days of current month
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dayOfWeekIso = (int) date('N', strtotime($dateStr));
            $isNationalHoliday = in_array($dateStr, $holidays, true);
            $classHolidayIds = $classHolidays[$dateStr] ?? [];

            $calendarDays[] = [
                'type' => 'day',
                'day' => $day,
                'date' => $dateStr,
                'day_of_week' => $dayOfWeekIso,
                'is_national_holiday' => $isNationalHoliday,
                'class_holidays' => $classHolidayIds,
                'is_weekend' => ($dayOfWeekIso === 7),
            ];
        }

        return view('settings.calendar', compact(
            'year',
            'month',
            'prevYear',
            'prevMonth',
            'nextYear',
            'nextMonth',
            'calendarDays',
            'classrooms',
            'holidays',
            'classHolidays'
        ));
    }

    /**
     * Update academic calendar holidays.
     */
    public function update(Request $request): RedirectResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('m'));

        $holidays = $request->input('holidays', []);
        if (is_string($holidays)) {
            $holidays = json_decode($holidays, true) ?: [];
        }
        $holidays = array_values(array_unique(array_filter((array) $holidays)));

        $classHolidays = $request->input('class_holidays', []);
        if (is_string($classHolidays)) {
            $classHolidays = json_decode($classHolidays, true) ?: [];
        }
        if (! is_array($classHolidays)) {
            $classHolidays = [];
        }

        // Clean class holidays
        $cleanedClassHolidays = [];
        foreach ($classHolidays as $dateStr => $classIds) {
            if (! empty($classIds) && is_array($classIds)) {
                $cleanedClassHolidays[$dateStr] = array_values(array_unique(array_map('intval', $classIds)));
            }
        }

        Setting::set("national_holidays_{$year}", json_encode($holidays));
        Setting::set("class_holidays_{$year}", json_encode($cleanedClassHolidays));

        return redirect()->route('academic-calendar.index', ['year' => $year, 'month' => $month])
            ->with('success', 'Alhamdulillah, kalender akademik dan konfigurasi hari libur berhasil disimpan.');
    }
}
