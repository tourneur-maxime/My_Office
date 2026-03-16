<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }
        .company-info, .client-info {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
        .invoice-details {
            margin: 20px 0;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .totals {
            text-align: right;
            margin-top: 20px;
        }
        .legal-mentions {
            margin-top: 40px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FACTURE</h1>
        <p>{{ $invoice->invoice_number }}</p>
    </div>

    <div class="company-info">
        <h3>{{ $companyProfile->name ?? 'Votre Entreprise' }}</h3>
        <p>{{ $companyProfile->address ?? '' }}</p>
        @if(isset($companyProfile->siret))
            <p>SIRET: {{ $companyProfile->siret }}</p>
        @endif
    </div>

    <div class="client-info">
        <h3>Client</h3>
        <p><strong>{{ $invoice->client->name }}</strong></p>
        <p>{{ $invoice->client->company }}</p>
        <p>{{ $invoice->client->address }}</p>
    </div>

    <div class="invoice-details">
        <p><strong>Date:</strong> {{ $invoice->created_at->format('d/m/Y') }}</p>
        <p><strong>Statut:</strong> {{ $invoice->status }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantité</th>
                <th>Prix Unitaire</th>
                <th>TVA</th>
                <th>Total TTC</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lineItems as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                <td>{{ $item->vat_rate }}%</td>
                <td>{{ number_format($item->total, 2, ',', ' ') }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p><strong>Sous-total HT:</strong> {{ number_format($invoice->subtotal, 2, ',', ' ') }} €</p>
        <p><strong>TVA:</strong> {{ number_format($invoice->vat_amount, 2, ',', ' ') }} €</p>
        <p><strong>TOTAL TTC:</strong> {{ number_format($invoice->total, 2, ',', ' ') }} €</p>
    </div>

    <div class="legal-mentions">
        <h4>Mentions légales</h4>

        @if(isset($legalMentions['basic']))
        <p style="white-space: pre-line;">{{ $legalMentions['basic'] }}</p>
        @endif

        @if(isset($legalMentions['vat_exempt']))
        <p><strong>{{ $legalMentions['vat_exempt'] }}</strong></p>
        @endif

        @if(isset($legalMentions['auto_entrepreneur']))
        <p>{{ $legalMentions['auto_entrepreneur'] }}</p>
        @endif

        @if(isset($legalMentions['late_payment']))
        <p style="white-space: pre-line;">{{ $legalMentions['late_payment'] }}</p>
        @endif

        @if(isset($legalMentions['custom']))
        <p style="white-space: pre-line;">{{ $legalMentions['custom'] }}</p>
        @endif
    </div>
</body>
</html>
