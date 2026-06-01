<?php

namespace Tests\Browser;

use App\Models\CompanyProfile;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class BrandingSettingsTest extends DuskTestCase
{
    /**
     * Test real-time preview updates when changing logo size and position.
     *
     * @return void
     */
    public function testRealtimePreviewUpdates()
    {
        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'logo_path' => 'logos/test-logo.webp', // Assume a logo is already uploaded
            'logo_size' => 100,
            'logo_position' => 'left',
        ]);

        // Create a dummy logo file
        \Illuminate\Support\Facades\Storage::disk('public')->put('logos/test-logo.webp', 'dummy-logo-content');

        $this->browse(function (Browser $browser) use ($user, $companyProfile) {
            $browser->loginAs($user)
                    ->visit(route('settings.branding'))
                    ->waitFor('@document-preview', 5) // Wait for the preview component to load
                    ->assertPresent('@document-preview img') // Ensure logo is visible
                    ->assertSeeIn('@document-preview', strtoupper($companyProfile->name)) // Ensure company name is in preview
                    ->waitFor('div[dusk="logo-adjustments-div"]', 5)

                    // Test logo size slider
                    ->scrollIntoView('input[type="range"][id="logo_size"]');
            
            $browser->script("document.getElementById('logo_size').value = 200;");
            $browser->script("document.getElementById('logo_size').dispatchEvent(new Event('input'));");
            $browser->script("document.getElementById('logo_size').dispatchEvent(new Event('change'));");

            $browser->pause(500) // Give Vue time to react
                    ->assertAttributeContains('@document-preview img', 'style', 'width: 200px') // Check inline style update

                    // Test logo position buttons
                    ->waitFor('button[dusk="logo-position-center-button"]', 5)
                    ->click('button[dusk="logo-position-center-button"]') // Click 'Center' button
                    ->assertAttributeContains('@document-preview div.mb-8', 'style', 'text-align: center') // Check inline style update for container
                    ->pause(500)

                    ->click('button[dusk="logo-position-right-button"]') // Click 'Right' button
                    ->assertAttributeContains('@document-preview div.mb-8', 'style', 'text-align: right') // Check inline style update for container
                    ->pause(500)
                    
                    ->click('button[dusk="logo-position-left-button"]') // Click 'Left' button (back to default)
                    ->assertAttributeContains('@document-preview div.mb-8', 'style', 'text-align: left') // Check inline style update for container
                    ->pause(500);
        });
        // Clean up the dummy logo file
        \Illuminate\Support\Facades\Storage::disk('public')->delete('logos/test-logo.webp');
    }

    /**
     * Test that colors and fonts can be changed and persisted.
     */
    public function testColorAndFontPersistence()
    {
        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'font_family' => 'sans-serif',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(route('settings.branding'))
                    ->waitFor('@document-preview', 5)
                    
                    // Change Primary Color via text input (it's bound to the same model as color picker)
                    ->type('@primary-color-text', '#ff0000')
                    ->pause(500)
                    // Verify preview update (Accent color)
                    ->assertAttributeContains('@document-preview h3', 'style', 'color: rgb(255, 0, 0)')

                    // Change Secondary Color
                    ->type('@secondary-color-text', '#00ff00')
                    ->pause(500)
                    // Verify preview update (Border bottom color)
                    ->assertAttributeContains('@document-preview table thead tr', 'style', 'border-bottom-color: rgb(0, 255, 0)')

                    // Change Font
                    ->select('@font-family-select', 'serif')
                    ->pause(500)
                    // Verify preview update
                    ->assertAttributeContains('@document-preview', 'style', 'font-family: serif')

                    // Save settings
                    ->click('@save-branding-button')
                    ->waitForText('Parametres de marque mis a jour.')

                    // Verify persistence by reloading or checking DB
                    ->assertInputValue('@primary-color-text', '#ff0000')
                    ->assertSelected('@font-family-select', 'serif');
        });
    }

    /**
     * Test saving current branding as a named template.
     */
    public function testSaveAsTemplate()
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(route('settings.branding'))
                    ->waitFor('@document-preview', 5)
                    
                    // Modify some settings
                    ->type('@primary-color-text', '#123456')
                    ->select('@font-family-select', 'monospace')
                    
                    // Click "Sauvegarder comme modèle"
                    ->waitFor('button[dusk="open-save-template-modal-button"]', 5)
                    ->click('button[dusk="open-save-template-modal-button"]')
                    ->waitForText('Sauvegarder comme nouveau modèle')
                    
                    // Enter template name
                    ->type('#template_name', 'Mon Modèle Custom')
                    ->check('#is_default')
                    
                    // Save
                    ->click('@save-template-confirm-button')
                    ->waitForText('Le modèle a été enregistré avec succès.')
                    
                    // Check database
                    ->visit(route('dashboard')); // Just to trigger a reload or check DB via next line if I had a way
        });

        $this->assertDatabaseHas('templates', [
            'user_id' => $user->id,
            'name' => 'Mon Modèle Custom',
            'primary_color' => '#123456',
            'font_family' => 'monospace',
            'is_default' => true,
        ]);
    }
}