<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Change of Course — Acceptance Letter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Georgia, serif; font-size: 13px; color: #111; background: #f3f4f6; margin: 0; padding: 24px; line-height: 1.6; }
        .sheet { max-width: 800px; margin: 0 auto; background: #fff; padding: 48px 56px; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .head { text-align: center; border-bottom: 3px double #1f2937; padding-bottom: 12px; }
        .head .logo { width: 72px; height: 72px; object-fit: contain; }
        .head h1 { margin: 8px 0 0; font-size: 24px; text-transform: uppercase; letter-spacing: .5px; }
        .head p { margin: 2px 0; font-size: 12px; color: #374151; }
        .office { text-align: center; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; font-size: 13px; margin: 12px 0 26px; }
        .meta { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 22px; }
        .addr strong { display: block; }
        .subject { font-weight: bold; text-decoration: underline; margin: 18px 0; font-size: 16px; }
        p.body { margin: 12px 0; text-align: justify; }
        .sign { margin-top: 52px; }
        .sign img { height: 60px; object-fit: contain; display: block; margin-bottom: 4px; }
        .sign .line { border-top: 1px solid #111; width: 240px; padding-top: 4px; font-weight: bold; }
        .actions { max-width: 800px; margin: 16px auto 0; text-align: center; }
        .btn { background: #1e3a8a; color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; font-family: system-ui, sans-serif; }
        @media print { body { background: #fff; padding: 0; } .actions { display: none; } .sheet { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            @if($college && $college->logo_path)<img class="logo" src="{{ media_url($college->logo_path) }}" alt="logo">@endif
            <h1>{{ $college->name ?? config('app.name', 'College') }}</h1>
            @if($college?->address)<p>{{ $college->address }}</p>@endif
            <p>@if($college?->phone){{ $college->phone }}@endif @if($college?->email) · {{ $college->email }}@endif</p>
        </div>
        <div class="office">Office of the Registrar</div>

        <div class="meta">
            <div class="addr">
                <strong>{{ optional($cocr->student)->full_name }}</strong>
                {{ optional($cocr->student)->registration_number ?? optional($cocr->student)->admission_number }}<br>
                {{ optional($cocr->currentProgram)->name ?? optional($cocr->student)->class_arm }}<br>
                @if(optional(optional($cocr->currentProgram)->department)->name)Department of {{ $cocr->currentProgram->department->name }}@endif
            </div>
            <div>Date: {{ optional($cocr->decided_at)->format('d F, Y') ?? now()->format('d F, Y') }}</div>
        </div>

        <p>Dear {{ optional($cocr->student)->full_name }},</p>

        <div class="subject">Re: Application for Change of Course</div>

        <p class="body">
            I write with reference to your application for a change of course of study from
            <strong>{{ optional($cocr->currentProgram)->name ?? optional($cocr->student)->class_arm }}</strong>
            to <strong>{{ optional($cocr->requestedProgram)->name }}</strong>@if(optional(optional($cocr->requestedProgram)->department)->name) (Department of {{ $cocr->requestedProgram->department->name }})@endif.
        </p>

        <p class="body">
            I am pleased to inform you that, following the recommendation of the Academic Secretary and the clearance of
            the Heads of the affected departments, your application has been <strong>approved</strong>. Congratulations!
        </p>

        <p class="body">
            To complete your transfer, kindly log in to your student dashboard and pay the
            <strong>registration fee for your new course of study</strong> under the Fees section. Your previous results
            will be retained and matched against the courses of your new department; any outstanding courses will appear
            in your course registration to be taken in the coming semesters.
        </p>

        <p class="body">Accept my congratulations once again.</p>

        <div class="sign">
            @if(!empty($registrarSignature))
                <img src="{{ $registrarSignature }}" alt="Registrar signature">
            @endif
            <div class="line">{{ $registrar->name ?? 'Registrar' }}<br><span style="font-weight:normal;font-size:11px;">Registrar</span></div>
        </div>
    </div>

    <div class="actions">
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
