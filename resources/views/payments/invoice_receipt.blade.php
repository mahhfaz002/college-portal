<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $invoice->reference }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background:#f3f4f6; margin:0; padding:24px; color:#1f2937; }
        .sheet { max-width:720px; margin:0 auto; background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.08); }
        .head { display:flex; gap:16px; align-items:center; padding:24px; background:linear-gradient(90deg,#1e3a8a,#047857); color:#fff; }
        .logo { width:64px; height:64px; border-radius:10px; background:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; color:#1e3a8a; font-weight:800; overflow:hidden; }
        .logo img { width:100%; height:100%; object-fit:contain; }
        .head h1 { margin:0; font-size:18px; } .head p { margin:2px 0 0; font-size:12px; opacity:.9; }
        .paidtag { margin-left:auto; background:rgba(255,255,255,.2); padding:6px 14px; border-radius:999px; font-weight:700; font-size:13px; }
        .body { padding:24px; }
        .title { font-size:22px; font-weight:800; margin:0 0 4px; }
        .muted { color:#6b7280; font-size:13px; }
        table { width:100%; border-collapse:collapse; margin-top:18px; }
        td { padding:10px 0; border-bottom:1px solid #f0f0f0; font-size:14px; }
        td.k { color:#6b7280; } td.v { text-align:right; font-weight:600; }
        .total { font-size:20px; font-weight:800; color:#047857; }
        .foot { padding:18px 24px; border-top:1px dashed #e5e7eb; font-size:12px; color:#6b7280; text-align:center; }
        .actions { max-width:720px; margin:16px auto 0; text-align:center; }
        .btn { background:#1e3a8a; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-weight:700; cursor:pointer; }
        @media print { body{background:#fff;padding:0;} .actions{display:none;} .sheet{box-shadow:none;} }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <div class="logo">
                @if($college && $college->logo_path)
                    <img src="{{ media_url($college->logo_path) }}" alt="logo">
                @else
                    {{ strtoupper(substr($college->acronym ?? 'C',0,3)) }}
                @endif
            </div>
            <div>
                <h1>{{ $college->name ?? config('app.name') }}</h1>
                <p>{{ $college->address ?? '' }} @if($college?->phone) · {{ $college->phone }} @endif</p>
            </div>
            <span class="paidtag">PAID</span>
        </div>

        <div class="body">
            <p class="title">Payment Receipt</p>
            <p class="muted">Receipt No: {{ $invoice->reference }}</p>

            <table>
                <tr><td class="k">Payer</td><td class="v">{{ optional($invoice->applicant)->full_name ?? optional($invoice->student)->full_name ?? $invoice->payer_email }}</td></tr>
                <tr><td class="k">Email</td><td class="v">{{ $invoice->payer_email }}</td></tr>
                <tr><td class="k">Description</td><td class="v">{{ $invoice->description }}</td></tr>
                <tr><td class="k">Purpose</td><td class="v">{{ ucwords(str_replace('_',' ',$invoice->purpose)) }}</td></tr>
                <tr><td class="k">Date Paid</td><td class="v">{{ optional($invoice->paid_at)->format('d M Y, g:ia') }}</td></tr>
                <tr><td class="k">Method</td><td class="v">{{ ucfirst($invoice->payment_method ?? 'paystack') }} @if($invoice->gateway_reference) ({{ $invoice->gateway_reference }}) @endif</td></tr>
                <tr><td class="k">Fee</td><td class="v">{{ money($invoice->amount) }}</td></tr>
                @if((float) $invoice->convenience_fee > 0)
                    <tr><td class="k">Portal convenience fee</td><td class="v">{{ money($invoice->convenience_fee) }}</td></tr>
                @endif
                @if((float) $invoice->service_fee > 0)
                    <tr><td class="k">Payment processing fee</td><td class="v">{{ money($invoice->service_fee) }}</td></tr>
                @endif
                <tr><td class="k">Total Paid</td><td class="v total">{{ money($invoice->chargeable()) }}</td></tr>
            </table>
        </div>

        <div class="foot">
            This is a computer-generated receipt and is valid without a signature. Thank you.
        </div>
    </div>

    <div class="actions">
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
