<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\CompanyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_encrypts_sensitive_company_profile_data(): void
    {
        $user = User::factory()->create();
        
        $profile = CompanyProfile::create([
            'user_id' => $user->id,
            'name' => 'Test Company',
            'address' => '123 Test Street',
            'siret' => '12345678901234',
            'email' => 'contact@test.com',
            'phone' => '0102030405',
        ]);

        // Verify data is encrypted in the database
        $raw = DB::table('company_profiles')->where('id', $profile->id)->first();
        
        $this->assertNotEquals('123 Test Street', $raw->address);
        $this->assertNotEquals('12345678901234', $raw->siret);
        $this->assertNotEquals('contact@test.com', $raw->email);
        $this->assertNotEquals('0102030405', $raw->phone);

        // Verify data is automatically decrypted when accessed through Eloquent
        $this->assertEquals('123 Test Street', $profile->address);
        $this->assertEquals('12345678901234', $profile->siret);
        $this->assertEquals('contact@test.com', $profile->email);
        $this->assertEquals('0102030405', $profile->phone);
    }

    public function test_validates_siret_format_during_profile_update(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Too short
        $response = $this->patch(route('settings.company.update'), [
            'name' => 'Test',
            'address' => 'Test',
            'siret' => '123',
        ]);
        $response->assertSessionHasErrors('siret');

        // Too long
        $response = $this->patch(route('settings.company.update'), [
            'name' => 'Test',
            'address' => 'Test',
            'siret' => '123456789012345678',
        ]);
        $response->assertSessionHasErrors('siret');

        // Valid
        $response = $this->patch(route('settings.company.update'), [
            'name' => 'Test',
            'address' => 'Test',
            'siret' => '12345678901234',
        ]);
        $response->assertSessionHasNoErrors();
    }

    public function test_allows_updating_email_and_phone_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->patch(route('settings.company.update'), [
            'name' => 'Updated Name',
            'address' => 'Updated Address',
            'siret' => '98765432109876',
            'email' => 'new@company.com',
            'phone' => '0607080910',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('company_profiles', [
            'user_id' => $user->id,
            'name' => 'Updated Name',
        ]);

        $profile = $user->refresh()->companyProfile;
        $this->assertEquals('new@company.com', $profile->email);
        $this->assertEquals('0607080910', $profile->phone);
    }
}
