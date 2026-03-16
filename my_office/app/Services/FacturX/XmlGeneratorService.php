<?php

declare(strict_types=1);

namespace App\Services\FacturX;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use DOMDocument;

class XmlGeneratorService
{
    private DOMDocument $doc;

    private \DOMElement $root;

    public function generate(Invoice $invoice): string
    {
        $this->doc = new DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = true;

        $this->createRoot();
        $this->createExchangedDocumentContext();
        $this->createExchangedDocument($invoice);
        $this->createSupplyChainTradeTransaction($invoice);

        return $this->doc->saveXML();
    }

    private function createRoot(): void
    {
        $this->root = $this->doc->createElementNS('urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100', 'rsm:CrossIndustryInvoice');
        $this->doc->appendChild($this->root);
        $this->root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
        $this->root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100');
    }

    private function createExchangedDocumentContext(): void
    {
        $context = $this->doc->createElement('rsm:ExchangedDocumentContext');
        $this->root->appendChild($context);

        $param = $this->doc->createElement('ram:GuidelineSpecifiedDocumentContextParameter');
        $context->appendChild($param);

        $id = $this->doc->createElement('ram:ID', 'urn:factur-x.eu:1p0:minimum');
        $param->appendChild($id);
    }

    private function createExchangedDocument(Invoice $invoice): void
    {
        $exchangedDoc = $this->doc->createElement('rsm:ExchangedDocument');
        $this->root->appendChild($exchangedDoc);

        $id = $this->doc->createElement('ram:ID', $invoice->invoice_number);
        $exchangedDoc->appendChild($id);

        $typeCode = $this->doc->createElement('ram:TypeCode', '380');
        $exchangedDoc->appendChild($typeCode);

        $issueDateTime = $this->doc->createElement('ram:IssueDateTime');
        $exchangedDoc->appendChild($issueDateTime);

        $issueDate = $invoice->issue_date ?? $invoice->created_at;
        $dateTimeString = $this->doc->createElement('udt:DateTimeString', $issueDate->format('Ymd'));
        $dateTimeString->setAttribute('format', '102');
        $issueDateTime->appendChild($dateTimeString);
    }

    private function createSupplyChainTradeTransaction(Invoice $invoice): void
    {
        $transaction = $this->doc->createElement('rsm:SupplyChainTradeTransaction');
        $this->root->appendChild($transaction);

        foreach ($invoice->lineItems as $index => $lineItem) {
            $this->createIncludedSupplyChainTradeLineItem($transaction, $lineItem, $index + 1);
        }

        $this->createApplicableHeaderTradeAgreement($transaction, $invoice);
        $this->createApplicableHeaderTradeDelivery($transaction);
        $this->createApplicableHeaderTradeSettlement($transaction, $invoice);
    }

    private function createApplicableHeaderTradeDelivery(\DOMElement $transaction): void
    {
        $delivery = $this->doc->createElement('ram:ApplicableHeaderTradeDelivery');
        $transaction->appendChild($delivery);
    }

    private function createIncludedSupplyChainTradeLineItem(\DOMElement $transaction, InvoiceLineItem $lineItem, int $index): void
    {
        $lineItemElement = $this->doc->createElement('ram:IncludedSupplyChainTradeLineItem');
        $transaction->appendChild($lineItemElement);

        $docLineDoc = $this->doc->createElement('ram:AssociatedDocumentLineDocument');
        $lineItemElement->appendChild($docLineDoc);

        $lineId = $this->doc->createElement('ram:LineID', (string) $index);
        $docLineDoc->appendChild($lineId);

        $product = $this->doc->createElement('ram:SpecifiedTradeProduct');
        $lineItemElement->appendChild($product);

        $name = $this->doc->createElement('ram:Name', $lineItem->description);
        $product->appendChild($name);

        $tradeAgreement = $this->doc->createElement('ram:SpecifiedLineTradeAgreement');
        $lineItemElement->appendChild($tradeAgreement);

        $netPrice = $this->doc->createElement('ram:NetPriceProductTradePrice');
        $tradeAgreement->appendChild($netPrice);

        $chargeAmount = $this->doc->createElement('ram:ChargeAmount', number_format($lineItem->unit_price, 2, '.', ''));
        $netPrice->appendChild($chargeAmount);

        $delivery = $this->doc->createElement('ram:SpecifiedLineTradeDelivery');
        $lineItemElement->appendChild($delivery);

        $billedQuantity = $this->doc->createElement('ram:BilledQuantity', number_format($lineItem->quantity, 4, '.', ''));
        $billedQuantity->setAttribute('unitCode', 'C62');
        $delivery->appendChild($billedQuantity);

        $settlement = $this->doc->createElement('ram:SpecifiedLineTradeSettlement');
        $lineItemElement->appendChild($settlement);

        $tradeTax = $this->doc->createElement('ram:ApplicableTradeTax');
        $settlement->appendChild($tradeTax);

        $typeCode = $this->doc->createElement('ram:TypeCode', 'VAT');
        $tradeTax->appendChild($typeCode);

        $categoryCode = $this->doc->createElement('ram:CategoryCode', 'S');
        $tradeTax->appendChild($categoryCode);

        $rate = $this->doc->createElement('ram:RateApplicablePercent', number_format($lineItem->vat_rate, 2, '.', ''));
        $tradeTax->appendChild($rate);

        $monetarySummation = $this->doc->createElement('ram:SpecifiedTradeSettlementLineMonetarySummation');
        $settlement->appendChild($monetarySummation);

        $lineTotal = $this->doc->createElement('ram:LineTotalAmount', number_format($lineItem->quantity * $lineItem->unit_price, 2, '.', ''));
        $monetarySummation->appendChild($lineTotal);
    }

