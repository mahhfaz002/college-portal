<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; color:#111; font-size:12px; }
    .head { text-align:center; border-bottom:2px solid #000; padding-bottom:10px; margin-bottom:16px; }
    .head h1 { margin:0; font-size:20px; text-transform:uppercase; }
    .title { text-align:center; font-weight:bold; text-decoration:underline; margin:12px 0; font-size:15px; }
    table { width:100%; border-collapse:collapse; margin:12px 0; }
    th, td { border:1px solid #000; padding:6px 8px; }
    .right { text-align:right; }
    .net { font-weight:bold; color:#047857; font-size:16px; }
    .meta td { border:none; padding:2px 0; }
</style>
</head>
<body>
    @php $college = \App\Models\College::withoutGlobalScopes()->find($payslip->college_id); @endphp
    <div class="head">
        <h1>{{ $college->name ?? setting('school_name', 'College') }}</h1>
        <p style="margin:2px 0;">{{ $college->address ?? setting('school_address') }}</p>
        <p style="margin:2px 0;">{{ $college->phone ?? setting('school_phone') }} | {{ $college->email ?? setting('school_email') }}</p>
    </div>
    <div class="title">STAFF PAYSLIP — {{ \Illuminate\Support\Carbon::parse($payslip->month.'-01')->format('F Y') }}</div>

    <table class="meta">
        <tr>
            <td><strong>STAFF:</strong> {{ $payslip->staff->name ?? '' }}<br><strong>ROLE:</strong> {{ ucwords(str_replace('_',' ', $payslip->staff->role ?? '')) }}<br><strong>STAFF ID:</strong> {{ $payslip->staff->staff_id ?? '—' }}</td>
            <td class="right"><strong>STATUS:</strong> {{ strtoupper($payslip->status) }}<br><strong>PAID:</strong> {{ $payslip->paid_at?->format('d/m/Y') ?? '—' }}</td>
        </tr>
    </table>

    @php $cur = setting('currency_symbol','N'); @endphp
    <table>
        <tr><th>Earnings</th><th class="right">Amount</th></tr>
        <tr><td>Basic Salary</td><td class="right">{{ $cur }}{{ number_format($payslip->basic_salary,2) }}</td></tr>
        <tr><td>Allowances</td><td class="right">{{ $cur }}{{ number_format($payslip->allowances,2) }}</td></tr>
    </table>
    <table>
        <tr><th>Deductions</th><th class="right">Amount</th></tr>
        @forelse($payslip->deductions ?? [] as $d)
            <tr><td>{{ $d['nature'] ?? '' }}</td><td class="right">{{ $cur }}{{ number_format($d['amount'] ?? 0,2) }}</td></tr>
        @empty
            <tr><td colspan="2" style="text-align:center; color:#888;">No itemised deductions</td></tr>
        @endforelse
        <tr><td>Tax ({{ rtrim(rtrim(number_format($payslip->tax,2),'0'),'.') }}%)</td><td class="right">{{ $cur }}{{ number_format($payslip->taxAmount(),2) }}</td></tr>
        <tr><td>Contributory Savings ({{ rtrim(rtrim(number_format($payslip->contributory_savings,2),'0'),'.') }}%)</td><td class="right">{{ $cur }}{{ number_format($payslip->savingsAmount(),2) }}</td></tr>
        <tr><td class="right"><strong>Total Deductions</strong></td><td class="right">{{ $cur }}{{ number_format($payslip->totalDeductions() + $payslip->taxAmount() + $payslip->savingsAmount(),2) }}</td></tr>
    </table>
    <table>
        <tr><td class="right"><strong>NET SALARY</strong></td><td class="right net">{{ $cur }}{{ number_format($payslip->net_salary,2) }}</td></tr>
    </table>
    <p style="text-align:center; font-style:italic; color:#888; margin-top:30px;">This payslip is system-generated and stored on the staff record.</p>
</body>
</html>
