<?php

namespace Tests\Unit\Services\FacturX;

use App\Exceptions\FacturXValidationException;
use App\Services\FacturX\SchematronValidatorService;
use Tests\TestCase;

class SchematronValidatorServiceTest extends TestCase
{
    private string $xsltPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xsltPath = storage_path('app/facturx/schemas/eInvoicing-EN16931-validation-1.3.15/cii/xslt/EN16931-CII-validation.xslt');
    }

    public function test_it_returns_true_for_a_valid_xml()
    {
        if (! file_exists($this->xsltPath)) {
            $this->markTestSkipped('Schematron XSLT file not found.');
        }

        $service = new SchematronValidatorService;
        $validXml = file_get_contents(base_path('tests/Fixtures/valid-invoice.xml'));
        $this->assertTrue($service->validate($validXml));
    }

    public function test_it_throws_exception_for_business_rule_violations()
    {
        if (! file_exists($this->xsltPath)) {
            $this->markTestSkipped('Schematron XSLT file not found.');
        }

        $this->expectException(FacturXValidationException::class);
        $this->expectExceptionMessage('XML failed Schematron validation');

        $service = new SchematronValidatorService;

        // XML with mismatched totals: TaxBasis(100) + Tax(20) != Grand(999)
        $invalidXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">
    <rsm:ExchangedDocumentContext>
        <ram:GuidelineSpecifiedDocumentContextParameter>
            <ram:ID>urn:factur-x.eu:1p0:minimum</ram:ID>
        </ram:GuidelineSpecifiedDocumentContextParameter>
    </rsm:ExchangedDocumentContext>
    <rsm:ExchangedDocument>
        <ram:ID>FAC-2023-0001</ram:ID>
        <ram:TypeCode>380</ram:TypeCode>
        <ram:IssueDateTime>
            <udt:DateTimeString format="102">20230115</udt:DateTimeString>
        </ram:IssueDateTime>
    </rsm:ExchangedDocument>
    <rsm:SupplyChainTradeTransaction>
        <ram:ApplicableHeaderTradeAgreement>
            <ram:SellerTradeParty>
                <ram:Name>Test Company</ram:Name>
            </ram:SellerTradeParty>
            <ram:BuyerTradeParty>
                <ram:Name>Test Client</ram:Name>
            </ram:BuyerTradeParty>
        </ram:ApplicableHeaderTradeAgreement>
        <ram:ApplicableHeaderTradeDelivery/>
        <ram:ApplicableHeaderTradeSettlement>
            <ram:InvoiceCurrencyCode>EUR</ram:InvoiceCurrencyCode>
            <ram:SpecifiedTradeSettlementHeaderMonetarySummation>
                <ram:LineTotalAmount>100.00</ram:LineTotalAmount>
                <ram:TaxBasisTotalAmount>100.00</ram:TaxBasisTotalAmount>
                <ram:TaxTotalAmount currencyID="EUR">20.00</ram:TaxTotalAmount>
                <ram:GrandTotalAmount>999.00</ram:GrandTotalAmount>
                <ram:DuePayableAmount>999.00</ram:DuePayableAmount>
            </ram:SpecifiedTradeSettlementHeaderMonetarySummation>
        </ram:ApplicableHeaderTradeSettlement>
    </rsm:SupplyChainTradeTransaction>
</rsm:CrossIndustryInvoice>
XML;

        $service->validate($invalidXml);
    }
}
