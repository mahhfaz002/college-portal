<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Results — {{ $subject->name }} ({{ $subject->course_code }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc ml-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Submission info --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex flex-wrap justify-between items-center gap-3">
                <div>
                    <p class="font-bold text-blue-900">Submitted by: {{ $submission->lecturer->name ?? 'Unknown' }}</p>
                    <p class="text-xs text-blue-700">{{ $submission->submitted_at?->format('M d, Y h:i A') }} &bull; Physical copy due: {{ $submission->physical_copy_deadline?->format('M d, Y h:i A') }}</p>
                </div>
                @if($submission->scan_path)
                    <a href="{{ route('results.officer.scan', $submission) }}" target="_blank"
                       class="bg-blue-600 text-white px-4 py-2 rounded-full text-sm font-bold hover:bg-blue-700">
                        View Scanned Copy
                    </a>
                @endif
            </div>

            <form action="{{ route('results.officer.save', $subject) }}" method="POST"
                  class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200">
                @csrf

                <div class="p-6 bg-indigo-900 text-white flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase text-indigo-300">Course</p>
                        <p class="mt-1 text-lg font-black">{{ $subject->name }}</p>
                        <p class="text-xs text-indigo-300">{{ $subject->course_code }} {{ optional($subject->program)->name }}{{ $subject->level ? ' · L'.$subject->level : '' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold">Max CA: {{ setting('ca_max_score', 40) }} | Max Exam: {{ setting('exam_max_score', 60) }}</p>
                        <p class="text-xs text-indigo-300 italic">{{ $term }} &bull; {{ $session }}</p>
                    </div>
                </div>

                <div class="p-6">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b-2 border-gray-100">
                                <th class="py-3 px-4 text-sm font-bold text-gray-600">#</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600">Student Name</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600">Reg. Number</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600 text-center">CA ({{ setting('ca_max_score', 40) }})</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600 text-center">Exam ({{ setting('exam_max_score', 60) }})</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600 text-center">Total</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600 text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($students as $i => $student)
                            @php $score = $scores->get($student->id); @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4 text-sm text-gray-400">{{ $i + 1 }}</td>
                                <td class="py-4 px-4 font-bold text-gray-800">{{ $student->full_name }}</td>
                                <td class="py-4 px-4 text-sm text-gray-500">{{ $student->registration_number ?? '—' }}</td>
                                <td class="py-4 px-4">
                                    <input type="number" name="scores[{{ $student->id }}][ca]"
                                           value="{{ $score->ca_score ?? '' }}"
                                           class="w-24 mx-auto block border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center font-bold"
                                           placeholder="0" min="0" max="{{ setting('ca_max_score', 40) }}">
                                </td>
                                <td class="py-4 px-4">
                                    <input type="number" name="scores[{{ $student->id }}][exam]"
                                           value="{{ $score->exam_score ?? '' }}"
                                           class="w-24 mx-auto block border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-center font-bold"
                                           placeholder="0" min="0" max="{{ setting('exam_max_score', 60) }}">
                                </td>
                                <td class="py-4 px-4 text-center font-black text-blue-600">{{ $score ? ($score->total ?? ($score->ca_score + $score->exam_score)) : '—' }}</td>
                                <td class="py-4 px-4 text-center">
                                    @if($score && $score->grade)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                            {{ $score->grade === 'A' ? 'bg-green-100 text-green-700' : '' }}
                                            {{ $score->grade === 'B' ? 'bg-blue-100 text-blue-700' : '' }}
                                            {{ $score->grade === 'C' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                            {{ in_array($score->grade, ['F','E']) ? 'bg-red-100 text-red-700' : '' }}">
                                            {{ $score->grade }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="py-8 text-center text-gray-400 italic">No students enrolled.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-8 flex gap-3">
                        <button type="submit"
                                class="flex-1 bg-indigo-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg transition">
                            Save & Continue
                        </button>
                        <a href="{{ route('results.officer.index', ['program_id' => $subject->program_id, 'level' => $subject->level]) }}"
                           class="px-8 py-4 rounded-xl font-bold text-gray-600 border border-gray-300 hover:bg-gray-50 transition flex items-center justify-center">
                            Back to List
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
