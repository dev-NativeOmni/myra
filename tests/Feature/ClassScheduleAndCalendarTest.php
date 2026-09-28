<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassScheduleAndCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $guru;

    protected Classroom $class1;

    protected Classroom $class2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
        $this->guru = User::where('role', User::ROLE_GURU)->first();
        $this->class1 = Classroom::where('name', '1')->first();
        $this->class2 = Classroom::where('name', '2')->first();
    }

    public function test_admin_can_view_and_update_class_schedules(): void
    {
        // 1. View schedule board
        $response = $this->actingAs($this->admin)->get(route('class-schedules.index'));
        $response->assertStatus(200);
        $response->assertSee('Jadwal Hari Aktif Halaqah Kelas');

        // 2. Update Class 1 to only meet on Monday (1) and Wednesday (3)
        $updateResponse = $this->actingAs($this->admin)->post(route('class-schedules.update'), [
            'schedules' => [
                $this->class1->id => [1, 3],
                $this->class2->id => [1, 2, 3, 4, 5, 6],
            ],
        ]);

        $updateResponse->assertRedirect(route('class-schedules.index'));
        $updateResponse->assertSessionHas('success');

        $this->assertEquals([1, 3], $this->class1->fresh()->tahfizh_days);
        $this->assertEquals([1, 2, 3, 4, 5, 6], $this->class2->fresh()->tahfizh_days);
    }

    public function test_admin_can_view_and_update_academic_calendar(): void
    {
        // 1. View calendar
        $response = $this->actingAs($this->admin)->get(route('academic-calendar.index', ['year' => 2026, 'month' => 8]));
        $response->assertStatus(200);
        $response->assertSee('Kalender Akademik');
        $response->assertSee('Agustus 2026');

        // 2. Set National Holiday and Class-specific Holiday
        $updateResponse = $this->actingAs($this->admin)->post(route('academic-calendar.update'), [
            'year' => 2026,
            'month' => 8,
            'holidays' => ['2026-08-17'], // Hari Kemerdekaan RI
            'class_holidays' => [
                '2026-08-19' => [$this->class1->id], // Khusus Kelas 1 libur
            ],
        ]);

        $updateResponse->assertRedirect(route('academic-calendar.index', ['year' => 2026, 'month' => 8]));
        $updateResponse->assertSessionHas('success');

        $savedHolidays = json_decode(Setting::get('national_holidays_2026'), true);
        $savedClassHolidays = json_decode(Setting::get('class_holidays_2026'), true);

        $this->assertContains('2026-08-17', $savedHolidays);
        $this->assertEquals([$this->class1->id], $savedClassHolidays['2026-08-19']);
    }

    public function test_spreadsheet_matrix_skips_holidays_and_respects_class_schedule(): void
    {
        // 1. Set Class 1 active days: Senin (1), Selasa (2), Rabu (3)
        $this->class1->update(['tahfizh_days' => [1, 2, 3]]);

        // 2. Set National Holiday on 2026-08-17 (Senin) and Class 1 holiday on 2026-08-18 (Selasa)
        Setting::set('national_holidays_2026', json_encode(['2026-08-17']));
        Setting::set('class_holidays_2026', json_encode(['2026-08-18' => [$this->class1->id]]));

        // 3. View spreadsheet for Class 1 in August 2026
        $response = $this->actingAs($this->guru)->get(route('tahfidz-journals.spreadsheet', [
            'classroom_id' => $this->class1->id,
            'month' => '2026-08',
        ]));

        $response->assertStatus(200);

        // 2026-08-17 (National holiday) and 2026-08-18 (Class holiday) must NOT be present in dates
        $dates = $response->viewData('dates');
        $this->assertNotContains('2026-08-17', $dates);
        $this->assertNotContains('2026-08-18', $dates);

        // 2026-08-19 (Rabu) is active and not a holiday, so it MUST be present
        $this->assertContains('2026-08-19', $dates);

        // 2026-08-20 (Kamis) is not in tahfizh_days [1, 2, 3], so it must NOT be present
        $this->assertNotContains('2026-08-20', $dates);
    }

    public function test_saturday_is_active_school_day_and_only_sunday_is_weekend(): void
    {
        // 1. Check default classroom tahfizh_days is [1, 2, 3, 4, 5, 6]
        $this->assertEquals([1, 2, 3, 4, 5, 6], $this->class1->tahfizh_days);

        // 2. View August 2026 calendar: 2026-08-01 is Saturday (ISO day 6), 2026-08-02 is Sunday (ISO day 7)
        $response = $this->actingAs($this->admin)->get(route('academic-calendar.index', ['year' => 2026, 'month' => 8]));
        $calendarDays = $response->viewData('calendarDays');

        $day1 = collect($calendarDays)->firstWhere('date', '2026-08-01');
        $this->assertNotNull($day1);
        $this->assertEquals(6, $day1['day_of_week']);
        $this->assertFalse($day1['is_weekend'], 'Saturday must not be marked as weekend');

        $day2 = collect($calendarDays)->firstWhere('date', '2026-08-02');
        $this->assertNotNull($day2);
        $this->assertEquals(7, $day2['day_of_week']);
        $this->assertTrue($day2['is_weekend'], 'Sunday must be marked as weekend');

        // 3. Check spreadsheet for August 2026 includes Saturdays (e.g. 2026-08-01) but excludes Sundays (e.g. 2026-08-02)
        $sheetResponse = $this->actingAs($this->guru)->get(route('tahfidz-journals.spreadsheet', [
            'classroom_id' => $this->class1->id,
            'month' => '2026-08',
        ]));
        $sheetResponse->assertStatus(200);
        $dates = $sheetResponse->viewData('dates');

        $this->assertContains('2026-08-01', $dates, 'Saturday 2026-08-01 must be an active date in spreadsheet');
        $this->assertNotContains('2026-08-02', $dates, 'Sunday 2026-08-02 must not be in spreadsheet');
    }
}
