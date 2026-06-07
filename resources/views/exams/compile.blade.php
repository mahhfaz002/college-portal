<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compile Results — {{ $exam->title }}</h2>
            <a href="{{ route('exams.show', $exam) }}" class="text-sm text-gray-500 font-bold">← Back</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white p-4 rounded-xl border text-sm text-gray-600 flex justify-between items-center">
                <span>{{ $scores->count() }} result(s). Edit live during the board meeting, then approve to publish to students.</span>
                @can('manage_exams')
                @if($scores->count())
                <form action="{{ route('exams.approve', $exam) }}" method="POST" onsubmit="return confirm('Approve and publish ALL results to students?')">@csrf
                    <button class="bg-green-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-green-700 text-sm">✓ Approve & Publish All</button>
                </form>
                @endif
                @endcan
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                            <th class="p-3">Student</th><th class="p-3">CA</th><th class="p-3">Exam</th>
                            <th class="p-3">Total</th><th class="p-3">Grade</th><th class="p-3">Status</th>
                            @can('manage_exams')<th class="p-3 text-right">Edit</th>@endcan
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scores as $score)
                        <tr class="border-b">
                            <td class="p-3 font-bold">{{ $score->student->full_name ?? '—' }}</td>
                            @can('manage_exams')
                            <form action="{{ route('scores.update', $score) }}" method="POST">
                                @csrf
                                <td class="p-3"><input type="number" name="ca_score" value="{{ $score->ca_score }}" class="w-16 border-gray-300 rounded text-sm"></td>
                                <td class="p-3"><input type="number" name="exam_score" value="{{ $score->exam_score }}" class="w-16 border-gray-300 rounded text-sm"></td>
                                <td class="p-3 font-bold">{{ $score->total ?? ($score->ca_score + $score->exam_score) }}</td>
                                <td class="p-3 font-bold">{{ $score->grade ?? '—' }}</td>
                                <td class="p-3"><span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-gray-100">{{ $score->status }}</span></td>
                                <td class="p-3 text-right"><button class="bg-gray-700 text-white text-xs px-3 py-1 rounded font-bold">Save</button></td>
                            </form>
                            @else
                                <td class="p-3">{{ $score->ca_score }}</td>
                                <td class="p-3">{{ $score->exam_score }}</td>
                                <td class="p-3 font-bold">{{ $score->total ?? ($score->ca_score + $score->exam_score) }}</td>
                                <td class="p-3 font-bold">{{ $score->grade ?? '—' }}</td>
                                <td class="p-3"><span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-gray-100">{{ $score->status }}</span></td>
                            @endcan
                        </tr>
                        @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-400 italic">No graded results forwarded yet. Subject teacher must submit grades first.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
