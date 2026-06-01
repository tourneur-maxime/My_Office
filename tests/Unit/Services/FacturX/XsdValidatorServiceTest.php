<?php

namespace Tests\Unit\Services\FacturX;

use App\Exceptions\FacturXValidationException;
use App\Services\FacturX\XsdValidatorService;
use Tests\TestCase;

class XsdValidatorServiceTest extends TestCase
{
    private string $xsdPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xsdPath = storage_path('app/schemas/factur-x/FACTUR-X_EN16931.xsd');
    }

    public function test_it_returns_true_for_a_valid_xml()
    {
        if (! file_exists($this->xsdPath)) {
            $this->markTestSkipped('XSD schema file not found, skipping validation tests.');
        }

        $service = new XsdValidatorService;
        $validXml = file_get_contents(base_path('tests/Fixtures/valid-invoice.xml'));
        $this->assertTrue($service->validate($validXml));
    }

    public function test_it_throws_exception_for_an_invalid_xml()
    {
        if (! file_exists($this->xsdPath)) {
            $this->markTestSkipped('XSD schema file not found, skipping validation tests.');
        }

        $this->expectException(FacturXValidationException::class);

        $service = new XsdValidatorService;
        $invalidXml = file_get_contents(base_path('tests/Fixtures/invalid-structure.xml'));
        $service->validate($invalidXml);
    }
}
