<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis {{ $quote->quote_number }}</title>
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
        .quote-details {
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
        .validity {
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DEVIS</h1>
        <p>{{ $quote->quote_number }}</p>
    </div>

    <div class="company-info">
        <h3>{{ $companyProfile->name ?? 'Votre Entreprise' }}</h3>
        <p>{{ $companyProfile->address ?? '' }}</p>
    </div>

    <div class="client-info">
        <h3>Client</h3>
        <p><strong>{{ $quote->client->name }}</strong></p>
        <p>{{ $quote->client->company }}</p>
        <p>{{ $quote->client->address }}</p>
    </div>

    <div class="quote-details">
        <p><strong>Date:</strong> {{ $quote->created_at->format('d/m/Y') }}</p>
        <p><strong>Statut:</strong> {{ $quote->status->value }}</p>
        @if($quote->expires_at)
            <p><strong>Valide jusqu'au:</strong> {{ $quote->expires_at->format('d/m/Y') }}</p>
        @endif
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
            @foreach($quote->lineItems as $item)
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
        <p><strong>Sous-total HT:</strong> {{ number_format($quote->subtotal, 2, ',', ' ') }} €</p>
        <p><strong>TVA:</strong> {{ number_format($quote->vat_amount, 2, ',', ' ') }} €</p>
        <p><strong>TOTAL TTC:</strong> {{ number_format($quote->total, 2, ',', ' ') }} €</p>
    </div>

    <div class="validity">
        <p>Ce devis est valable {{ $quote->expires_at ? 'jusqu\'au ' . $quote->expires_at->format('d/m/Y') : '30 jours' }}.</p>
    </div>
</body>
</html>
