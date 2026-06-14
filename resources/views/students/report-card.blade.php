<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto bg-white p-10 shadow-lg border-t-8 border-blue-600" id="report-card">

            <div class="text-center mb-8">
                <h1 class="text-3xl font-black uppercase text-blue-800">{{ $school['name'] ?? 'School Portal' }}</h1>
                <p class="font-bold text-gray-600 italic">{{ $school['tagline'] ?? '' }}</p>
                <p class="text-sm">Continuous Assessment & Terminal Report Sheet</p>
                <div class="inline-block bg-blue-100 px-4 py-1 rounded-full mt-2 font-bold text-blue-800">
                    {{ $term }} | {{ $session }} Session
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6 border-b pb-6">
                <div>
                    <p class="text-sm"><strong>STUDENT:</strong> {{ $student->full_name }}</p>
                    <p class="text-sm"><strong>ADMISSION NO:</strong> {{ $student->admission_number }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm"><strong>CLASS:</strong> {{ $student->class_arm }}</p>
                    <p class="text-sm"><strong>DATE:</strong> {{ now()->format('d/m/Y') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-blue-50 p-3 rounded border border-blue-200 text-center">
                    <p class="text-[10px] text-blue-600 uppercase font-bold">Class Position</p>
                    <p class="text-lg font-black text-blue-800">
                        {{ $position }}{{ $position == 1 ? 'st' : ($position == 2 ? 'nd' : ($position == 3 ? 'rd' : 'th')) }}
                    </p>
                </div>

                <div class="bg-green-50 p-3 rounded border border-green-200 text-center">
                    <p class="text-[10px] text-green-600 uppercase font-bold">Attendance</p>
                    <p class="text-lg font-black text-green-800">{{ $daysPresent }} / {{ $totalDays }}</p>
                </div>

                <div class="bg-gray-50 p-3 rounded border border-gray-200 text-center">
                    <p class="text-[10px] text-gray-600 uppercase font-bold">Total Marks</p>
                    <p class="text-lg font-black text-gray-800">{{ $scores->sum(fn($s) => $s->ca_score + $s->exam_score) }}</p>
                </div>

                <div class="bg-gray-50 p-3 rounded border border-gray-200 text-center">
                    <p class="text-[10px] text-gray-600 uppercase font-bold">Class Size</p>
                    <p class="text-lg font-black text-gray-800">{{ $totalInClass }} Students</p>
                </div>
            </div>

            <table class="w-full border-collapse border border-gray-400 text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-400 p-2 text-left">Course</th>
                        <th class="border border-gray-400 p-2 text-center">CA (40)</th>
                        <th class="border border-gray-400 p-2 text-center">Exam (60)</th>
                        <th class="border border-gray-400 p-2 text-center">Total (100)</th>
                        <th class="border border-gray-400 p-2 text-center">Grade</th>
                        <th class="border border-gray-400 p-2 text-left">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = 0; @endphp
                    @forelse($scores as $score)
                        @php
                            $total = $score->ca_score + $score->exam_score;
                            $grandTotal += $total;

                            // Grading is driven by the school's configurable grading scheme.
                            $g = grade_for($total);
                            $grade = $g['grade'];
                            $remark_text = $g['remark'];
                            $color = match($grade) {
                                'A' => 'text-green-600',
                                'B' => 'text-blue-600',
                                'C' => 'text-gray-700',
                                'D', 'E' => 'text-orange-600',
                                default => 'text-red-600',
                            };
                        @endphp
                        <tr>
                            <td class="border border-gray-400 p-2 font-bold">{{ $score->subject->name }}</td>
                            <td class="border border-gray-400 p-2 text-center">{{ $score->ca_score }}</td>
                            <td class="border border-gray-400 p-2 text-center">{{ $score->exam_score }}</td>
                            <td class="border border-gray-400 p-2 text-center font-bold">{{ $total }}</td>
                            <td class="border border-gray-400 p-2 text-center font-black {{ $color }}">{{ $grade }}</td>
                            <td class="border border-gray-400 p-2 italic text-xs">{{ $remark_text }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-4 text-center text-gray-500 italic">No results recorded for this term.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-8 grid grid-cols-2 gap-10">
                <div class="border p-4 rounded bg-gray-50">
                    <p class="text-sm">Total Courses: <strong>{{ $scores->count() }}</strong></p>
                    <p class="text-sm">Average Score: <strong>{{ $scores->count() > 0 ? round($grandTotal / $scores->count(), 2) : 0 }}%</strong></p>
                </div>
                <div class="text-center">
                    <div class="border-b border-black w-48 mx-auto mt-8"></div>
                    <p class="text-xs mt-1">Provost's Signature & Date</p>
                </div>
            </div>

            <div class="hidden print:block mt-6">
                <p class="text-sm"><strong>Lecturer's Comment:</strong> <span class="italic border-b border-gray-400 pb-1">{{ is_object($remark) ? ($remark->teacher_comment ?? 'No comment provided.') : 'No comment provided.' }}</span></p>
            </div>

            <div class="mt-8 border border-gray-300 rounded shadow-sm">
                <div class="bg-gray-100 p-2 text-center border-b border-gray-300">
                    <h4 class="text-xs font-bold uppercase tracking-widest text-gray-700">Grading System & Interpretation</h4>
                </div>
                <table class="w-full text-center text-[10px] border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 border-b border-gray-300">
                            <th class="p-1 border-r border-gray-300">Score Range</th>
                            <th class="p-1 border-r border-gray-300">Grade</th>
                            <th class="p-1 border-r border-gray-300">Classification</th>
                            <th class="p-1">Interpretation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-200"><td>70 – 100%</td><td class="font-bold">A</td><td class="font-semibold text-green-700">Distinction</td><td class="italic">Excellent</td></tr>
                        <tr class="border-b border-gray-200 bg-gray-50"><td>60 – 69%</td><td class="font-bold">B</td><td class="font-semibold text-blue-700">Very Good</td><td class="italic">Very Good</td></tr>
                        <tr class="border-b border-gray-200"><td>50 – 59%</td><td class="font-bold">C</td><td class="font-semibold text-gray-700">Good</td><td class="italic">Good</td></tr>
                        <tr class="border-b border-gray-200 bg-gray-50"><td>45 – 49%</td><td class="font-bold">D</td><td class="font-semibold text-orange-700">Pass</td><td class="italic">Fair</td></tr>
                        <tr class="border-b border-gray-200"><td>40 – 44%</td><td class="font-bold">E</td><td class="font-semibold text-orange-600">Pass</td><td class="italic">Pass</td></tr>
                        <tr class="bg-red-50"><td>0 – 39%</td><td class="font-bold text-red-600">F</td><td class="font-semibold text-red-700">Fail</td><td class="italic">Fail</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-10 flex gap-4 justify-center no-print">
                <button onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded font-bold">🖨️ Print Statement of Result</button>
                <a href="{{ route('students.show', $student->id) }}" class="bg-gray-200 px-6 py-2 rounded font-bold">Back</a>
            </div>
        </div>

        <div class="max-w-4xl mx-auto mt-8 no-print">
            <div class="bg-gray-50 p-4 rounded border">
                <form action="{{ route('students.remark', $student->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="term" value="{{ $term }}">
                    <input type="hidden" name="session" value="{{ $session }}">

                    <label class="block font-bold mb-2 text-gray-700">Course Lecturer's Remark:</label>
                    <textarea name="teacher_comment" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500" rows="2">{{ is_object($remark) ? ($remark->teacher_comment ?? '') : '' }}</textarea>

                    <button type="submit" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded font-bold text-sm hover:bg-blue-700">
                        Save Remark
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #report-card, #report-card * { visibility: visible; }
            #report-card { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
            .no-print { display: none !important; }
            nav, header { display: none !important; }
        }
    </style>
</x-app-layout>
