<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TerminologyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->superAdmin = User::where('role', User::ROLE_SUPER_ADMIN)->first();
    }

    public function test_terminology_defaults_match_existing_hardcoded_labels(): void
    {
        $this->assertSame('Santri', Institution::term('student'));
        $this->assertSame('Guru', Institution::term('teacher'));
        $this->assertSame('Kelas', Institution::term('class'));
    }

    public function test_current_institution_survives_a_serializing_cache_store(): void
    {
        config([
            'cache.default' => 'file',
            'cache.stores.file.path' => storage_path('framework/testing/cache'),
        ]);
        Cache::purge('file');
        Cache::store('file')->flush();

        // The second call reads back from the file store, which unserializes
        // without allowing model classes (cache.serializable_classes = false).
        Institution::term('student');
        $this->assertSame('Santri', Institution::term('student'));

        Cache::store('file')->flush();
    }

    public function test_admin_can_override_terminology(): void
    {
        $this->actingAs($this->superAdmin)->put(route('institution.update'), $this->validInstitutionPayload([
            'term_student' => 'Siswa',
            'term_teacher' => 'Musyrif',
            'term_class' => 'Halaqah',
        ]));

        $this->assertSame('Siswa', Institution::term('student'));
        $this->assertSame('Musyrif', Institution::term('teacher'));
        $this->assertSame('Halaqah', Institution::term('class'));
    }

    public function test_terminology_update_rejects_values_outside_the_allowed_pair(): void
    {
        $response = $this->actingAs($this->superAdmin)->put(route('institution.update'), $this->validInstitutionPayload([
            'term_student' => 'Murid', // not one of Santri/Siswa
        ]));

        $response->assertSessionHasErrors('term_student');
        $this->assertSame('Santri', Institution::term('student'));
    }

    public function test_guru_role_label_reflects_musyrif_terminology(): void
    {
        $guru = User::where('role', User::ROLE_GURU)->first();
        $this->assertSame('Guru Tahfidz', $guru->role_label);

        $this->actingAs($this->superAdmin)->put(route('institution.update'), $this->validInstitutionPayload([
            'term_teacher' => 'Musyrif',
        ]));

        $guru->refresh();
        $this->assertSame('Musyrif Tahfidz', $guru->role_label);
    }

    public function test_students_index_header_reflects_siswa_terminology(): void
    {
        $this->actingAs($this->superAdmin)->put(route('institution.update'), $this->validInstitutionPayload([
            'term_student' => 'Siswa',
        ]));

        $response = $this->actingAs($this->superAdmin)->get(route('students.index'));

        $response->assertSee('Master Data Siswa');
        $response->assertDontSee('Master Data Santri');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validInstitutionPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'PONDOK PESANTREN CONTOH',
            'city' => 'KOTA CONTOH',
            'director_name' => 'Ust. Fulan, S.Pd.',
            'director_title' => 'Direktur Pesantren',
            'accent_color' => '#059669',
            'term_student' => 'Santri',
            'term_teacher' => 'Guru',
            'term_class' => 'Kelas',
        ], $overrides);
    }
}
