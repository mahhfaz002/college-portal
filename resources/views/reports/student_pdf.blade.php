<!DOCTYPE html>
<html>
<head>
    <title>Statement of Result</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .student-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ current_college()->name ?? config('app.name') }}</h1>
        <h3>First Semester Statement of Result - 2025/2026</h3>
    </div>

    <div class="student-info">
        <p><strong>Name:</strong> {{ $student->full_name }}</p>
        <p><strong>Admission No:</strong> {{ $student->admission_number }}</p>
        <p><strong>Programme:</strong> {{ $student->class_arm }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Course</th>
                <th class="text-center">CA (40)</th>
                <th class="text-center">Exam (60)</th>
                <th class="text-center">Total (100)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($scores as $score)
            <tr>
                <td>{{ $score->subject }}</td>
                <td class="text-center">{{ $score->ca_score }}</td>
                <td class="text-center">{{ $score->exam_score }}</td>
                <td class="text-center font-weight-bold">{{ $score->ca_score + $score->exam_score }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">Average Score</td>
                <td class="text-center">{{ number_format($average, 1) }}%</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
