<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🧾 Exam Question Reviews @if($department) — {{ $department->name }} @endif
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <div class="bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-3 rounded-lg text-sm">
                Review-only. Approve a question set to forward it to the Exam Officer, or query it back to the lecturer with your reasons. Approved sets leave this list.
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Submitted for review</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Course</th>
                            <th class="px-6 py-3 text-left">Course of Study · Level</th>
                            <th class="px-6 py-3 text-left">Questions</th>
                            <th class="px-6 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($exams as $ex)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ optional($ex->subject)->name }} <span class="text-xs text-gray-400">{{ optional($ex->subject)->course_code }}</span></td>
                                <td class="px-6 py-3 text-gray-500">{{ optional(optional($ex->subject)->program)->name ?? '—' }} · {{ optional($ex->subject)->level ? 'L'.$ex->subject->level : '—' }}</td>
                                <td class="px-6 py-3 text-gray-600 text-xs">{{ $ex->objective_count }} obj · {{ $ex->theory_count }} theory</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('hod.exam-reviews', ['open' => $ex->id]) }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700">Review</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No question sets awaiting your review.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ READ-ONLY REVIEW POPUP ============ --}}
    @if($openExam)
        @php
            $objectives = $openExam->questions->where('type','objective')->values();
            $theory = $openExam->questions->where('type','theory')->sortBy('theory_number')->values();
        @endphp
        <div x-data="{ open: true, showQuery: false }" x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-gray-800">{{ optional($openExam->subject)->name }} <span class="text-xs text-gray-400">{{ optional($openExam->subject)->course_code }}</span></h3>
                        <p class="text-xs text-gray-400">{{ optional(optional($openExam->subject)->program)->name }} · {{ optional($openExam->subject)->level ? 'L'.$openExam->subject->level : '' }} · review only</p>
                    </div>
                    <a href="{{ route('hod.exam-reviews') }}" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</a>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Objectives (read-only) --}}
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Objective questions ({{ $objectives->count() }})</p>
                        <div class="space-y-2">
                            @forelse($objectives as $i => $q)
                                <div class="border rounded-lg p-3 text-sm">
                                    <p class="font-semibold text-gray-800">{{ $i+1 }}. {{ $q->question_text }}</p>
                                    <ul class="mt-1 ml-4 text-xs text-gray-500 grid sm:grid-cols-2 gap-x-4">
                                        <li class="{{ $q->correct_option==='a'?'text-green-600 font-bold':'' }}">A. {{ $q->option_a }}</li>
                                        <li class="{{ $q->correct_option==='b'?'text-green-600 font-bold':'' }}">B. {{ $q->option_b }}</li>
                                        @if($q->option_c)<li class="{{ $q->correct_option==='c'?'text-green-600 font-bold':'' }}">C. {{ $q->option_c }}</li>@endif
                                        @if($q->option_d)<li class="{{ $q->correct_option==='d'?'text-green-600 font-bold':'' }}">D. {{ $q->option_d }}</li>@endif
                                    </ul>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">No objective questions.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Theory (read-only) --}}
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Theory questions ({{ $theory->count() }})</p>
                        <div class="space-y-2">
                            @forelse($theory as $q)
                                <div class="border rounded-lg p-2 text-sm"><span class="font-bold text-gray-700">Q{{ $q->theory_number }}.</span> {{ $q->question_text }}</div>
                            @empty
                                <p class="text-sm text-gray-400">No theory questions.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Decision --}}
                    <div class="border-t pt-4 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <button @click="showQuery = !showQuery" class="bg-amber-500 text-white px-5 py-2 rounded-lg font-bold hover:bg-amber-600 text-sm">Query / Return</button>
                            <form method="POST" action="{{ route('hod.exam-reviews.approve', $openExam) }}" onsubmit="return confirm('Approve and forward to the Exam Officer? You will no longer be able to view these.')">
                                @csrf
                                <button class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-emerald-700 text-sm">Approve</button>
                            </form>
                        </div>
                        <form x-show="showQuery" method="POST" action="{{ route('hod.exam-reviews.query', $openExam) }}" class="space-y-2">
                            @csrf
                            <textarea name="hod_feedback" rows="4" required placeholder="State the reasons, the question number(s) affected, and your recommendation…" class="w-full border-gray-300 rounded-lg text-sm"></textarea>
                            <button class="bg-amber-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-amber-700 text-sm">Return to lecturer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
