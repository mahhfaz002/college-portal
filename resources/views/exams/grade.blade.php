<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Grade — {{ $exam->title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('error'))<div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <div class="bg-white p-4 rounded-xl border text-sm text-gray-600 mb-4">
                Subject: <strong>{{ $exam->subject->name ?? '—' }}</strong>. Objective (exam) marks are auto-scored. Enter each student's CA / test mark; totals & grades compute automatically on save and are forwarded to the Exam Officer.
            </div>

            <form action="{{ route('exams.grade.save', $exam) }}" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
                @csrf
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                            <th class="p-3">Student</th>
                            <th class="p-3">Objective (auto)</th>
                            <th class="p-3">Exam mark</th>
                            <th class="p-3">CA / Test</th>
                            <th class="p-3">Prev grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($submissions as $sub)
                        @php $prev = $scores->get($sub->student_id); @endphp
                        <tr class="border-b">
                            <td class="p-3 font-bold">{{ $sub->student->full_name ?? '—' }}</td>
                            <td class="p-3 text-gray-500">{{ $sub->objective_score }} / {{ $sub->max_score }}</td>
                            <td class="p-3">
                                <input type="number" name="exam[{{ $sub->student_id }}]" value="{{ $prev->exam_score ?? $sub->objective_score }}" min="0" class="w-20 border-gray-300 rounded text-sm">
                            </td>
                            <td class="p-3">
                                <input type="number" name="ca[{{ $sub->student_id }}]" value="{{ $prev->ca_score ?? 0 }}" min="0" class="w-20 border-gray-300 rounded text-sm">
                            </td>
                            <td class="p-3">{{ $prev->grade ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="p-8 text-center text-gray-400 italic">No submissions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($submissions->count())
                <div class="p-4 border-t text-right">
                    <button class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 text-sm">Submit Grades to Exam Officer</button>
                </div>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
