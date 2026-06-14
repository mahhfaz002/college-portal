<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 12px; }
    .head { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 16px; }
    .head h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
    .title { text-align: center; font-weight: bold; text-decoration: underline; margin: 12px 0; font-size: 15px; }
    table { width: 100%; border-collapse: collapse; margin: 12px 0; }
    th, td { border: 1px solid #000; padding: 8px; }
    .right { text-align: right; }
    .meta td { border: none; padding: 2px 0; }
    .total { font-weight: bold; color: #047857; font-size: 16px; }
    .sign { margin-top: 50px; }
    .sign td { border: none; text-align: center; }
    .line { border-top: 1px solid #000; width: 140px; margin: 0 auto; }
</style>
</head>
<body>
    <div class="head">
        <h1>{{ setting('school_name', 'School') }}</h1>
        <p style="margin:2px 0; font-style:italic;">{{ setting('school_tagline', '') }}</p>
        <p style="margin:2px 0;">{{ setting('school_address') }}</p>
        <p style="margin:2px 0;">{{ setting('school_phone') }} | {{ setting('school_email') }}</p>
    </div>

    <div class="title">OFFICIAL PAYMENT RECEIPT</div>

    <table class="meta">
        <tr>
            <td>
                <strong>STUDENT:</strong> {{ $payment->student->full_name }}<br>
                <strong>ADMISSION NO:</strong> {{ $payment->student->admission_number }}<br>
                <strong>PROGRAMME:</strong> {{ $payment->student->class_arm }}
            </td>
            <td class="right">
                <strong>RECEIPT NO:</strong> #{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}<br>
                <strong>DATE:</strong> {{ $payment->created_at->format('d/m/Y') }}<br>
                <strong>METHOD:</strong> {{ $payment->payment_method }}
            </td>
        </tr>
    </table>

    <table>
        <thead><tr><th>Description</th><th class="right">Amount ({{ setting('currency_symbol','N') }})</th></tr></thead>
        <tbody>
            <tr><td style="height:60px; vertical-align:top;">{{ $payment->description ?? 'College Fees Payment' }}</td><td class="right">{{ number_format($payment->amount, 2) }}</td></tr>
            <tr><td class="right"><strong>TOTAL PAID</strong></td><td class="right total">{{ number_format($payment->amount, 2) }}</td></tr>
        </tbody>
    </table>

    <p><strong>Remaining balance:</strong> {{ setting('currency_symbol','N') }}{{ number_format($payment->student->fees_balance, 2) }}</p>

    <table class="sign">
        <tr>
            <td><div class="line"></div>Bursar's Signature</td>
            <td><div class="line"></div>Student / Parent</td>
        </tr>
    </table>
    <p style="text-align:center; font-style:italic; color:#888; margin-top:20px;">Thank you for your payment. Keep this receipt safe.</p>
</body>
</html>
