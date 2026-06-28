<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔁 Change of Course</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            {{-- An active application (still processing, or approved awaiting the new
                 registration fee) blocks a new one. --}}
            @php $active = $requests->first(fn ($r) => ! in_array($r->status, ['rejected', 'completed'])); @endphp

            {{-- Application form --}}
            @unless($active)
            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <h3 class="font-bold text-gray-800 mb-1">Apply for a Change of Course</h3>
                <p class="text-sm text-gray-500 mb-4">
                    You are currently on <b>{{ optional($student->program)->name ?? $student->class_arm }}</b>.
                    A non-refundable application fee of <b>{{ money($fee) }}</b>
                    applies. After payment it is reviewed by the Academic Secretary, the new and current department HODs, then approved by the Registrar.
                </p>
                <form method="POST" action="{{ route('change-of-course.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">New course of study</label>
                        <select name="requested_program_id" required class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">— Select the course you want —</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}" @selected(old('requested_program_id')==$p->id)>{{ $p->name }}@if($p->department) — {{ $p->department->name }}@endif</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Choose the course of study (and department) you want to move to.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason for the change</label>
                        <textarea name="reason" rows="4" required class="w-full border-gray-300 rounded-lg text-sm" placeholder="Explain why you are applying to change your course of study.">{{ old('reason') }}</textarea>
                    </div>
                    <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-indigo-700">
                        Submit &amp; Pay {{ money($fee) }}
                    </button>
                </form>
            </div>
            @endunless

            {{-- My Applications (paid / submitted only) --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <h3 class="font-bold text-gray-800 mb-4">My Applications</h3>
                @forelse($requests as $r)
                    @php $stage = $r->studentStage(); @endphp
                    <div class="border rounded-xl p-4 mb-3" x-data="{ open: false }">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm">
                                <b>{{ optional($r->currentProgram)->name ?? '—' }}</b>
                                <span class="text-gray-400">→</span>
                                <b class="text-indigo-700">{{ optional($r->requestedProgram)->name }}</b>
                            </div>
                            @if($stage === 'processing')
                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-yellow-100 text-yellow-700">Processing</span>
                            @elseif($r->isApproved())
                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-green-100 text-green-700">{{ $r->status === 'completed' ? 'Completed' : 'Approved' }}</span>
                            @else
                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-red-100 text-red-700">Closed — Rejected</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Reason: {{ $r->reason }}</p>

                        {{-- Progress timeline --}}
                        @php
                            $steps = [
                                'secretary_review'=>1,'new_hod_review'=>2,'new_hod_approved'=>2,'new_hod_rejected'=>2,
                                'current_hod_review'=>3,'current_hod_approved'=>3,'current_hod_rejected'=>3,
                                'registrar_review'=>4,'approved'=>5,'completed'=>5,'rejected'=>5,
                            ];
                            $st = $steps[$r->status] ?? 1;
                        @endphp
                        <div class="flex flex-wrap items-center gap-1.5 mt-3 text-[11px]">
                            <span class="{{ $st>=1?'text-green-600 font-bold':'text-gray-400' }}">Paid</span><span class="text-gray-300">—</span>
                            <span class="{{ $st>=1?'text-green-600 font-bold':'text-gray-400' }}">Academic Sec.</span><span class="text-gray-300">—</span>
                            <span class="{{ $st>=2?'text-green-600 font-bold':'text-gray-400' }}">New HOD</span><span class="text-gray-300">—</span>
                            <span class="{{ $st>=3?'text-green-600 font-bold':'text-gray-400' }}">Current HOD</span><span class="text-gray-300">—</span>
                            <span class="{{ $st>=4?'text-green-600 font-bold':'text-gray-400' }}">Registrar</span><span class="text-gray-300">—</span>
                            <span class="{{ $r->isApproved()?'text-green-600 font-bold':($r->isRejected()?'text-red-600 font-bold':'text-gray-400') }}">Decision</span>
                        </div>

                        <button @click="open = !open" class="mt-3 text-xs font-bold text-indigo-600 hover:underline">
                            <span x-show="!open">View details ▾</span><span x-show="open">Hide details ▴</span>
                        </button>

                        <div x-show="open" x-collapse class="mt-3 border-t pt-3 text-sm space-y-2">
                            @if($r->isApproved())
                                <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-800">
                                    🎉 Your application has been <b>approved</b>. Download your acceptance letter and pay the new registration fee to complete your transfer.
                                </div>
                                <a href="{{ route('change-of-course.letter', $r) }}" target="_blank" class="inline-block bg-gray-800 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-gray-900">View / Download Acceptance Letter</a>
                                @if($r->awaitingNewRegistrationFee())
                                    <a href="{{ route('change-of-course.pay-new-fee', $r) }}" class="inline-block bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700">Pay New Registration Fee</a>
                                    <p class="text-xs text-gray-500">You can also pay it from your <a href="{{ route('student.fees') }}" class="underline">Fees</a> page.</p>
                                @elseif($r->status === 'completed')
                                    <p class="text-xs text-green-700 font-semibold">Registration fee paid — you are now on your new course. Your previous results are retained.</p>
                                @endif
                            @elseif($r->isRejected())
                                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-800">
                                    <p class="font-bold">Application closed — rejected.</p>
                                    @if($r->rejection_reason)<p class="mt-1 text-xs">Reason: {{ $r->rejection_reason }}</p>@endif
                                </div>
                            @else
                                <p class="text-gray-600">Your application is being processed — current stage: <b>{{ $r->statusLabel() }}</b>. You will be notified once a decision is made.</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">You have no submitted change-of-course applications yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
