@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: dejavusans, sans-serif;
            color: #333333;
            font-size: 10pt;
            line-height: 1.4;
        }
        .container {
            width: 100%;
            padding: 10mm;
        }
        .logo-container {
            margin-bottom: 5mm;
            width: 100%;
        }
        .logo-left { text-align: left; }
        .logo-center { text-align: center; }
        .logo-right { text-align: right; }
        .logo-img {
            max-width: 100%;
        }
        h1 {
            font-size: 20pt;
            color: #000000;
            margin-bottom: 5mm;
        }
        .invoice-header {
            width: 100%;
            margin-bottom: 10mm;
        }
        .company-details {
            width: 48%;
            float: left;
        }
        .client-details {
            width: 48%;
            float: right;
            text-align: right;
        }
        .clearfix {
            clear: both;
        }
        .invoice-details {
            text-align: right;
            margin-bottom: 8mm;
            margin-top: 5mm;
        }
        .invoice-details p {
            margin: 0;
            padding: 1mm 0;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10mm;
        }
        table.items th,
        table.items td {
            border: 1px solid #cccccc;
            padding: 3mm;
            text-align: left;
        }
        table.items th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .totals-container {
            width: 100%;
        }
        table.totals {
            width: 40%;
            margin-left: 60%;
            border-collapse: collapse;
        }
        table.totals th,
        table.totals td {
            padding: 2mm 3mm;
            border: none;
        }
        table.totals th {
            text-align: left;
            font-weight: normal;
        }
        table.totals td {
            text-align: right;
        }
        table.totals tr.grand-total {
            font-weight: bold;
            font-size: 11pt;
            border-top: 2px solid #333333;
        }
        .legal-mentions {
            margin-top: 15mm;
            font-size: 8pt;
            color: #555555;
            border-top: 1px solid #cccccc;
            padding-top: 5mm;
        }
        .legal-mentions p {
            margin: 0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($companyProfile->logo_path)
            <div class="logo-container logo-left"> {{-- Enforced left alignment for compliance --}}
                <img src="{{ storage_path('app/public/' . $companyProfile->logo_path) }}" class="logo-img" style="width: {{ $logoWidthMm }}mm;">
            </div>
        @endif
        <h1>{{ $invoice->type === \App\Enums\InvoiceType::Avoir ? 'Avoir' : 'Facture' }}</h1>

        <div class="invoice-header">
            <div class="company-details">
                <strong>{{ $companyProfile->name ?? 'Entreprise' }}</strong><br>
                @if($companyProfile->legal_form)
                    {{ $companyProfile->legal_form }}
                    @if($companyProfile->share_capital)
                        au capital de {{ number_format($companyProfile->share_capital, 0, ',', ' ') }} &euro;
                    @endif
                    <br>
                @endif
                {{ $companyProfile->address ?? '' }}<br>
                {{ $companyProfile->zip_code ?? '' }} {{ $companyProfile->city ?? '' }}<br>
                @if($companyProfile->siret)
                    SIRET: {{ $companyProfile->siret }}<br>
                @endif
                @if($companyProfile->rcs_number)
                    RCS: {{ $companyProfile->rcs_number }}<br>
                @endif
                @if($companyProfile->vat_number)
                    TVA Intracom.: {{ $companyProfile->vat_number }}
                @endif
            </div>
            <div class="client-details">
                <strong>Client :</strong><br>
                {{ $invoice->client->name }}<br>
                @if($invoice->client->company)
                    {{ $invoice->client->company }}<br>
                @endif
                {{ $invoice->client->address }}<br>
                {{ $invoice->client->zip_code }} {{ $invoice->client->city }}<br>
                @if($invoice->client->siret)
                    SIRET: {{ $invoice->client->siret }}<br>
                @endif
                @if($invoice->client->vat_number)
                    TVA Intracom.: {{ $invoice->client->vat_number }}
                @endif
            </div>
            <div class="clearfix"></div>
        </div>

        <div class="invoice-details">
            <p><strong>N&deg; {{ $invoice->type === \App\Enums\InvoiceType::Avoir ? 'd\'avoir' : 'de facture' }} :</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Date d'&eacute;mission :</strong> {{ $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : Carbon::parse($invoice->created_at)->format('d/m/Y') }}</p>
            @if($invoice->service_date)
                <p><strong>Date de prestation :</strong> {{ Carbon::parse($invoice->service_date)->format('d/m/Y') }}</p>
            @endif
            @if($invoice->due_date)
                <p><strong>Date d'&eacute;ch&eacute;ance :</strong> {{ Carbon::parse($invoice->due_date)->format('d/m/Y') }}</p>
            @endif
            @if($invoice->credit_note_for_id)
                <p><strong>R&eacute;f. facture originale :</strong> {{ $invoice->creditNoteFor->invoice_number ?? '' }}</p>
            @endif
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th class="text-right" style="width: 12%;">Quantité</th>
                    <th class="text-right" style="width: 16%;">Prix Unitaire HT</th>
                    <th class="text-right" style="width: 12%;">Taux TVA</th>
                    <th class="text-right" style="width: 20%;">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->lineItems as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }} &euro;</td>
                    <td class="text-right">{{ number_format($item->vat_rate, 2, ',', ' ') }} %</td>
                    <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 2, ',', ' ') }} &euro;</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals-container">
            <table class="totals">
                <tr>
                    <th>Sous-total HT</th>
                    <td>{{ number_format($invoice->subtotal, 2, ',', ' ') }} &euro;</td>
                </tr>
                <tr>
                    <th>Montant TVA</th>
                    <td>{{ number_format($invoice->vat_amount, 2, ',', ' ') }} &euro;</td>
                </tr>
                <tr class="grand-total">
                    <th>Total TTC</th>
                    <td>{{ number_format($invoice->total, 2, ',', ' ') }} &euro;</td>
                </tr>
            </table>
        </div>

        <div class="legal-mentions">
            <p>
                @if($companyProfile->is_vat_exempt ?? false)
                    TVA non applicable, art. 293 B du CGI.<br>
                @endif
                Conditions de paiement : {{ $companyProfile->default_payment_terms ?? 'Paiement &agrave; r&eacute;ception de la facture.' }}<br>
                Aucun escompte pour paiement anticip&eacute;.<br>
                {{ $companyProfile->late_payment_penalty_rate ?? 'En cas de retard de paiement, une p&eacute;nalit&eacute; de 3 fois le taux d\'int&eacute;r&ecirc;t l&eacute;gal sera appliqu&eacute;e, ainsi qu\'une indemnit&eacute; forfaitaire pour frais de recouvrement de 40 &euro;.' }}
                @if($companyProfile->custom_legal_mentions)
                    <br>{{ $companyProfile->custom_legal_mentions }}
                @endif
            </p>
        </div>
    </div>
</body>
</html>
