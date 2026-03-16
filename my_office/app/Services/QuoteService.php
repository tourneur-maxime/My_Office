<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Models\Prospect;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;
use App\Services\QuoteNumberService;

class QuoteService
{
    protected QuoteNumberService $quoteNumberService;

    public function __construct(QuoteNumberService $quoteNumberService)
    {
        $this->quoteNumberService = $quoteNumberService;
    }

    /**
     * Create a new quote and its line items, performing all necessary calculations.
     * Quote number is assigned after successful creation to ensure gapless sequencing.
     */
    public function createQuote(Prospect $client, array $lineItemsData, ?\DateTimeInterface $expiresAt = null, ?int $templateId = null, ?array $layoutConfig = null): Quote
    {
        return DB::transaction(function () use ($client, $lineItemsData, $expiresAt, $templateId, $layoutConfig) {
            $quote = new Quote([
                'client_id' => $client->id,
                'user_id' => $client->user_id,
                'status' => QuoteStatus::Brouillon,
                'expires_at' => $expiresAt,
                'template_id' => $templateId,
                'layout_configuration' => $layoutConfig,
            ]);

            // Save once to generate ID
            $quote->save();

            // Assign quote number only after successful DB insert
            $quote->quote_number = $this->quoteNumberService->getNext($client->user);
            $quote->save();

            $this->updateLineItemsAndTotals($quote, $lineItemsData);

            return $quote;
        });
    }

    /**
     * Update an existing quote and its line items.
     */
    public function updateQuote(Quote $quote, array $validatedData): Quote
    {
        return DB::transaction(function () use ($quote, $validatedData) {
            $this->updateLineItemsAndTotals($quote, $validatedData['line_items']);

            $quote->update([
                'status' => $validatedData['status'],
                'expires_at' => $validatedData['expires_at'] ?? null,
                'template_id' => $validatedData['template_id'] ?? $quote->template_id,
                'layout_configuration' => $validatedData['layout_configuration'] ?? $quote->layout_configuration,
            ]);

            return $quote;
        });
    }

    /**
     * Duplicate a quote and its line items.
     */
    public function duplicateQuote(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote) {
            $newQuote = $quote->replicate();
            $newQuote->status = QuoteStatus::Brouillon;
            $newQuote->expires_at = null;
            $newQuote->quote_number = null;
            $newQuote->save();

            // Assign new quote number after successful insert
            $numberService = new QuoteNumberService();
            $newQuote->quote_number = $numberService->getNext($quote->user);
            $newQuote->save();

            foreach ($quote->lineItems as $lineItem) {
                $newLineItem = $lineItem->replicate();
                $newLineItem->quote_id = $newQuote->id;
                $newLineItem->save();
            }

            return $newQuote;
        });
    }

    /**
     * Update line items and recalculate quote totals with precision rounding.
     */
    protected function updateLineItemsAndTotals(Quote $quote, array $lineItemsData): void
    {
        $updatedLineItemIds = [];

        foreach ($lineItemsData as $index => $itemData) {
            $itemData['sort_order'] = $index;

            // Ensure numeric values are properly handled
            $itemData['quantity'] = (float) ($itemData['quantity'] ?? 0);
            $itemData['unit_price'] = (float) ($itemData['unit_price'] ?? 0);
            $itemData['vat_rate'] = (float) ($itemData['vat_rate'] ?? 0);

            if (isset($itemData['id'])) {
                $lineItem = $quote->lineItems()->where('id', $itemData['id'])->firstOrFail();
                $lineItem->update($itemData);
            } else {
                $lineItem = $quote->lineItems()->create($itemData);
            }
            $updatedLineItemIds[] = $lineItem->id;
        }

        $quote->lineItems()->whereNotIn('id', $updatedLineItemIds)->delete();
        $quote->load('lineItems');

        $subtotal = 0;
        $vatAmount = 0;

        foreach ($quote->lineItems as $item) {
            $itemTotal = round($item->quantity * $item->unit_price, 2);
            $itemVat = round($itemTotal * ($item->vat_rate / 100), 2);

            $subtotal += $itemTotal;
            $vatAmount += $itemVat;
        }

        $quote->subtotal = round($subtotal, 2);
        $quote->vat_amount = round($vatAmount, 2);
        $quote->total = round($subtotal + $vatAmount, 2);
        $quote->save();
    }
}
