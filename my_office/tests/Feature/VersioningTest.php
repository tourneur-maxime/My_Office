<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_shared_props_contain_version_information(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->has('app_version')
            ->has('facturx_version')
        );
    }

    public function test_versions_match_config_values(): void
    {
        $user = User::factory()->create();
        
        config(['versions.app_version' => '2.3.4']);
        config(['versions.facturx_spec_version' => '1.0.06']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('app_version', '2.3.4')
            ->where('facturx_version', '1.0.06')
        );
    }
}
