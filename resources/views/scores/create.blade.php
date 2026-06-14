<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📊 Score Entry — {{ optional($selectedSubject)->name ?? 'Select a course' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc ml-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Course picker. Lecturers see only their assigned courses; oversight roles see all. --}}
            <form method="GET" action="{{ route('scores.create') }}" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3">
                <label class="text-xs font-bold text-gray-500 uppercase">Course</label>
                <select name="subject_id" onchange="this.form.submit()" class="rounded-lg border-gray-300 w-full max-w-xl">
                    <option value="">— Select course —</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" {{ optional($selectedSubject)->id == $s->id ? 'selected' : '' }}>
                            {{ $s->name }}{{ $s->course_code ? ' ('.$s->course_code.')' : '' }}@if($s->program_id || $s->level) — {{ optional($s->program)->name }}{{ $s->level ? ' · L'.$s->level : '' }}@endif
                        </option>
                    @endforeach
                </select>
            </form>
            @if($subjects->isEmpty())
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">No courses are assigned to you yet. Ask the Academic Secretary or your HOD to assign your courses.</div>
            @endif

            <form action="{{ route('scores.store') }}" method="POST" class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200">
                @csrf

                <input type="hidden" name="subject_id" value="{{ optional($selectedSubject)->id }}">
                <div class="p-6 bg-indigo-900 text-white flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <p class="block text-xs font-bold uppercase text-indigo-300">Course</p>
                        <p class="mt-1 text-lg font-black">{{ optional($selectedSubject)->name ?? '— Select a course above —' }}</p>
                        @if($selectedSubject)
                            <p class="text-xs text-indigo-300">{{ $selectedSubject->course_code ?? '' }} {{ optional($selectedSubject->program)->name }}{{ $selectedSubject->level ? ' · L'.$selectedSubject->level : '' }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold">Max CA: {{ setting('ca_max_score', 40) }} | Max Exam: {{ setting('exam_max_score', 60) }}</p>
                        <p class="text-xs text-indigo-300 italic">{{ setting('current_term') }} • {{ setting('current_session') }}</p>
                    </div>
                </div>

                <div class="p-6">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b-2 border-gray-100">
                                <th class="py-3 px-4 text-sm font-bold text-gray-600">Student Name</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600 text-center">CA Score (40)</th>
                                <th class="py-3 px-4 text-sm font-bold text-gray-600 text-center">Exam Score (60)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($students as $student)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4 font-bold text-gray-800">{{ $student->full_name }}</td>
                                <td class="py-4 px-4">
                                    <input type="number" name="scores[{{ $student->id }}][ca]"
                                           class="w-24 mx-auto block border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-center font-bold"
                                           placeholder="0" min="0" max="{{ setting('ca_max_score', 40) }}">
                                </td>
                                <td class="py-4 px-4">
                                    <input type="number" name="scores[{{ $student->id }}][exam]"
                                           class="w-24 mx-auto block border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-center font-bold"
                                           placeholder="0" min="0" max="{{ setting('exam_max_score', 60) }}">
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="py-8 text-center text-gray-400 italic">{{ $selectedSubject ? 'No students enrolled in this programme & level yet.' : 'Select a course above to load its students.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-8">
                        <button type="submit" @disabled($students->isEmpty() || !$selectedSubject) class="w-full bg-indigo-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg transition disabled:opacity-40">
                            💾 Upload Course Scores
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
