<?php

declare(strict_types=1);

namespace App\Services\FacturX;

use App\Exceptions\FacturXValidationException;
use DOMDocument;

class XsdValidatorService
{
    private string $xsdPath;

    public function __construct()
    {
        $this->xsdPath = storage_path('app/schemas/factur-x/FACTUR-X_EN16931.xsd');
    }

    public function validate(string $xmlContent): bool
    {
        if (! file_exists($this->xsdPath)) {
            throw new FacturXValidationException('XSD schema file not found at: '.$this->xsdPath);
        }

        $dom = new DOMDocument;
        $dom->loadXML($xmlContent);

        // Enable libxml error handling
        libxml_use_internal_errors(true);
        libxml_clear_errors();

        if (! $dom->schemaValidate($this->xsdPath)) {
            $errors = libxml_get_errors();
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[] = sprintf(
                    '[%s %s] %s (Line: %d, Column: %d)',
                    $this->getLibxmlErrorLevel($error->level),
                    $error->code,
                    trim($error->message),
                    $error->line,
                    $error->column
                );
            }
            libxml_clear_errors();
            throw new FacturXValidationException('XML failed XSD validation.', $formattedErrors);
        }

        libxml_clear_errors();

        return true;
    }

    private function getLibxmlErrorLevel(int $level): string
    {
        return match ($level) {
            LIBXML_ERR_WARNING => 'WARNING',
            LIBXML_ERR_ERROR => 'ERROR',
            LIBXML_ERR_FATAL => 'FATAL',
            default => 'UNKNOWN',
        };
    }
}
