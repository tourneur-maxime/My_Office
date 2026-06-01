<?php

namespace Tests\Unit\Models;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_fields_are_encrypted()
    {
        $user = User::factory()->create();
        $profile = CompanyProfile::create([
            'user_id' => $user->id,
            'name' => 'Test Corp',
            'iban' => 'FR7630006000011234567890189',
            'bic' => 'ABCDEFGH',
        ]);

        // Access via model (should be decrypted)
        $this->assertEquals('FR7630006000011234567890189', $profile->iban);
        $this->assertEquals('ABCDEFGH', $profile->bic);

        // Access via DB (should be encrypted)
        $rawProfile = DB::table('company_profiles')->where('id', $profile->id)->first();
        
        $this->assertNotEquals('FR7630006000011234567890189', $rawProfile->iban);
        $this->assertNotEquals('ABCDEFGH', $rawProfile->bic);
        $this->assertNotEmpty($rawProfile->iban);
        $this->assertNotEmpty($rawProfile->bic);
    }
}