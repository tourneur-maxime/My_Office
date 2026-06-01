@php use Carbon\Carbon; @endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->type === \App\Enums\InvoiceType::Avoir ? 'Avoir' : 'Facture' }} #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.6;
        }
        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }
        .header, .footer {
            width: 100%;
            text-align: center;
            position: fixed;
        }
        .header {
            top: 0px;
        }
        .footer {
            bottom: 0px;
            font-size: 10px;
            color: #777;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        .invoice-header div {
            width: 48%;
        }
        .company-details {
            text-align: left;
        }
        .client-details {
            text-align: right;
        }
        h1 {
            font-size: 24px;
            color: #000;
            margin-bottom: 10px;
        }
        .invoice-details {
            text-align: right;
            margin-bottom: 20px;
        }
        .invoice-details p {
            margin: 0;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f9f9f9;
        }
        td {
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            width: 40%;
            margin-left: 60%;
        }
        .totals table {
            width: 100%;
        }
        .totals th, .totals td {
            border: none;
            padding: 5px 10px;
        }
        .totals th {
            text-align: left;
        }
        .totals td {
            text-align: right;
        }
        .grand-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
        }
        .legal-mentions {
            margin-top: 40px;
            font-size: 10px;
            color: #555;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    @php $cp = $invoice->user->companyProfile; @endphp
    <div class="container">
        <h1>{{ $invoice->type === \App\Enums\InvoiceType::Avoir ? 'Avoir' : 'Facture' }}</h1>

        <div class="invoice-header">
            <div class="company-details">
                <strong>{{ $cp->name }}</strong><br>
                @if($cp->legal_form)
                    {{ $cp->legal_form }}
                    @if($cp->share_capital)
                        au capital de {{ number_format($cp->share_capital, 0, ',', ' ') }} &euro;
                    @endif
                    <br>
                @endif
                {{ $cp->address }}<br>
                {{ $cp->zip_code }} {{ $cp->city }}<br>
                @if($cp->siret)
                    SIRET: {{ $cp->siret }}<br>
                @endif
                @if($cp->rcs_number)
                    RCS: {{ $cp->rcs_number }}<br>
                @endif
                @if($cp->vat_number)
                    TVA Intracom.: {{ $cp->vat_number }}
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
        </div>

        <div class="invoice-details">
            <p><strong>N&deg; {{ $invoice->type === \App\Enums\InvoiceType::Avoir ? "d'avoir" : 'de facture' }} :</strong> {{ $invoice->invoice_number }}</p>
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

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Quantité</th>
                    <th class="text-right">Prix Unitaire HT</th>
                    <th class="text-right">Taux TVA</th>
                    <th class="text-right">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->lineItems as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                    <td class="text-right">{{ number_format($item->vat_rate, 2, ',', ' ') }} %</td>
                    <td class="text-right">{{ number_format($item->quantity * $item->unit_price, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <th>Sous-total HT</th>
                    <td>{{ number_format($invoice->subtotal, 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <th>Montant TVA</th>
                    <td>{{ number_format($invoice->vat_amount, 2, ',', ' ') }} €</td>
                </tr>
                <tr class="grand-total">
                    <th>Total TTC</th>
                    <td>{{ number_format($invoice->total, 2, ',', ' ') }} €</td>
                </tr>
            </table>
        </div>

        <div class="legal-mentions">
            <p>
                @if($cp->is_vat_exempt ?? false)
                    TVA non applicable, art. 293 B du CGI.<br>
                @endif
                Conditions de paiement : {{ $cp->default_payment_terms ?? 'Paiement à réception de la facture.' }}<br>
                Aucun escompte pour paiement anticipé.<br>
                {{ $cp->late_payment_penalty_rate ?? 'En cas de retard de paiement, une indemnité forfaitaire pour frais de recouvrement de 40€ sera appliquée.' }}
                @if($cp->custom_legal_mentions)
                    <br>{{ $cp->custom_legal_mentions }}
                @endif
            </p>
        </div>
    </div>
</body>
</html>
