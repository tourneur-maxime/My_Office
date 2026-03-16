<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    protected InvoiceNumberService $invoiceNumberService;

    public function __construct(InvoiceNumberService $invoiceNumberService)
    {
        $this->invoiceNumberService = $invoiceNumberService;
    }

    public function createFromQuote(Quote $quote): Invoice
    {
        return DB::transaction(function () use ($quote) {
            $invoice = new Invoice([
                'client_id' => $quote->client_id,
                'user_id' => $quote->user_id,
                'quote_id' => $quote->id,
                'template_id' => $quote->template_id,
                'layout_configuration' => $quote->layout_configuration,
                'status' => InvoiceStatus::Brouillon,
                'issue_date' => now(),
                'subtotal' => $quote->subtotal,
                'vat_amount' => $quote->vat_amount,
                'total' => $quote->total,
            ]);

            // Attempt to get invoice number
            try {
                $invoice->invoice_number = $this->invoiceNumberService->getNextInvoiceNumber($quote->user);
            } catch (\Exception $e) {
                throw $e; // Re-throw to halt transaction
            }

            $invoice->save();

            foreach ($quote->lineItems as $lineItem) {
                $invoice->lineItems()->create([
                    'description' => $lineItem->description,
                    'quantity' => $lineItem->quantity,
                    'unit_price' => $lineItem->unit_price,
                    'vat_rate' => $lineItem->vat_rate,
                    'total' => $lineItem->total,
                    'sort_order' => $lineItem->sort_order,
                ]);
            }

            return $invoice;
        });
    }

    public function create(Prospect $client, array $lineItems, ?int $templateId = null, ?array $layoutConfig = null): Invoice
    {
        return DB::transaction(function () use ($client, $lineItems, $templateId, $layoutConfig) {
            $subtotal = 0;
            $vatAmount = 0;

            foreach ($lineItems as $item) {
                $lineSubtotal = round($item['quantity'] * $item['unit_price'], 2);
                $subtotal += $lineSubtotal;
                $vatAmount += round($lineSubtotal * ($item['vat_rate'] / 100), 2);
            }

            $total = $subtotal + $vatAmount;

            $invoice = new Invoice([
                'client_id' => $client->id,
                'user_id' => $client->user_id,
                'template_id' => $templateId,
                'layout_configuration' => $layoutConfig,
                'status' => InvoiceStatus::Brouillon,
                'issue_date' => now(),
                'subtotal' => round($subtotal, 2),
                'vat_amount' => round($vatAmount, 2),
                'total' => round($total, 2),
            ]);

            $invoice->invoice_number = $this->invoiceNumberService->getNextInvoiceNumber($client->user);
            $invoice->save();

            foreach ($lineItems as $index => $item) {
                $lineTotal = round($item['quantity'] * $item['unit_price'] * (1 + $item['vat_rate'] / 100), 2);
                $invoice->lineItems()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['vat_rate'],
                    'total' => $lineTotal,
                    'sort_order' => $item['sort_order'] ?? $index,
                ]);
            }

            return $invoice;
        });
    }

    public function update(Invoice $invoice, array $lineItemsData): Invoice
    {
        return DB::transaction(function () use ($invoice, $lineItemsData) {
            // Deduplicate line items by ID to prevent silent data loss from duplicate IDs
            $seenIds = [];
            $deduplicatedItems = [];
            foreach ($lineItemsData as $item) {
                $id = $item['id'] ?? null;
                if ($id !== null && in_array($id, $seenIds, true)) {
                    // Skip duplicate ID entries - keep only the first occurrence
                    continue;
                }
                if ($id !== null) {
                    $seenIds[] = $id;
                }
                $deduplicatedItems[] = $item;
            }
            $lineItemsData = $deduplicatedItems;

            $existingIds = collect($lineItemsData)->pluck('id')->filter()->toArray();

            // Delete missing items
            $invoice->lineItems()->whereNotIn('id', $existingIds)->delete();

            $subtotal = 0;
            $vatAmount = 0;

            foreach ($lineItemsData as $index => $itemData) {
                // Calculate line total using standard precision
                $lineTotal = round($itemData['quantity'] * $itemData['unit_price'] * (1 + $itemData['vat_rate'] / 100), 2);

                // Calculate invoice totals
                $lineSubtotal = round($itemData['quantity'] * $itemData['unit_price'], 2);
                $subtotal += $lineSubtotal;
                $vatAmount += round($lineSubtotal * ($itemData['vat_rate'] / 100), 2);

                $data = [
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'vat_rate' => $itemData['vat_rate'],
                    'total' => $lineTotal,
                    'sort_order' => $itemData['sort_order'] ?? $index,
                ];

                if (isset($itemData['id'])) {
                    $invoice->lineItems()->where('id', $itemData['id'])->update($data);
                } else {
                    $invoice->lineItems()->create($data);
                }
            }

            // Update invoice totals
            $invoice->update([
                'subtotal' => round($subtotal, 2),
                'vat_amount' => round($vatAmount, 2),
                'total' => round($subtotal + $vatAmount, 2),
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * Create a credit note (avoir) for an existing invoice.
     */
    public function createCreditNote(Invoice $originalInvoice): Invoice
    {
        return DB::transaction(function () use ($originalInvoice) {
            $originalInvoice->load('lineItems');

            $creditNote = new Invoice([
                'client_id' => $originalInvoice->client_id,
                'user_id' => $originalInvoice->user_id,
                'type' => InvoiceType::Avoir,
                'credit_note_for_id' => $originalInvoice->id,
                'template_id' => $originalInvoice->template_id,
                'layout_configuration' => $originalInvoice->layout_configuration,
                'status' => InvoiceStatus::Brouillon,
                'issue_date' => now(),
                'subtotal' => -abs($originalInvoice->subtotal),
                'vat_amount' => -abs($originalInvoice->vat_amount),
                'total' => -abs($originalInvoice->total),
            ]);

            $creditNote->invoice_number = $this->invoiceNumberService->getNextCreditNoteNumber($originalInvoice->user);
            $creditNote->save();

            foreach ($originalInvoice->lineItems as $lineItem) {
                $creditNote->lineItems()->create([
                    'description' => $lineItem->description,
                    'quantity' => -abs($lineItem->quantity),
                    'unit_price' => $lineItem->unit_price,
                    'vat_rate' => $lineItem->vat_rate,
                    'total' => -abs($lineItem->total),
                    'sort_order' => $lineItem->sort_order,
                ]);
            }

            return $creditNote;
        });
    }

    /**
     * Calculate SHA-256 hash of the PDF content and store it in the invoice.
     */
    public function calculateAndStoreSignatureHash(Invoice $invoice, string $pdfContent): void
    {
        $hash = hash('sha256', $pdfContent);

        $invoice->update([
            'signature_hash' => $hash,
            'is_ready_for_signature' => true,
        ]);
    }
}
