<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $subscription->invoice_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #113f2c;
            font-size: 12px;
            margin: 0;
            padding: 24px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .header > div {
            display: table-cell;
            vertical-align: top;
        }
        .right {
            text-align: right;
        }
        .brand {
            color: #006b42;
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }
        .sub {
            color: #466958;
            margin-top: 4px;
        }
        .chip {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 4px;
            background: #f7efd9;
            color: #8a6a24;
            font-weight: 700;
            margin-top: 8px;
        }
        .card {
            border: 1px solid #dbe8df;
            border-radius: 8px;
            margin-bottom: 14px;
        }
        .card h4 {
            margin: 0;
            padding: 10px 12px;
            background: #f4f8f5;
            border-bottom: 1px solid #dbe8df;
            color: #0f3a28;
            font-size: 13px;
        }
        .card .content {
            padding: 10px 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            border: 1px solid #dbe8df;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f4f8f5;
            color: #0f3a28;
        }
        .amount {
            font-size: 18px;
            color: #0f3a28;
            font-weight: 700;
        }
        .muted {
            color: #466958;
        }
    </style>
</head>
<body>
<div class="header">
    <div>
        <p class="brand">ZuriTours ERP</p>
        <p class="sub">Platform Billing Invoice</p>
    </div>
    <div class="right">
        <div class="chip">Invoice {{ $subscription->invoice_number }}</div>
        <p class="muted" style="margin: 8px 0 0;">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</p>
    </div>
</div>

<div class="card">
    <h4>Billed To</h4>
    <div class="content">
        <strong>{{ $company->name ?? 'Company' }}</strong><br>
        <span class="muted">{{ $company->email ?? 'N/A' }}</span><br>
        <span class="muted">Code: {{ $company->company_code ?? 'N/A' }}</span>
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

        <p style="margin-top: 14px;" class="amount">
            Total Paid: {{ $subscription->currency }} {{ number_format((float) $subscription->amount_paid, 2) }}
        </p>
        <p class="muted">
            Payment Date: {{ optional($subscription->payment_date)->format('Y-m-d') ?? 'N/A' }}
        </p>
        <p class="muted">
            Service Period: {{ optional($subscription->starts_at)->format('Y-m-d') }} to {{ optional($subscription->ends_at)->format('Y-m-d') }}
        </p>
    </div>
</div>

<p class="muted" style="font-size: 11px; margin-top: 16px;">
    This is a computer-generated invoice and is valid without signature.
</p>
</body>
</html>
