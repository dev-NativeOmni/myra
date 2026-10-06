<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->first();
    }

    public function test_creating_a_classroom_scoped_user_assigns_selected_classrooms(): void
    {
        $classrooms = Classroom::take(2)->pluck('id');

        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Ustadz Baru',
            'username' => 'ustadz_baru',
            'email' => 'ustadz.baru@example.com',
            'password' => 'password123',
            'role' => User::ROLE_GURU,
            'classroom_ids' => $classrooms->all(),
        ]);

        $response->assertRedirect(route('users.index'));

        $user = User::where('username', 'ustadz_baru')->firstOrFail();
        $this->assertSame($classrooms->sort()->values()->all(), $user->classrooms->pluck('id')->sort()->values()->all());
    }

    public function test_creating_a_lembaga_wide_user_ignores_submitted_classroom_ids(): void
    {
        $classrooms = Classroom::take(2)->pluck('id');

        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Staf TU Baru',
            'username' => 'tu_baru',
            'email' => 'tu.baru@example.com',
            'password' => 'password123',
            'role' => User::ROLE_TU,
            'classroom_ids' => $classrooms->all(),
        ]);

        $user = User::where('username', 'tu_baru')->firstOrFail();
        $this->assertTrue($user->classrooms->isEmpty());
    }

    public function test_updating_a_users_role_and_classrooms_resyncs_assignments(): void
    {
        $allClassrooms = Classroom::pluck('id');
        $guru = User::where('role', User::ROLE_GURU)->first();
        $guru->classrooms()->sync([$allClassrooms->first()]);

        $newClassroomIds = $allClassrooms->skip(1)->take(2)->all();

        $this->actingAs($this->admin)->put(route('users.update', $guru->id), [
            'name' => $guru->name,
            'username' => $guru->username,
            'email' => $guru->email,
            'role' => User::ROLE_GURU,
            'classroom_ids' => $newClassroomIds,
        ]);

        $guru->refresh();
        $this->assertSame(collect($newClassroomIds)->sort()->values()->all(), $guru->classrooms->pluck('id')->sort()->values()->all());
    }

    public function test_changing_role_away_from_classroom_scoped_clears_assignments(): void
    {
        $guru = User::where('role', User::ROLE_GURU)->first();
        $guru->classrooms()->sync(Classroom::pluck('id'));

        $this->actingAs($this->admin)->put(route('users.update', $guru->id), [
            'name' => $guru->name,
            'username' => $guru->username,
            'email' => $guru->email,
            'role' => User::ROLE_TU,
        ]);

        $guru->refresh();
        $this->assertTrue($guru->classrooms->isEmpty());
    }

    public function test_creating_a_user_rejects_password_shorter_than_eight_characters(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Wali Baru',
            'username' => 'wali_baru',
            'password' => 'pass123',
            'role' => User::ROLE_TU,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['username' => 'wali_baru']);
    }

    public function test_updating_a_user_rejects_password_shorter_than_eight_characters(): void
    {
        $user = User::where('role', User::ROLE_TU)->firstOrFail();
        $originalHash = $user->password;

        $response = $this->actingAs($this->admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'password' => 'pass123',
            'role' => $user->role,
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_creating_a_parent_links_all_selected_children(): void
    {
        $children = Student::take(2)->pluck('id')->sort()->values();

        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Wali Dua Anak',
            'username' => 'wali_dua_anak',
            'password' => 'password123',
            'role' => User::ROLE_WALI_MURID,
            'student_ids' => $children->all(),
        ]);

        $parent = User::where('username', 'wali_dua_anak')->firstOrFail();
        $this->assertSame($children->all(), $parent->children->pluck('id')->sort()->values()->all());
    }

    public function test_creating_a_parent_without_children_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Wali Tanpa Anak',
            'username' => 'wali_tanpa_anak',
            'password' => 'password123',
            'role' => User::ROLE_WALI_MURID,
        ]);

        $response->assertSessionHasErrors('student_ids');
        $this->assertDatabaseMissing('users', ['username' => 'wali_tanpa_anak']);
    }

    public function test_changing_a_parent_to_staff_role_unlinks_children(): void
    {
        $parent = User::where('role', User::ROLE_WALI_MURID)->has('children')->firstOrFail();

        $this->actingAs($this->admin)->put(route('users.update', $parent), [
            'name' => $parent->name,
            'username' => $parent->username,
            'role' => User::ROLE_TU,
        ]);

        $this->assertTrue($parent->fresh()->children->isEmpty());
    }

    public function test_institution_admin_cannot_create_a_super_admin(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Calon Super Admin',
            'username' => 'calon_super',
            'password' => 'password123',
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['username' => 'calon_super']);
    }
}
