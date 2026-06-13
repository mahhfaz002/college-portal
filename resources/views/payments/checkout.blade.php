<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout — {{ $invoice->reference }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php $brand = $college->primary_color ?? '#1e3a8a'; @endphp
    <style>
        :root { --brand: {{ $brand }}; }
        * { box-sizing: border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background:#f3f4f6; margin:0; padding:24px; color:#1f2937; }
        .sheet { max-width:560px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,.08); }
        .head { display:flex; gap:14px; align-items:center; padding:22px 24px; background:var(--brand); color:#fff; }
        .logo { width:52px; height:52px; border-radius:10px; background:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; color:var(--brand); font-weight:800; overflow:hidden; flex:none; }
        .logo img { width:100%; height:100%; object-fit:contain; }
        .head h1 { margin:0; font-size:16px; } .head p { margin:2px 0 0; font-size:12px; opacity:.9; }
        .body { padding:24px; }
        .eyebrow { font-size:11px; letter-spacing:.12em; text-transform:uppercase; color:#9ca3af; font-weight:700; }
        .title { font-size:20px; font-weight:800; margin:2px 0 2px; }
        .muted { color:#6b7280; font-size:13px; margin:0; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        td { padding:11px 0; border-bottom:1px solid #f0f0f0; font-size:14px; }
        td.k { color:#4b5563; } td.v { text-align:right; font-weight:600; white-space:nowrap; }
        td.sub { color:#6b7280; font-size:13px; } td.sub.v { font-weight:500; }
        .hint { font-size:11px; color:#9ca3af; }
        tr.total td { border-bottom:none; padding-top:16px; font-size:18px; font-weight:800; }
        tr.total td.v { color:var(--brand); }
        .pay { display:block; text-align:center; margin-top:22px; background:var(--brand); color:#fff; text-decoration:none; padding:14px; border-radius:10px; font-weight:800; font-size:15px; }
        .pay:hover { filter:brightness(.94); }
        .back { display:block; text-align:center; margin-top:12px; color:#6b7280; font-size:13px; text-decoration:none; }
        .secure { text-align:center; font-size:11px; color:#9ca3af; margin-top:14px; }
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
                <p>Secure online payment</p>
            </div>
        </div>

        <div class="body">
            <p class="eyebrow">Review your payment</p>
            <p class="title">{{ $invoice->description }}</p>
            <p class="muted">{{ ucwords(str_replace('_',' ', $invoice->purpose)) }} · Ref {{ $invoice->reference }}</p>

            <table>
                {{-- Fee item(s). Currently one line per invoice; the layout itemises
                     each component should an invoice ever carry several. --}}
                <tr>
                    <td class="k">{{ $invoice->description }}</td>
                    <td class="v">{{ money($breakdown['base']) }}</td>
                </tr>
                <tr>
                    <td class="sub">Portal convenience fee
                        <div class="hint">Flat fee per transaction</div>
                    </td>
                    <td class="sub v">{{ money($breakdown['convenience']) }}</td>
                </tr>
                <tr>
                    <td class="sub">Payment processing fee
                        <div class="hint">Paystack gateway charge</div>
                    </td>
                    <td class="sub v">{{ money($breakdown['service']) }}</td>
                </tr>
                <tr class="total">
                    <td class="k">Total to pay</td>
                    <td class="v">{{ money($breakdown['total']) }}</td>
                </tr>
            </table>

            <a href="{{ route('payments.initialize', $invoice) }}" class="pay">Proceed to Payment →</a>
            <a href="{{ url()->previous() }}" class="back">Cancel and go back</a>
            <p class="secure">🔒 Payments are processed securely by Paystack.</p>
        </div>
    </div>
</body>
</html>
