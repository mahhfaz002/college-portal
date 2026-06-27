<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Disciplinary Notice — {{ $case->student->full_name ?? '' }}</title>
    <style>
        body { font-family: 'Times New Roman', serif; color: #1a1a1a; max-width: 800px; margin: 40px auto; padding: 0 40px; line-height: 1.6; }
        .header { text-align: center; border-bottom: 3px double #333; padding-bottom: 16px; margin-bottom: 24px; }
        .header h1 { margin: 0; font-size: 22px; }
        .header p { margin: 2px 0; font-size: 13px; color: #555; }
        .title { text-align: center; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin: 24px 0; text-decoration: underline; }
        .meta { font-size: 14px; margin-bottom: 20px; }
        .meta strong { display: inline-block; width: 140px; }
        .body-text { font-size: 15px; text-align: justify; }
        .resolution { margin-top: 20px; padding: 16px; border-left: 4px solid #333; background: #f8f8f8; }
        .signature { margin-top: 60px; }
        .print-btn { text-align: center; margin: 20px 0; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <div class="print-btn">
        <button onclick="window.print()" style="padding: 8px 24px; font-size: 14px; cursor: pointer;">Print</button>
    </div>

    <div class="header">
        <h1>{{ $college->name ?? 'College' }}</h1>
        <p>{{ $college->address ?? '' }}</p>
        <p>Office of Student Affairs</p>
    </div>

    <p class="meta"><strong>Date:</strong> {{ $case->resolution_date?->format('F d, Y') ?? now()->format('F d, Y') }}</p>

    <div class="title">Notice of {{ ucfirst($case->category) }} Resolution</div>

    <div class="meta">
        <p><strong>Student Name:</strong> {{ $case->student->full_name ?? $case->student_name }}</p>
        <p><strong>Registration No:</strong> {{ $case->student->registration_number ?? '—' }}</p>
        <p><strong>Programme:</strong> {{ $case->student->program->name ?? '—' }}</p>
    </div>

    <div class="body-text">
        <p>This is to formally notify you of the resolution reached by the College authorities concerning the following matter:</p>

        <p><strong>Matter:</strong> {{ $case->description }}</p>

        @if($case->final_resolution)
        <div class="resolution">
            <p style="margin:0;"><strong>Resolution / Decision:</strong></p>
            <p style="margin:8px 0 0;">{{ $case->final_resolution }}</p>
        </div>
        @endif

        <p style="margin-top: 24px;">You are hereby advised to comply with the terms of this resolution. Failure to adhere to the College's rules and guidelines may result in further disciplinary action.</p>
    </div>

    <div class="signature">
        <p>_______________________________</p>
        <p>Registrar / Student Affairs Office</p>
        <p>{{ $college->name ?? 'College' }}</p>
    </div>
</body>
</html>
