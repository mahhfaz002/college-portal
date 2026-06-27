<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admission Letter — {{ $applicant->full_name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing:border-box; font-family:'Georgia','Times New Roman',serif; }
        body { background:#f3f4f6; margin:0; padding:24px; color:#111827; }
        .page { max-width:800px; margin:0 auto; background:#fff; padding:48px 56px; box-shadow:0 10px 30px rgba(0,0,0,.08); }
        .head { text-align:center; border-bottom:3px double #1e3a8a; padding-bottom:16px; margin-bottom:8px; }
        .head .logo { width:70px; height:70px; border-radius:8px; object-fit:contain; }
        .head h1 { margin:8px 0 2px; color:#1e3a8a; font-size:22px; }
        .head p { margin:0; font-size:12px; color:#555; }
        .meta { display:flex; justify-content:space-between; font-size:13px; margin:24px 0; }
        h2 { text-align:center; text-transform:uppercase; letter-spacing:1px; font-size:15px; text-decoration:underline; margin:24px 0; }
        p.body { font-size:14px; line-height:1.8; text-align:justify; }
        .tbl { width:100%; border-collapse:collapse; margin:16px 0; font-size:13px; }
        .tbl td { padding:6px 8px; border:1px solid #ddd; } .tbl td.k { background:#f9fafb; font-weight:bold; width:38%; }
        .sign { margin-top:48px; font-size:13px; }
        .actions { max-width:800px; margin:16px auto 0; text-align:center; }
        .btn { background:#1e3a8a; color:#fff; border:none; padding:10px 22px; border-radius:8px; font-weight:700; cursor:pointer; font-family:sans-serif; }
        @media print { body{background:#fff;padding:0;} .actions{display:none;} .page{box-shadow:none;} }
    </style>
</head>
<body>
    <div class="page">
        <div class="head">
            @if($college && $college->logo_path)<img class="logo" src="{{ media_url($college->logo_path) }}" alt="logo">@endif
            <h1>{{ $college->name ?? config('app.name') }}</h1>
            <p>{{ $college->address ?? '' }} @if($college?->phone) · Tel: {{ $college->phone }} @endif @if($college?->email) · {{ $college->email }} @endif</p>
        </div>

        <div class="meta">
            <span>Ref: {{ $applicant->admission_number }}</span>
            <span>Date: {{ now()->format('d F Y') }}</span>
        </div>

        <p class="body">
            {{ $applicant->full_name }}<br>
            {{ $applicant->address }}
        </p>

        <h2>Offer of Provisional Admission</h2>

        <p class="body">
            Dear {{ $applicant->first_name }},
        </p>
        <p class="body">
            I am pleased to inform you that, following the consideration of your application, the management of
            <strong>{{ $college->name ?? 'the College' }}</strong> has offered you <strong>provisional admission</strong>
            into the programme stated below for the {{ now()->year }}/{{ now()->year + 1 }} academic session.
        </p>

        <table class="tbl">
            <tr><td class="k">Admission Number</td><td>{{ $applicant->admission_number }}</td></tr>
            <tr><td class="k">Programme</td><td>{{ $program->name ?? '' }}</td></tr>
            <tr><td class="k">Department</td><td>{{ $program->department->name ?? '' }}</td></tr>
            <tr><td class="k">Level</td><td>100 (First Year)</td></tr>
        </table>

        <p class="body">
            This admission is provisional and subject to your payment of the prescribed registration fee, the
            submission and verification of your credentials by your Head of Department, and your meeting all the
            requirements of the College. You are required to accept this offer, pay the registration fee, and complete
            your registration on or before the resumption date.
        </p>

        <p class="body">Please accept my congratulations.</p>

        <div class="sign">
            <p>Yours faithfully,</p>
            @if(!empty($registrarSignature))
                <img src="{{ $registrarSignature }}" alt="Registrar signature" style="height:60px;object-fit:contain;display:block;margin:6px 0;">
            @else
                <br><br>
            @endif
            <p style="margin:0;">______________________________<br>
            <strong>Registrar</strong><br>
            For: {{ $college->name ?? 'the College' }}</p>
        </div>
    </div>

    <div class="actions">
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