    private function createApplicableHeaderTradeAgreement(\DOMElement $transaction, Invoice $invoice): void
    {
        $agreement = $this->doc->createElement('ram:ApplicableHeaderTradeAgreement');
        $transaction->appendChild($agreement);

        $this->createSellerTradeParty($agreement, $invoice->user->companyProfile);
        $this->createBuyerTradeParty($agreement, $invoice->client);
    }

    private function createSellerTradeParty(\DOMElement $agreement, $company): void
    {
        $seller = $this->doc->createElement('ram:SellerTradeParty');
        $agreement->appendChild($seller);

        $name = $this->doc->createElement('ram:Name', $company->name);
        $seller->appendChild($name);

        $legalOrg = $this->doc->createElement('ram:SpecifiedLegalOrganization');
        $seller->appendChild($legalOrg);

        $orgID = $this->doc->createElement('ram:ID', $company->siret);
        $legalOrg->appendChild($orgID);

        $postalAddress = $this->doc->createElement('ram:PostalTradeAddress');
        $seller->appendChild($postalAddress);

        $postcodeCode = $this->doc->createElement('ram:PostcodeCode', $company->zip_code);
        $postalAddress->appendChild($postcodeCode);

        $lineOne = $this->doc->createElement('ram:LineOne', $company->address);
        $postalAddress->appendChild($lineOne);

        $cityName = $this->doc->createElement('ram:CityName', $company->city);
        $postalAddress->appendChild($cityName);

        $countryID = $this->doc->createElement('ram:CountryID', 'FR');
        $postalAddress->appendChild($countryID);
    }

    private function createBuyerTradeParty(\DOMElement $agreement, $client): void
    {
        $buyer = $this->doc->createElement('ram:BuyerTradeParty');
        $agreement->appendChild($buyer);

        $name = $this->doc->createElement('ram:Name', $client->name);
        $buyer->appendChild($name);

        $postalAddress = $this->doc->createElement('ram:PostalTradeAddress');
        $buyer->appendChild($postalAddress);

        $postcodeCode = $this->doc->createElement('ram:PostcodeCode', $client->zip_code);
        $postalAddress->appendChild($postcodeCode);

        $lineOne = $this->doc->createElement('ram:LineOne', $client->address);
        $postalAddress->appendChild($lineOne);

        $cityName = $this->doc->createElement('ram:CityName', $client->city);
        $postalAddress->appendChild($cityName);

        $countryID = $this->doc->createElement('ram:CountryID', 'FR');
        $postalAddress->appendChild($countryID);
    }

    private function createApplicableHeaderTradeSettlement(\DOMElement $transaction, Invoice $invoice): void
    {
        $settlement = $this->doc->createElement('ram:ApplicableHeaderTradeSettlement');
        $transaction->appendChild($settlement);

        $currency = $this->doc->createElement('ram:InvoiceCurrencyCode', 'EUR');
        $settlement->appendChild($currency);

        $paymentMeans = $this->doc->createElement('ram:SpecifiedTradeSettlementPaymentMeans');
        $settlement->appendChild($paymentMeans);

        $typeCode = $this->doc->createElement('ram:TypeCode', '30');
        $paymentMeans->appendChild($typeCode);

        $monetarySummation = $this->doc->createElement('ram:SpecifiedTradeSettlementHeaderMonetarySummation');
        $settlement->appendChild($monetarySummation);

        $lineTotal = $this->doc->createElement('ram:LineTotalAmount', number_format($invoice->subtotal, 2, '.', ''));
        $monetarySummation->appendChild($lineTotal);

        $taxBasisTotal = $this->doc->createElement('ram:TaxBasisTotalAmount', number_format($invoice->subtotal, 2, '.', ''));
        $monetarySummation->appendChild($taxBasisTotal);

        $taxTotal = $this->doc->createElement('ram:TaxTotalAmount', number_format($invoice->vat_amount, 2, '.', ''));
        $taxTotal->setAttribute('currencyID', 'EUR');
        $monetarySummation->appendChild($taxTotal);

        $grandTotal = $this->doc->createElement('ram:GrandTotalAmount', number_format($invoice->total, 2, '.', ''));
        $monetarySummation->appendChild($grandTotal);

        $duePayable = $this->doc->createElement('ram:DuePayableAmount', number_format($invoice->total, 2, '.', ''));
        $monetarySummation->appendChild($duePayable);
    }
}
