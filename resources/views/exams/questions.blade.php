<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Questions — {{ $exam->title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <div class="bg-white p-4 rounded-xl border text-sm text-gray-600 flex items-center justify-between flex-wrap gap-2">
                <span>Course: <strong>{{ $exam->subject->name ?? '—' }}</strong> · Questions: {{ $exam->questions->count() }} · Total marks: {{ $exam->totalMarks() }}
                @if($exam->isLocked())<span class="ml-2 text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">Submitted to Exam Officer — editing locked</span>@endif</span>
                @unless($exam->isLocked())
                    @if($exam->questions->count() > 0)
                    <form action="{{ route('exams.submit', $exam) }}" method="POST" onsubmit="return confirm('Submit to the Exam Officer? You will not be able to edit afterwards.')">
                        @csrf<button class="bg-emerald-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-emerald-700">Submit to Exam Officer</button>
                    </form>
                    @endif
                @endunless
            </div>

            @unless($exam->isLocked())
            {{-- Bulk CSV upload --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-700">Bulk Upload (CSV)</h3>
                    <a href="{{ route('exams.questions.template') }}" class="text-xs font-bold text-indigo-600 hover:underline">⬇ Download template</a>
                </div>
                <form action="{{ route('exams.questions.import', $exam) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-center">
                    @csrf
                    <input type="file" name="csv" accept=".csv,text/csv" required class="text-sm text-gray-600">
                    <button class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-900">Import Questions</button>
                </form>
                <p class="text-xs text-gray-400 mt-2">Columns: QUESTION, OPTION A, OPTION B, OPTION C, OPTION D, CORRECT ANSWER (A/B/C/D).</p>
            </div>
            @endunless

            <!-- Existing questions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 divide-y">
                @forelse($exam->questions as $i => $q)
                <div class="p-4">
                    <div class="flex justify-between">
                        <p class="font-bold text-gray-800">{{ $i+1 }}. {{ $q->question_text }} <span class="text-xs text-gray-400">({{ $q->marks }} mk)</span></p>
                        <form action="{{ route('exams.questions.delete', $q) }}" method="POST" onsubmit="return confirm('Delete question?')">@csrf @method('DELETE')
                            <button class="text-red-600 text-xs font-bold">✕</button>
                        </form>
                    </div>
                    <ul class="text-sm text-gray-600 mt-1 ml-4">
                        <li class="{{ $q->correct_option==='a' ? 'font-bold text-green-700' : '' }}">A. {{ $q->option_a }}</li>
                        <li class="{{ $q->correct_option==='b' ? 'font-bold text-green-700' : '' }}">B. {{ $q->option_b }}</li>
                        @if($q->option_c)<li class="{{ $q->correct_option==='c' ? 'font-bold text-green-700' : '' }}">C. {{ $q->option_c }}</li>@endif
                        @if($q->option_d)<li class="{{ $q->correct_option==='d' ? 'font-bold text-green-700' : '' }}">D. {{ $q->option_d }}</li>@endif
                    </ul>
                </div>
                @empty
                <p class="p-6 text-center text-gray-400 italic">No questions yet. Add the first one below.</p>
                @endforelse
            </div>

            <!-- Add question -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Add Objective Question</h3>
                <form action="{{ route('exams.questions.store', $exam) }}" method="POST" class="space-y-4">
                    @csrf
                    <textarea name="question_text" rows="2" placeholder="Question text" class="w-full border-gray-300 rounded-md shadow-sm" required></textarea>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input name="option_a" placeholder="Option A" class="border-gray-300 rounded-md shadow-sm" required>
                        <input name="option_b" placeholder="Option B" class="border-gray-300 rounded-md shadow-sm" required>
                        <input name="option_c" placeholder="Option C (optional)" class="border-gray-300 rounded-md shadow-sm">
                        <input name="option_d" placeholder="Option D (optional)" class="border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Correct Option</label>
                            <select name="correct_option" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Marks</label>
                            <input type="number" name="marks" value="1" min="1" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                    </div>
                    <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Add Question</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
