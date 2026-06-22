<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color:#111; font-size:12px; margin:0; }
        .head { text-align:center; border-bottom:2px solid #111; padding-bottom:8px; margin-bottom:10px; }
        .head h1 { margin:0; font-size:16px; text-transform:uppercase; }
        .head p { margin:1px 0; font-size:11px; }
        .title { text-align:center; font-weight:bold; text-transform:uppercase; margin:8px 0; font-size:13px; }
        table.bio { width:100%; border-collapse:collapse; margin-bottom:10px; }
        table.bio td { padding:3px 4px; font-size:11px; }
        table.bio td.k { color:#555; width:120px; }
        table.bio td.v { font-weight:bold; }
        table.courses { width:100%; border-collapse:collapse; margin-top:4px; }
        table.courses th, table.courses td { border:1px solid #333; padding:5px 6px; font-size:11px; }
        table.courses th { background:#eee; text-transform:uppercase; font-size:10px; }
        .sem { font-weight:bold; background:#f3f3f3; text-transform:uppercase; font-size:11px; }
        .tot td { font-weight:bold; }
        .sign { margin-top:34px; width:100%; }
        .sign td { width:50%; font-size:11px; padding-top:18px; }
        .line { border-top:1px solid #111; padding-top:3px; width:75%; }
    </style>
</head>
<body>
    <div class="head">
        <h1>{{ $college->name ?? 'College' }}</h1>
        @if($college?->address)<p>{{ $college->address }}</p>@endif
        <p>{{ $college->email }} {{ $college->phone ? ' · '.$college->phone : '' }}</p>
    </div>

    <div class="title">Course Registration Form — {{ $session }}</div>

    <table class="bio">
        <tr>
            <td class="k">Name</td><td class="v">{{ $student->full_name }}</td>
            <td class="k">Reg. Number</td><td class="v">{{ $student->registration_number }}</td>
        </tr>
        <tr>
            <td class="k">Department</td><td class="v">{{ $student->department->name ?? '—' }}</td>
            <td class="k">Course of Study</td><td class="v">{{ $student->program->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Level</td><td class="v">{{ $student->level }}</td>
            <td class="k">Session</td><td class="v">{{ $session }}</td>
        </tr>
    </table>

    @forelse($bySemester as $semester => $courses)
        <table class="courses">
            <thead>
                <tr><th colspan="5" class="sem">{{ $semester }} — Level {{ $student->level }}</th></tr>
                <tr>
                    <th style="width:34px">S/N</th>
                    <th style="width:90px">Course Code</th>
                    <th>Course Title</th>
                    <th style="width:44px">Unit</th>
                    <th style="width:150px">Course Registration</th>
                </tr>
            </thead>
            <tbody>
                @foreach($courses as $i => $c)
                    <tr>
                        <td style="text-align:center">{{ $i + 1 }}</td>
                        <td>{{ $c->course_code }}</td>
                        <td>{{ $c->name }}</td>
                        <td style="text-align:center">{{ $c->course_unit }}</td>
                        <td></td>{{-- lecturer signs here --}}
                    </tr>
                @endforeach
                <tr class="tot">
                    <td colspan="3" style="text-align:right">{{ $semester }} total units</td>
                    <td style="text-align:center">{{ (int) $courses->sum('course_unit') }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
        <br>
    @empty
        <p style="text-align:center;color:#777">No courses have been published for your programme and level yet. Please check back, or contact your department.</p>
    @endforelse

    @if($totalUnits)
        <p style="text-align:right;font-weight:bold">Total Credit Units: {{ $totalUnits }}</p>
    @endif

    <table class="sign">
        <tr>
            <td><div class="line">Student's Signature / Date</div></td>
            <td><div class="line">HOD's Signature / Date</div></td>
        </tr>
    </table>
</body>
</html>
