<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Services\FacturX\FacturXService;
use App\Services\FacturX\SchematronValidatorService;
use App\Services\LegalMentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ComplianceController extends Controller
{
    protected FacturXService $facturXService;
    protected LegalMentionService $legalMentionService;
    protected SchematronValidatorService $schematronValidator;

    public function __construct(
        FacturXService $facturXService, 
        LegalMentionService $legalMentionService,
        SchematronValidatorService $schematronValidator
    ) {
        $this->facturXService = $facturXService;
        $this->legalMentionService = $legalMentionService;
        $this->schematronValidator = $schematronValidator;
    }

    public function validateData(Request $request): JsonResponse
    {
        try {
            // 1. Create temporary Invoice model
            $invoice = new Invoice($request->only([
                'invoice_number', 'issue_date', 'due_date', 'currency', 'client_id'
            ]));
            
            // Set defaults if missing (for validation context)
            $invoice->invoice_number = $invoice->invoice_number ?? 'DRAFT';
            $invoice->issue_date = $invoice->issue_date ?? now();
            
            // 2. Set Relations
            $user = $request->user();
            $invoice->setRelation('user', $user);
            
            // Load Company Profile
            if (!$user->companyProfile) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'valid' => false,
                        'errors' => ['company' => ["Le profil d'entreprise est manquant."]],
                        'statuses' => [
                            'xml' => 'invalid',
                            'schematron' => 'pending',
                            'pdf' => 'valid',
                            'xmp' => 'valid',
                            'signature' => 'not_ready',
                            'legal_mentions' => 'warning',
                        ]
                    ]
                ]);
            }
            $invoice->user->setRelation('companyProfile', $user->companyProfile);

            // Load Client
            $client = Prospect::find($request->client_id);
            if (!$client) {
                 return response()->json([
                    'success' => true,
                    'data' => [
                        'valid' => false,
                        'errors' => ['client_id' => ["Le client sélectionné est introuvable."]],
                        'statuses' => [
                            'xml' => 'invalid',
                            'schematron' => 'pending',
                            'pdf' => 'valid',
                            'xmp' => 'valid',
                            'signature' => 'not_ready',
                            'legal_mentions' => 'warning',
                        ]
                    ]
                ]);
            }
            $invoice->setRelation('client', $client);

            // Set Line Items
            $lineItemsData = $request->input('line_items', []);
            $lineItems = collect($lineItemsData)->map(function ($itemData, $index) {
                $item = new InvoiceLineItem($itemData);
                $item->id = $itemData['id'] ?? ($index + 1); 
                return $item;
            });
            $invoice->setRelation('lineItems', $lineItems);
            
            // Calculate totals
            $subtotal = $lineItems->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            });
            $invoice->subtotal = $subtotal;
            
            $vatAmount = $lineItems->sum(function ($item) {
                return round($item->quantity * $item->unit_price * ($item->vat_rate / 100), 2);
            });
            $invoice->vat_amount = $vatAmount;
            $invoice->total = $subtotal + $vatAmount;

            // 3. Generate XML
            $xmlContent = $this->facturXService->generateXml($invoice);

            // 4. Validate XML (XSD)
            $xsdResult = $this->facturXService->validateXml($xmlContent);
            $mappedErrors = [];
            if (!$xsdResult['is_valid']) {
                $mappedErrors = $this->mapValidationErrors($xsdResult['errors']);
            }

            // 5. Validate Schematron (Business Rules)
            $schematronStatus = 'pending';
            if (empty($mappedErrors)) {
                try {
                    $this->schematronValidator->validate($xmlContent);
                    $schematronStatus = 'valid';
                } catch (\App\Exceptions\FacturXValidationException $e) {
                    $schematronStatus = 'invalid';
                    $mappedErrors = array_merge_recursive($mappedErrors, $this->mapSchematronErrors($e->getErrors()));
                }
            }

            // 6. Readiness Checks
            $pdfStatus = $this->checkPdfReadiness();
            $signatureStatus = $this->checkSignatureReadiness($user, empty($mappedErrors));
            
            // Legal Mentions Check
            $legalMentions = $this->legalMentionService->getMentions($invoice);
            $legalStatus = !empty($legalMentions) ? 'valid' : 'warning';

            $isValid = empty($mappedErrors) && $pdfStatus === 'valid' && $schematronStatus === 'valid';

            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => $isValid,
                    'errors' => $mappedErrors,
                    'statuses' => [
                        'xml' => empty($xsdResult['errors']) ? 'valid' : 'invalid',
                        'schematron' => $schematronStatus,
                        'pdf' => $pdfStatus,
                        'xmp' => 'valid',
                        'signature' => $signatureStatus,
                        'legal_mentions' => $legalStatus,
                    ],
                    'report' => [
                        'xsd' => $xsdResult,
                        'schematron' => [
                            'is_valid' => $schematronStatus === 'valid',
                            'errors' => $schematronStatus === 'invalid' ? $mappedErrors['schematron'] ?? [] : [],
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => false,
                    'errors' => ['global' => [$e->getMessage()]],
                    'statuses' => [
                        'xml' => 'invalid',
                        'schematron' => 'pending',
                        'pdf' => 'pending',
                        'xmp' => 'pending',
                        'signature' => 'not_ready',
                    ]
                ]
            ]);
        }
    }

    /**
     * Map technical XSD errors to user-friendly French messages.
     */
    private function mapValidationErrors(array $xsdErrors): array
    {
        $mapped = [];
        $uniqueMessages = [];

        foreach ($xsdErrors as $error) {
            $userMessage = '';
            $field = 'global';

            if (str_contains($error, "The element 'SellerTradeParty' has invalid child element")) {
                $userMessage = "Les informations du vendeur (Votre Entreprise) sont incomplètes.";
                $field = 'company';
            } elseif (str_contains($error, "The element 'BuyerTradeParty' has invalid child element")) {
                $userMessage = "Les informations de l'acheteur (Client) sont incomplètes.";
                $field = 'client_id';
            } elseif (str_contains($error, "'GlobalID'") || str_contains($error, "'SIRET'")) {
                 $userMessage = "Le numéro SIRET est manquant ou invalide.";
                 $field = 'company';
            } elseif (str_contains($error, "'SpecifiedTaxRegistration'")) {
                 $userMessage = "Le numéro de TVA est manquant.";
                 $field = 'company';
            } elseif (str_contains($error, "'PostalTradeAddress'")) {
                 $userMessage = "L'adresse est incomplète (Code postal ou Ville manquant).";
                 $field = 'company';
            } elseif (str_contains($error, "'NetPriceProductTradePrice'")) {
                 $userMessage = "Le prix unitaire d'un article est invalide.";
                 $field = 'line_items';
            } else {
                $userMessage = "Erreur de structure XML : " . $error;
            }

            if (!in_array($userMessage, $uniqueMessages)) {
                $mapped[$field][] = $userMessage;
                $uniqueMessages[] = $userMessage;
            }
        }

        return $mapped;
    }

    /**
     * Map technical Schematron errors to user-friendly French messages.
     */
    private function mapSchematronErrors(array $errors): array
    {
        $mapped = ['schematron' => []];
        foreach ($errors as $error) {
            // Handle case where error might be an array
            $errorString = is_array($error) ? json_encode($error) : (string) $error;

            // Simplified mapping for common EN 16931 rules
            if (str_contains($errorString, "BR-S-08") || str_contains($errorString, "sum of all Invoice line net amounts")) {
                $msg = "Le montant total HT ne correspond pas à la somme des lignes.";
            } elseif (str_contains($errorString, "BR-S-09") || str_contains($errorString, "sum of all taxes")) {
                $msg = "Le calcul de la TVA est incohérent avec les taux appliqués.";
            } else {
                $msg = "Règle métier non respectée : " . $errorString;
            }
            $mapped['schematron'][] = $msg;
        }
        return $mapped;
    }

    private function checkPdfReadiness(): string
    {
        return 'valid'; 
    }

    private function checkSignatureReadiness($user, bool $xmlValid): string
    {
        if (!$xmlValid) {
            return 'not_ready';
        }
        return 'not_ready_no_cert'; 
    }
}
