<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $subscription->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #153a2b;
            font-size: 11.5px;
            margin: 0;
            padding: 20px;
            background: #f4f8f6;
        }

        .invoice-shell {
            border: 1px solid #d7e5de;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
        }

        .hero {
            display: table;
            width: 100%;
            background: #0e4a31;
            color: #ffffff;
        }

        .hero > div {
            display: table-cell;
            vertical-align: top;
            padding: 16px 18px;
        }

        .right {
            text-align: right;
        }

        .brand {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.4px;
        }

        .sub {
            color: #d6e9df;
            margin: 4px 0 0;
            font-size: 11px;
        }

        .chip {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 16px;
            background: #e6f1eb;
            color: #1b4f3a;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .hero-meta {
            margin: 8px 0 0;
            color: #d6e9df;
            font-size: 10.5px;
        }

        .body {
            padding: 16px;
        }

        .card {
            border: 1px solid #dce8e2;
            border-radius: 10px;
            margin-bottom: 12px;
            background: #ffffff;
        }

        .card h4 {
            margin: 0;
            padding: 10px 12px;
            background: #f2f8f4;
            border-bottom: 1px solid #dce8e2;
            color: #0f3a28;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .card .content {
            padding: 11px 12px;
        }

        .meta-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin: 0 0 12px;
        }

        .meta-grid td {
            border: 1px solid #e1ebe6;
            border-radius: 8px;
            padding: 8px 10px;
            vertical-align: top;
            width: 33.33%;
            background: #fafdfb;
        }

        .party-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin-bottom: 12px;
        }

        .party-grid td {
            width: 50%;
            vertical-align: top;
            border: 1px solid #dce8e2;
            border-radius: 10px;
            background: #fbfdfc;
            padding: 10px 12px;
        }

        .party-title {
            margin: 0 0 8px;
            font-size: 10px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #4f6f60;
            font-weight: 700;
        }

        .party-name {
            margin: 0 0 3px;
            font-size: 13px;
            color: #133425;
            font-weight: 700;
        }

        .party-line {
            margin: 0;
            font-size: 11px;
            color: #4f6f60;
            line-height: 1.5;
        }

        .label {
            font-size: 9.5px;
            color: #5a7869;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .value {
            font-size: 12.5px;
            color: #143726;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            border: 1px solid #dce8e2;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f2f8f4;
            color: #0f3a28;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .amount {
            font-size: 17px;
            color: #0f3a28;
            font-weight: 700;
            margin: 12px 0 6px;
        }

        .muted {
            color: #4f6f60;
        }

        .footer-note {
            margin-top: 12px;
            font-size: 10px;
            color: #587868;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $billTo = $subscription->company ?? $company;
@endphp
<div class="invoice-shell">
    <div class="hero">
        <div>
            <p class="brand">ZuriTours ERP</p>
            <p class="sub">Platform Billing Invoice</p>
        </div>
        <div class="right">
            <div class="chip">Invoice {{ $subscription->invoice_number }}</div>
            <p class="hero-meta">Generated: {{ $generatedAt->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="body">
        <div class="card">
            <h4>Parties</h4>
            <div class="content">
                <table class="party-grid">
                    <tr>
                        <td>
                            <p class="party-title">From</p>
                            <p class="party-name">Technoguru Digital Systems Ltd</p>
                            <p class="party-line">Billing Team</p>
                            <p class="party-line">Email: ict@technoguru.co.tz</p>
                        </td>
                        <td>
                            <p class="party-title">Bill To</p>
                            <p class="party-name">{{ $billTo?->name ?? 'N/A' }}</p>
                            <p class="party-line">Email: {{ $billTo?->email ?? 'N/A' }}</p>
                            <p class="party-line">Code: {{ $billTo?->company_code ?? 'N/A' }}</p>
                        </td>
                    </tr>
                </table>

                <table class="meta-grid">
                    <tr>
                        <td>
                            <div class="label">Invoice Number</div>
                            <div class="value">{{ $subscription->invoice_number ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="label">Invoice Date</div>
                            <div class="value">{{ $generatedAt->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <div class="label">Due Date</div>
                            <div class="value">{{ optional($subscription->payment_date)->format('d/m/Y') ?? $generatedAt->format('d/m/Y') }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <h4>Subscription Payment</h4>
            <div class="content">
                <table>
                    <thead>
                    <tr>
                        <th>Description</th>
                        <th>Billing Cycle</th>
                        <th>Payment Method</th>
                        <th>Reference</th>
                        <th>Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{ $plan?->name ?? 'Subscription Plan' }} ({{ $subscription->license_key }})</td>
                        <td>{{ str_replace('_', ' ', ucfirst($subscription->billing_cycle)) }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($subscription->payment_method ?? 'N/A')) }}</td>
                        <td>{{ $subscription->payment_reference ?? 'N/A' }}</td>
                        <td>{{ $subscription->currency }} {{ number_format((float) $subscription->amount_paid, 2) }}</td>
                    </tr>
                    </tbody>
                </table>

                <p class="amount">Total Paid: {{ $subscription->currency }} {{ number_format((float) $subscription->amount_paid, 2) }}</p>
                <p class="muted">Payment Date: {{ optional($subscription->payment_date)->format('d/m/Y') ?? 'N/A' }}</p>
                <p class="muted">Service Period: {{ optional($subscription->starts_at)->format('d/m/Y') }} to {{ optional($subscription->ends_at)->format('d/m/Y') }}</p>
            </div>
        </div>

        <p class="footer-note">This is a computer-generated invoice and is valid without signature.</p>
    </div>
</div>
</body>
</html>
