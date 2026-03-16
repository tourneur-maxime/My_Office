<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Jobs\GenerateInvoicePdfJob;
use App\Models\Invoice;
use App\Models\Prospect;
use App\Services\FacturX\FacturXService;
use App\Services\InvoicePdfService;
use App\Services\InvoiceService;
use App\Services\LegalMentionService;
use App\Services\TemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    protected FacturXService $facturXService;

    protected InvoicePdfService $invoicePdfService;

    protected TemplateService $templateService;

    public function __construct(
        InvoiceService $invoiceService,
        FacturXService $facturXService,
        InvoicePdfService $invoicePdfService,
        TemplateService $templateService
    ) {
        $this->invoiceService = $invoiceService;
        $this->facturXService = $facturXService;
        $this->invoicePdfService = $invoicePdfService;
        $this->templateService = $templateService;
    }

    public function index(Request $request): Response
    {
        $invoices = Invoice::with(['client'])
            ->where('user_id', $request->user()->id)
            ->filter($request->all())
            ->orderBy('invoice_number', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['client_id', 'status', 'is_compliant', 'date_from', 'date_to', 'search']),
            'clients' => Prospect::where('user_id', $request->user()->id)->select('id', 'name', 'company')->get(),
        ]);
    }

    public function create(?Prospect $client = null): Response
    {
        if ($client && request()->user()->id !== $client->user_id) {
            abort(403, 'Unauthorized action.');
        }

        return Inertia::render('DocumentBuilder', [
            'type' => 'invoice',
            'client' => $client,
            'clients' => request()->user()->prospects()
                ->where('status', 'client')
                ->select('id', 'name', 'company', 'status')
                ->get(),
            'templates' => request()->user()->templates()->get(),
            'companyProfile' => request()->user()->companyProfile,
        ]);
    }

    public function store(LegalMentionService $legalMentionService, StoreInvoiceRequest $request, ?Prospect $client = null): RedirectResponse
    {
        if (! $client && $request->has('client_id')) {
            $client = Prospect::findOrFail($request->input('client_id'));
        }

        if (! $client) {
            return redirect()->back()->with('error', 'Le client est requis.');
        }

        if ($request->user()->id !== $client->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Validate line items
        $lineItems = $request->input('line_items', []);
        if (empty($lineItems)) {
            return redirect()->back()->with('error', 'La facture doit contenir au moins une ligne d\'article.');
        }

        $userCompanyProfile = $request->user()->companyProfile;
        if (! $userCompanyProfile) {
            return redirect()->back()->with('error', 'Veuillez compléter votre profil d\'entreprise avant de créer une facture.');
        }

        $missingFields = $legalMentionService->validateMandatoryFields($userCompanyProfile);
        if (! empty($missingFields)) {
            $errorMessage = 'Veuillez compléter les informations obligatoires de votre profil d\'entreprise pour créer une facture : '.implode(', ', $missingFields);

            return redirect()->back()->with('error', $errorMessage);
        }

        try {
            $invoice = $this->invoiceService->create(
                $client,
                $lineItems,
                $request->input('template_id'),
                $request->input('layout_configuration')
            );
        } catch (\Exception $e) {
            Log::error('Invoice creation failed: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Une erreur est survenue lors de la création de la facture.');
        }

        return redirect()->route('invoices.index')->with('success', 'Facture créée avec succès.');
    }

    public function edit(Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);

        $invoice->load(['client', 'lineItems']);

        return Inertia::render('DocumentBuilder', [
            'type' => 'invoice',
            'invoice' => $invoice,
            'clients' => request()->user()->prospects()
                ->select('id', 'name', 'company', 'status')
                ->get(),
            'templates' => request()->user()->templates()->get(),
            'companyProfile' => request()->user()->companyProfile,
        ]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'template_id' => ['nullable', 'exists:templates,id'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'line_items.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'line_items.*.sort_order' => ['nullable', 'integer'],
            'line_items.*.id' => ['nullable', 'exists:invoice_line_items,id'],
        ]);

        // Ensure we don't accept empty payload
        if (empty($validated['line_items'])) {
            return redirect()->back()->with('error', 'La facture doit contenir au moins une ligne d\'article.');
        }

        try {
            $invoice->update(['template_id' => $validated['template_id'] ?? null]);
            $this->invoiceService->update($invoice, $validated['line_items']);
        } catch (\Exception $e) {
            Log::error('Invoice update failed: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Une erreur est survenue lors de la mise à jour de la facture.');
        }

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Facture mise à jour avec succès.');
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'lineItems']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function markAsPaid(Invoice $invoice)
    {
        $this->authorize('markAsPaid', $invoice);

        $invoice->update([
            'status' => InvoiceStatus::Payé,
            'paid_at' => now(),
        ]);

        // Send notification
        $invoice->user->notify(new \App\Notifications\InvoicePaidNotification(
            invoiceNumber: $invoice->invoice_number,
            invoiceId: $invoice->id,
            amount: $invoice->total
        ));

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture marquée comme payée.');
    }

    /**
     * Global sequential integrity check.
     */
    public function checkGaps(Request $request)
    {
        $gaps = Invoice::checkSequenceGaps($request->user()->id);

        return response()->json([
            'has_gaps' => !empty($gaps),
            'gaps' => $gaps,
        ]);
    }

    /**
     * Generate Factur-X compliant PDF/A-3 invoice.
     * Validates XML, Embeds it into PDF, Saves to disk, and Downloads.
     */
    public function generate(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'lineItems', 'user.companyProfile', 'creditNoteFor']);

        try {
            // 1. Resolve Branding and capture snapshot only if not already set or still a draft
            $branding = $this->templateService->resolveBranding($invoice);

            // Only capture/update snapshot if it's missing OR if the invoice is still a draft
            // This allows fixing branding on drafts but locks it once it's a real invoice
            $updateSnapshot = is_null($invoice->branding_snapshot) || $invoice->status === InvoiceStatus::Brouillon;
            $brandingSnapshot = $updateSnapshot ? $branding->toArray() : $invoice->branding_snapshot;

            // 2. Generate XML
            $xmlContent = $this->facturXService->generateXml($invoice);

            // 3. Validate XML and get audit metadata
            $validationResult = $this->facturXService->validateXml($xmlContent);

            // 4. Generate PDF/A-3 with embedded XML and specific branding
            $pdfContent = $this->invoicePdfService->generate($invoice, $xmlContent, $branding);

            // 5. Save to storage
            $year = $invoice->issue_date ? $invoice->issue_date->format('Y') : date('Y');
            $month = $invoice->issue_date ? $invoice->issue_date->format('m') : date('m');
            $fileName = sprintf(
                'FACTURE_%s_%s_%s.pdf',
                $invoice->invoice_number,
                str_replace(' ', '_', strtoupper($invoice->client->company ?? $invoice->client->name)),
                now()->format('Y-m-d')
            );
            $path = "invoices/{$year}/{$month}/{$fileName}";

            Storage::disk('local')->put($path, $pdfContent);

            // 6. Update Invoice Metadata
            $updateData = [
                'pdf_path' => $path,
                'is_compliant' => $validationResult['is_valid'],
                'facturx_metadata' => $validationResult,
                'signature_hash' => hash('sha256', $pdfContent),
                'is_ready_for_signature' => true,
            ];

            if ($updateSnapshot) {
                $updateData['branding_snapshot'] = $brandingSnapshot;
            }

            $invoice->update($updateData);

            // 7. Return download response
            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        } catch (\Exception $e) {
            Log::error('Factur-X generation failed: '.$e->getMessage());

            return redirect()->back()->with('error', 'Une erreur est survenue lors de la génération Factur-X.');
        }
    }

    /**
     * Dispatch job for asynchronous Factur-X PDF generation.
     */
    public function generatePdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        GenerateInvoicePdfJob::dispatch($invoice)->onQueue('invoices');

        return response()->json([
            'success' => true,
            'message' => 'La génération du PDF est en cours. Vous serez notifié une fois terminée.',
        ]);
    }

    /**
     * Download an existing PDF, or generate it if not yet created.
     */
    public function download(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->download($invoice->pdf_path);
        }

        return $this->generate($invoice);
    }

    /**
     * Create a credit note (avoir) for an existing invoice.
     */
    public function createCreditNote(Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

        if ($invoice->status === InvoiceStatus::Brouillon) {
            return redirect()->back()->with('error', 'Impossible de créer un avoir pour une facture en brouillon.');
        }

        try {
            $creditNote = $this->invoiceService->createCreditNote($invoice);

            return redirect()->route('invoices.show', $creditNote->id)
                ->with('success', 'Avoir créé avec succès.');
        } catch (\Exception $e) {
            Log::error('Credit note creation failed: '.$e->getMessage(), ['exception' => $e]);

            return redirect()->back()->with('error', 'Erreur lors de la création de l\'avoir.');
        }
    }

    /**
     * Delete an invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        try {
            // Delete PDF file if it exists on disk
            if ($invoice->pdf_path) {
                Storage::disk('local')->delete($invoice->pdf_path);
            }

            // Delete the invoice (line items will be deleted via cascade)
            $invoice->delete();

            return redirect()->route('invoices.index')->with('success', 'Facture supprimée avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression de la facture.');
        }
    }
}
