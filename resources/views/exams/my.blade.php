<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📝 Set Exam Questions</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            @if(!$cycle)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">
                    Exam Mode is not active. You can set exam questions once the Exam Officer activates it.
                </div>
            @else
                <div class="bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-3 rounded-lg text-sm">
                    <strong>{{ $cycle->title }}</strong> — submit your questions on or before
                    <strong>{{ $cycle->submission_deadline_at->format('D, d M Y g:ia') }}</strong> (5 days before exams).
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">My Assigned Courses</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Course</th>
                            <th class="px-6 py-3 text-left">Course of Study</th>
                            <th class="px-6 py-3 text-left">Level</th>
                            <th class="px-6 py-3 text-left">Questions</th>
                            <th class="px-6 py-3 text-left">Status</th>
                            <th class="px-6 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($subjects as $s)
                            @php $ex = $exams->get($s->id); $status = $ex->status ?? 'not_started'; $submitted = $ex && $ex->isLocked(); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $s->name }} <span class="text-xs text-gray-400">{{ $s->course_code }}</span></td>
                                <td class="px-6 py-3 text-gray-500">{{ optional($s->program)->name ?? '—' }}</td>
                                <td class="px-6 py-3">{{ $s->level ? 'L'.$s->level : '—' }}</td>
                                <td class="px-6 py-3 text-gray-600">
                                    @if($ex)<span class="text-xs">{{ $ex->objective_count }} obj · {{ $ex->theory_count }} theory</span>@else<span class="text-gray-300">—</span>@endif
                                </td>
                                <td class="px-6 py-3">
                                    @php $badge = ['not_started'=>'bg-gray-100 text-gray-500','draft'=>'bg-amber-100 text-amber-700','submitted'=>'bg-blue-100 text-blue-700','hod_returned'=>'bg-red-100 text-red-700','approved'=>'bg-green-100 text-green-700'][$status] ?? 'bg-gray-100 text-gray-500'; @endphp
                                    <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $badge }}">{{ str_replace('_',' ',$status) }}</span>
                                    @if($status === 'hod_returned' && $ex->hod_feedback)
                                        <p class="text-xs text-red-600 mt-1 max-w-xs"><span class="font-bold">HOD query:</span> {{ $ex->hod_feedback }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if($submitted)
                                        <span class="text-xs text-gray-400 italic">Submitted — locked</span>
                                    @elseif($cycle)
                                        <a href="{{ route('exams.open', $s) }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700">Set Questions</a>
                                    @else
                                        <span class="text-xs text-gray-300">Exam Mode off</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No courses assigned to you yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============ AUTHORING POPUP ============ --}}
    @if($openExam)
        @php
            $objectives = $openExam->questions->where('type','objective')->values();
            $theory = $openExam->questions->where('type','theory')->sortBy('theory_number')->values();
        @endphp
        <div x-data="authoring()" x-init="open=true" x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl my-8">
                <div class="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white rounded-t-2xl">
                    <div>
                        <h3 class="font-bold text-gray-800">{{ $openExam->subject->name }} <span class="text-xs text-gray-400">{{ $openExam->subject->course_code }}</span></h3>
                        <p class="text-xs text-gray-400">{{ optional($openExam->subject->program)->name }} · {{ $openExam->subject->level ? 'L'.$openExam->subject->level : '' }}</p>
                    </div>
                    <a href="{{ route('exams.my') }}" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</a>
                </div>

                <div class="p-6 space-y-6">
                    @if($openExam->status === 'hod_returned' && $openExam->hod_feedback)
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-sm">
                            <p class="font-bold text-red-700 mb-1">⚠️ Returned by HOD — please correct and resubmit:</p>
                            <p class="text-red-700 whitespace-pre-line">{{ $openExam->hod_feedback }}</p>
                        </div>
                    @endif

                    {{-- CSV upload --}}
                    <div class="bg-gray-50 border rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs font-bold text-gray-500 uppercase">Bulk upload (objectives)</p>
                            <a href="{{ route('exams.questions.template') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Download CSV template</a>
                        </div>
                        <form method="POST" action="{{ route('exams.questions.import', $openExam) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="csv" accept=".csv,text/csv" required class="text-sm flex-1">
                            <button class="bg-gray-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-800">Upload</button>
                        </form>
                    </div>

                    {{-- Objective question form (add / edit) --}}
                    <div class="border rounded-xl p-4">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2" x-text="f.question_id ? 'Edit objective question' : 'Add objective question'"></p>
                        <form method="POST" action="{{ route('exams.questions.store', $openExam) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="question_id" :value="f.question_id">
                            <textarea name="question_text" x-model="f.question_text" rows="2" required placeholder="Question text" class="w-full border-gray-300 rounded-lg text-sm"></textarea>
                            <div class="grid sm:grid-cols-2 gap-2">
                                <label class="flex items-center gap-2 text-sm"><input type="radio" name="correct_option" value="a" x-model="f.correct_option" required><input name="option_a" x-model="f.option_a" required placeholder="Option A" class="flex-1 border-gray-300 rounded text-sm py-1"></label>
                                <label class="flex items-center gap-2 text-sm"><input type="radio" name="correct_option" value="b" x-model="f.correct_option"><input name="option_b" x-model="f.option_b" required placeholder="Option B" class="flex-1 border-gray-300 rounded text-sm py-1"></label>
                                <label class="flex items-center gap-2 text-sm"><input type="radio" name="correct_option" value="c" x-model="f.correct_option"><input name="option_c" x-model="f.option_c" placeholder="Option C (optional)" class="flex-1 border-gray-300 rounded text-sm py-1"></label>
                                <label class="flex items-center gap-2 text-sm"><input type="radio" name="correct_option" value="d" x-model="f.correct_option"><input name="option_d" x-model="f.option_d" placeholder="Option D (optional)" class="flex-1 border-gray-300 rounded text-sm py-1"></label>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-gray-500">Marks</label>
                                <input type="number" name="marks" x-model="f.marks" min="1" class="w-20 border-gray-300 rounded text-sm py-1">
                                <span class="text-xs text-gray-400">Tick the radio beside the correct option.</span>
                                <button class="ml-auto bg-indigo-600 text-white px-5 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700" x-text="f.question_id ? 'Update' : 'Create Question'"></button>
                                <button type="button" x-show="f.question_id" @click="reset()" class="text-xs text-gray-500 font-bold">Cancel</button>
                            </div>
                        </form>
                    </div>

                    {{-- Objective list --}}
                    <div>
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Objective questions ({{ $objectives->count() }})</p>
                        <div class="space-y-2">
                            @forelse($objectives as $i => $q)
                                @php $qdata = json_encode(['question_id'=>$q->id,'question_text'=>$q->question_text,'option_a'=>$q->option_a,'option_b'=>$q->option_b,'option_c'=>$q->option_c,'option_d'=>$q->option_d,'correct_option'=>$q->correct_option,'marks'=>$q->marks]); @endphp
                                <div class="border rounded-lg p-3 text-sm">
                                    <div class="flex justify-between gap-2">
                                        <p class="font-semibold text-gray-800">{{ $i+1 }}. {{ $q->question_text }}</p>
                                        <div class="flex gap-2 shrink-0">
                                            <button @click='edit({{ $qdata }})' class="text-xs text-indigo-600 font-bold">Edit</button>
                                            <form method="POST" action="{{ route('exams.questions.delete', $q) }}" onsubmit="return confirm('Delete this question?')">@csrf @method('DELETE')<button class="text-xs text-red-500 font-bold">Delete</button></form>
                                        </div>
                                    </div>
                                    <ul class="mt-1 ml-4 text-xs text-gray-500 grid sm:grid-cols-2 gap-x-4">
                                        <li class="{{ $q->correct_option==='a'?'text-green-600 font-bold':'' }}">A. {{ $q->option_a }}</li>
                                        <li class="{{ $q->correct_option==='b'?'text-green-600 font-bold':'' }}">B. {{ $q->option_b }}</li>
                                        @if($q->option_c)<li class="{{ $q->correct_option==='c'?'text-green-600 font-bold':'' }}">C. {{ $q->option_c }}</li>@endif
                                        @if($q->option_d)<li class="{{ $q->correct_option==='d'?'text-green-600 font-bold':'' }}">D. {{ $q->option_d }}</li>@endif
                                    </ul>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">No objective questions yet.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Theory (optional) --}}
                    <div class="border rounded-xl p-4">
                        <p class="text-xs font-bold text-gray-500 uppercase mb-2">Theory questions <span class="font-normal text-gray-400">(optional)</span></p>
                        <form method="POST" action="{{ route('exams.theory.store', $openExam) }}" class="flex flex-col sm:flex-row gap-2 mb-3">
                            @csrf
                            <select name="theory_number" required class="border-gray-300 rounded-lg text-sm sm:w-40">
                                <option value="">Question #</option>
                                @for($n=1;$n<=10;$n++)<option value="{{ $n }}">Question {{ $n }}</option>@endfor
                            </select>
                            <textarea name="question_text" rows="1" required placeholder="Theory question text" class="flex-1 border-gray-300 rounded-lg text-sm"></textarea>
                            <button class="bg-gray-700 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-800">Save</button>
                        </form>
                        <div class="space-y-2">
                            @forelse($theory as $q)
                                <div class="flex justify-between gap-2 border rounded-lg p-2 text-sm">
                                    <p><span class="font-bold text-gray-700">Q{{ $q->theory_number }}.</span> {{ $q->question_text }}</p>
                                    <form method="POST" action="{{ route('exams.questions.delete', $q) }}" onsubmit="return confirm('Delete this theory question?')">@csrf @method('DELETE')<button class="text-xs text-red-500 font-bold">Delete</button></form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400">No theory questions.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-between border-t pt-4">
                        <a href="{{ route('exams.my') }}" class="text-sm font-bold text-gray-500">Close (save as draft)</a>
                        <form method="POST" action="{{ route('exams.submit', $openExam) }}" onsubmit="return confirm('Submit these questions for HOD review? You will not be able to edit them afterwards.')">
                            @csrf
                            <button class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-emerald-700 text-sm">Submit Questions</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function authoring() {
                return {
                    open: false,
                    f: { question_id:'', question_text:'', option_a:'', option_b:'', option_c:'', option_d:'', correct_option:'a', marks:1 },
                    edit(q) {
                        this.f = { question_id:q.question_id, question_text:q.question_text, option_a:q.option_a||'', option_b:q.option_b||'', option_c:q.option_c||'', option_d:q.option_d||'', correct_option:q.correct_option||'a', marks:q.marks||1 };
                        window.scrollTo({top:0,behavior:'smooth'});
                    },
                    reset() { this.f = { question_id:'', question_text:'', option_a:'', option_b:'', option_c:'', option_d:'', correct_option:'a', marks:1 }; },
                }
            }
        </script>
    @endif
</x-app-layout>
