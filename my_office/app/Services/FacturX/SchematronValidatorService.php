<?php

declare(strict_types=1);

namespace App\Services\FacturX;

use App\Exceptions\FacturXValidationException;
use DOMDocument;
use XSLTProcessor;

class SchematronValidatorService
{
    private string $xsltPath;

    public function __construct()
    {
        $this->xsltPath = storage_path('app/facturx/schemas/eInvoicing-EN16931-validation-1.3.15/cii/xslt/EN16931-CII-validation.xslt');
    }

    public function validate(string $xmlContent): bool
    {
        if (! file_exists($this->xsltPath)) {
            \Illuminate\Support\Facades\Log::warning('Schematron XSLT file not found at: '.$this->xsltPath.'. Skipping Schematron validation.');

            return true;
        }

        $xml = new DOMDocument;
        $xml->loadXML($xmlContent);

        $xslt = new DOMDocument;
        $xslt->load($this->xsltPath);

        $processor = new XSLTProcessor;
        $processor->importStylesheet($xslt);

        $result = $processor->transformToDoc($xml);

        $failedAsserts = $result->getElementsByTagNameNS('http://purl.oclc.org/dsdl/svrl', 'failed-assert');

        if ($failedAsserts->length > 0) {
            $errors = [];
            foreach ($failedAsserts as $assert) {
                $errorMessage = trim($assert->getElementsByTagName('text')->item(0)->textContent);
                $errors[] = $errorMessage;
            }
            throw new FacturXValidationException('XML failed Schematron validation.', $errors);
        }

        return true;
    }
}
