<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admission Acceptance Form — {{ $applicant->full_name }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing:border-box; font-family:'Georgia','Times New Roman',serif; }
        body { background:#f3f4f6; margin:0; padding:24px; color:#111827; }
        .page { max-width:800px; margin:0 auto; background:#fff; padding:48px 56px; box-shadow:0 10px 30px rgba(0,0,0,.08); }
        .head { text-align:center; border-bottom:3px double #1e3a8a; padding-bottom:16px; }
        .head .logo { width:70px; height:70px; object-fit:contain; }
        .head h1 { margin:8px 0 2px; color:#1e3a8a; font-size:20px; }
        .head p { margin:0; font-size:12px; color:#555; }
        h2 { text-align:center; text-transform:uppercase; letter-spacing:1px; font-size:15px; text-decoration:underline; margin:24px 0; }
        p.body { font-size:14px; line-height:1.8; text-align:justify; }
        .tbl { width:100%; border-collapse:collapse; margin:16px 0; font-size:13px; }
        .tbl td { padding:8px; border:1px solid #ddd; } .tbl td.k { background:#f9fafb; font-weight:bold; width:38%; }
        .field { border-bottom:1px solid #888; min-height:22px; display:inline-block; min-width:60%; }
        .sign { margin-top:40px; font-size:13px; display:flex; justify-content:space-between; }
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
            <p>{{ $college->address ?? '' }}</p>
        </div>

        <h2>Admission Acceptance Form</h2>

        <table class="tbl">
            <tr><td class="k">Full Name</td><td>{{ $applicant->full_name }}</td></tr>
            <tr><td class="k">Admission Number</td><td>{{ $applicant->admission_number }}</td></tr>
            <tr><td class="k">Programme</td><td>{{ $program->name ?? '' }}</td></tr>
            <tr><td class="k">Department</td><td>{{ $program->department->name ?? '' }}</td></tr>
        </table>

        <p class="body">
            I, <span class="field">&nbsp;</span>, hereby accept the offer of provisional admission given to me by
            {{ $college->name ?? 'the College' }} into the above-named programme. I undertake to abide by the rules
            and regulations of the College, and I confirm that the information I have provided is true and correct.
        </p>

        <div class="sign">
            <div>Signature: <span class="field" style="min-width:160px">&nbsp;</span></div>
            <div>Date: <span class="field" style="min-width:120px">&nbsp;</span></div>
        </div>

        <p class="body" style="margin-top:32px; font-size:12px; color:#666;">
            <em>Instructions: Print this form, complete and sign it, then upload the signed copy in the
            registration documents section of your dashboard.</em>
        </p>
    </div>

    <div class="actions">
        <button class="btn" onclick="window.print()">🖨️ Print Form</button>
    </div>
</body>
</html>
