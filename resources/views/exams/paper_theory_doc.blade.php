<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>Theory Paper — {{ optional($exam->subject)->name }}</title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 12pt; color: #000; }
        .head { text-align: center; margin-bottom: 18pt; }
        .head h1 { font-size: 16pt; margin: 0; text-transform: uppercase; }
        .head p { margin: 2pt 0; font-size: 10pt; }
        .meta { margin: 12pt 0; font-size: 11pt; }
        .meta td { padding: 2pt 8pt 2pt 0; }
        .title { text-align: center; font-weight: bold; text-decoration: underline; margin: 14pt 0; font-size: 13pt; }
        .instr { font-style: italic; margin-bottom: 12pt; }
        ol { margin-left: 18pt; }
        li { margin-bottom: 14pt; }
        .marks { font-size: 10pt; color: #333; }
    </style>
</head>
<body>
    <div class="head">
        <h1>{{ $college->name ?? config('app.name', 'College') }}</h1>
        @if($college?->address)<p>{{ $college->address }}</p>@endif
    </div>

    <table class="meta">
        <tr><td><strong>Course:</strong></td><td>{{ optional($exam->subject)->name }} ({{ optional($exam->subject)->course_code }})</td></tr>
        <tr><td><strong>Programme:</strong></td><td>{{ optional(optional($exam->subject)->program)->name ?? '—' }}</td></tr>
        <tr><td><strong>Level / Semester:</strong></td><td>{{ $exam->level ? (is_numeric($exam->level) ? 'Level '.$exam->level : $exam->level) : (optional($exam->subject)->level ? 'Level '.$exam->subject->level : '—') }} · {{ $exam->term ?: '—' }} {{ $exam->session ? '('.$exam->session.')' : '' }}</td></tr>
        <tr><td><strong>Duration:</strong></td><td>{{ $exam->duration_minutes ? $exam->duration_minutes.' minutes' : '—' }}</td></tr>
    </table>

    <div class="title">{{ $exam->title ?: 'Theory Examination' }} — Theory (Paper-Based) Section</div>

    <p class="instr">{{ $exam->instructions_theory ?: 'Answer the questions as instructed.' }}</p>

    @php $theory = $exam->questions->where('type', 'theory')->sortBy('theory_number')->values(); @endphp
    @if($theory->count())
        <ol>
            @foreach($theory as $q)
                <li>
                    {{ $q->question_text }}
                    @if(!is_null($q->marks))<span class="marks">({{ $q->marks }} marks)</span>@endif
                </li>
            @endforeach
        </ol>
    @else
        <p><em>No theory questions were set for this paper.</em></p>
    @endif
</body>
</html>
