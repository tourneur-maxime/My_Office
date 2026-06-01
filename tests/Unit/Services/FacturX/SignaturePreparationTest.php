<?php

namespace Tests\Unit\Services\FacturX;

use App\Models\Invoice;
use App\Services\FacturX\ComplianceEngine;
use App\Services\FacturX\FacturXService;
use App\Services\FacturX\XsdValidatorService;
use App\Services\FacturX\SchematronValidatorService;
use App\Services\FacturX\PdfGeneratorService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Mockery;

class SignaturePreparationTest extends TestCase
{
    public function test_compliance_engine_calculates_hash_after_generation()
    {
        // Arrange
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $invoice->id = 1;
        
        $xml_generator = Mockery::mock(FacturXService::class);
        $xsd_validator = Mockery::mock(XsdValidatorService::class);
        $schematron_validator = Mockery::mock(SchematronValidatorService::class);
        $pdf_generator = Mockery::mock(PdfGeneratorService::class);

        $xml_content = '<xml>test</xml>';
        $pdf_content = '%PDF-1.4 test content';
        $conformance_level = 'EN 16931';

        $xml_generator->shouldReceive('generateXml')->with($invoice)->andReturn($xml_content);
        $xsd_validator->shouldReceive('validate')->with($xml_content);
        $schematron_validator->shouldReceive('validate')->with($xml_content);
        $pdf_generator->shouldReceive('generateWithFacturX')
            ->with($invoice, $xml_content, $conformance_level)
            ->andReturn($pdf_content);

        // Expect the invoice to be updated with hash and ready flag
        $invoice->shouldReceive('update')->once()->with(Mockery::on(function ($data) {
            return !empty($data['signature_hash']) && $data['is_ready_for_signature'] === true;
        }));

        $engine = new ComplianceEngine(
            $xml_generator,
            $xsd_validator,
            $schematron_validator,
            $pdf_generator
        );

        // Act
        $result = $engine->generate($invoice);

        // Assert
        $this->assertEquals($pdf_content, $result);
    }
}
