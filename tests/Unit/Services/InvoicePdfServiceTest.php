<?php

namespace Tests\Unit\Services;

use App\DTOs\BrandingDTO;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoicePdfService;
use App\Services\LegalMentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoicePdfService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoicePdfService(new LegalMentionService());
    }

    public function test_determine_font_maps_correctly()
    {
        // Access protected method via reflection or just make it public for now if easy, 
        // but strictly we should test public interface. 
        // Since I am refactoring, I will make them public helper methods or test via subclass/reflection.
        // Let's use reflection.
        
        $method = new \ReflectionMethod(InvoicePdfService::class, 'determineFont');
        $method->setAccessible(true);

        $this->assertEquals('dejavusans', $method->invoke($this->service, 'sans-serif'));
        $this->assertEquals('dejavuserif', $method->invoke($this->service, 'serif'));
        $this->assertEquals('dejavusansmono', $method->invoke($this->service, 'monospace'));
        $this->assertEquals('dejavusans', $method->invoke($this->service, 'Arial, sans-serif'));
        $this->assertEquals('dejavuserif', $method->invoke($this->service, 'Georgia, serif'));
    }

    public function test_determine_colors_uses_branding_dto()
    {
        $branding = new BrandingDTO(
            primaryColor: '#FF0000',
            secondaryColor: '#00FF00',
        );

        $method = new \ReflectionMethod(InvoicePdfService::class, 'determineColors');
        $method->setAccessible(true);

        $colors = $method->invoke($this->service, $branding);

        $this->assertEquals([255, 0, 0], $colors['primary']);
        $this->assertEquals([0, 255, 0], $colors['secondary']);
    }

    public function test_determine_colors_falls_back_to_defaults()
    {
        $branding = new BrandingDTO();

        $method = new \ReflectionMethod(InvoicePdfService::class, 'determineColors');
        $method->setAccessible(true);

        $colors = $method->invoke($this->service, $branding);

        // Default primary is blue #3B82F6 -> [59, 130, 246]
        $this->assertEquals([59, 130, 246], $colors['primary']);
        // Default secondary is dark blue #1E40AF -> [30, 64, 175]
        $this->assertEquals([30, 64, 175], $colors['secondary']);
    }
}
