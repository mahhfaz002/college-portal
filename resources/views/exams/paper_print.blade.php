<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ optional($exam->subject)->course_code }} — Question Paper</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $objectives = $exam->questions->where('type','objective')->values();
        $theory = $exam->questions->where('type','theory')->sortBy('theory_number')->values();
    @endphp
    <style>
        * { box-sizing: border-box; font-family: 'Times New Roman', Georgia, serif; }
        body { margin:0; padding:32px; color:#111; background:#f3f4f6; }
        .sheet { max-width:800px; margin:0 auto; background:#fff; padding:40px; box-shadow:0 6px 24px rgba(0,0,0,.08); }
        .head { text-align:center; border-bottom:2px solid #111; padding-bottom:12px; margin-bottom:18px; }
        .head h1 { margin:0; font-size:20px; text-transform:uppercase; }
        .head p { margin:3px 0; font-size:13px; }
        .meta { display:flex; justify-content:space-between; font-size:13px; margin-bottom:18px; }
        h2.section { font-size:15px; text-transform:uppercase; border-bottom:1px solid #999; margin:22px 0 10px; padding-bottom:3px; }
        ol { padding-left:22px; }
        .q { margin-bottom:12px; font-size:14px; }
        .opts { list-style:none; padding-left:14px; margin:5px 0 0; }
        .opts li { font-size:13px; margin:2px 0; }
        .instr { font-size:12px; font-style:italic; color:#444; margin-bottom:6px; }
        .actions { max-width:800px; margin:18px auto 0; text-align:center; }
        .btn { background:#111; color:#fff; border:none; padding:10px 22px; border-radius:6px; font-weight:bold; cursor:pointer; }
        @media print { body{background:#fff;padding:0;} .sheet{box-shadow:none;} .actions{display:none;} }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <h1>{{ $college->name ?? config('app.name') }}</h1>
            <p>{{ optional($exam->examCycle)->title ?? 'Examinations' }}</p>
            <p><strong>{{ optional($exam->subject)->course_code }} — {{ optional($exam->subject)->name }}</strong></p>
        </div>

        @php $examLevel = $exam->level ?: optional($exam->subject)->level; @endphp
        <div class="meta">
            <span>Programme: {{ optional(optional($exam->subject)->program)->name ?? '—' }}</span>
            <span>Level: {{ $examLevel ? (is_numeric($examLevel) ? 'L'.$examLevel : $examLevel) : '—' }}</span>
            <span>Semester: {{ optional($exam->subject)->semester ?? '—' }}</span>
        </div>
        <div class="meta">
            <span>Term: {{ $exam->term ?: '—' }} {{ $exam->session ? '('.$exam->session.')' : '' }}</span>
            <span>Duration: {{ $exam->duration_minutes ? $exam->duration_minutes.' minutes' : '—' }}</span>
            <span>Total marks: {{ method_exists($exam,'totalMarks') ? $exam->totalMarks() : $exam->questions->sum('marks') }}</span>
        </div>

        @if($objectives->count())
            <h2 class="section">Section A — Objective Questions</h2>
            <p class="instr">{{ $exam->instructions_objective ?: 'Answer ALL questions. Choose the correct option.' }}</p>
            <ol>
                @foreach($objectives as $q)
                    <li class="q">
                        {{ $q->question_text }}
                        <ul class="opts">
                            <li>A. {{ $q->option_a }}</li>
                            <li>B. {{ $q->option_b }}</li>
                            @if($q->option_c)<li>C. {{ $q->option_c }}</li>@endif
                            @if($q->option_d)<li>D. {{ $q->option_d }}</li>@endif
                        </ul>
                    </li>
                @endforeach
            </ol>
        @endif

        @if($theory->count())
            <h2 class="section">Section B — Theory Questions</h2>
            <p class="instr">{{ $exam->instructions_theory ?: 'Answer the questions as instructed.' }}</p>
            <ol>
                @foreach($theory as $q)
                    <li class="q">{{ $q->question_text }}</li>
                @endforeach
            </ol>
        @endif

        @if(!$objectives->count() && !$theory->count())
            <p>No questions in this paper.</p>
        @endif
    </div>

    <div class="actions">
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
