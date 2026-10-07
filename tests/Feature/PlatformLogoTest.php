<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformLogoTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $institutionAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SampleDataSeeder::class);

        $this->superAdmin = User::withoutGlobalScopes()->where('role', User::ROLE_SUPER_ADMIN)->firstOrFail();
        $this->institutionAdmin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    public function test_super_admin_can_upload_and_delete_platform_logo(): void
    {
        Storage::fake('public');

        // 1. Super Admin uploads platform logo
        $logoFile = UploadedFile::fake()->image('myra-logo.png');

        $response = $this->actingAs($this->superAdmin)->post(route('platform.settings.logo'), [
            'logo' => $logoFile,
        ]);

        $response->assertRedirect(route('platform.institutions.index'));
        $response->assertSessionHas('success');

        $this->assertNotNull(Setting::getGlobal('platform_logo_path'));
        Storage::disk('public')->assertExists(Setting::getGlobal('platform_logo_path'));

        // 2. Gateway and Login pages display the platform logo
        auth()->logout();

        $gatewayResponse = $this->get(route('gateway.index'));
        $gatewayResponse->assertOk();
        $gatewayResponse->assertSee(Setting::platformLogoUrl());

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertOk();
        $loginResponse->assertSee(Setting::platformLogoUrl());

        // 3. Super admin can delete the platform logo
        $deleteResponse = $this->actingAs($this->superAdmin)->delete(route('platform.settings.logo.delete'));
        $deleteResponse->assertRedirect(route('platform.institutions.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertNull(Setting::getGlobal('platform_logo_path'));
    }

    public function test_institution_admin_cannot_upload_platform_logo(): void
    {
        Storage::fake('public');
        $logoFile = UploadedFile::fake()->image('myra-logo.png');

        $response = $this->actingAs($this->institutionAdmin)->post(route('platform.settings.logo'), [
            'logo' => $logoFile,
        ]);

        $response->assertForbidden();
    }

    public function test_superadmin_view_and_top_navbar_render_institution_logo(): void
    {
        $institution = $this->institutionAdmin->institution;
        $institution->update(['logo_path' => 'institutions/sample-logo.png']);

        // 1. Super Admin view displays the institution logo in the card
        $platformResponse = $this->actingAs($this->superAdmin)->get(route('platform.institutions.index'));
        $platformResponse->assertOk();
        $platformResponse->assertSee($institution->imageUrl('logo_path'));

        // 2. Institution Admin header navbar displays the institution logo
        $adminResponse = $this->actingAs($this->institutionAdmin)->get(route('dashboard'));
        $adminResponse->assertOk();
        $adminResponse->assertSee($institution->imageUrl('logo_path'));
    }
}
