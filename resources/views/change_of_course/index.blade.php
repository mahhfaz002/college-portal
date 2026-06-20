<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔁 Change of Course</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            @php $open = $requests->firstWhere(fn ($r) => ! in_array($r->status, ['approved', 'rejected'])); @endphp

            {{-- Application form (only when there is no application in progress) --}}
            @unless($open)
            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <h3 class="font-bold text-gray-800 mb-1">Apply for a Change of Course</h3>
                <p class="text-sm text-gray-500 mb-4">
                    You are currently on <b>{{ optional($student->program)->name ?? $student->class_arm }}</b>.
                    A non-refundable application fee of <b>{{ money(\App\Models\ChangeOfCourseRequest::FEE) }}</b>
                    applies. After payment, the Academic Secretary reviews your request and the Registrar makes the final decision.
                </p>
                <form method="POST" action="{{ route('change-of-course.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">New course of study</label>
                        <select name="requested_program_id" required class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">— Select the course you want —</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}@if($p->department) — {{ $p->department->name }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason for the change</label>
                        <textarea name="reason" rows="4" required class="w-full border-gray-300 rounded-lg text-sm" placeholder="Explain why you are applying to change your course of study.">{{ old('reason') }}</textarea>
                    </div>
                    <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-indigo-700">
                        Submit &amp; Pay {{ money(\App\Models\ChangeOfCourseRequest::FEE) }}
                    </button>
                </form>
            </div>
            @endunless

            {{-- Tracking --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <h3 class="font-bold text-gray-800 mb-4">My Applications</h3>
                @forelse($requests as $r)
                    @php
                        $badge = [
                            'pending_payment' => 'bg-yellow-100 text-yellow-700',
                            'under_review'    => 'bg-blue-100 text-blue-700',
                            'recommended'     => 'bg-indigo-100 text-indigo-700',
                            'not_recommended' => 'bg-orange-100 text-orange-700',
                            'approved'        => 'bg-green-100 text-green-700',
                            'rejected'        => 'bg-red-100 text-red-700',
                        ][$r->status] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <div class="border rounded-xl p-4 mb-3">
                        <div class="flex items-center justify-between">
                            <div class="text-sm">
                                <b>{{ optional($r->currentProgram)->name ?? '—' }}</b>
                                <span class="text-gray-400">→</span>
                                <b class="text-indigo-700">{{ optional($r->requestedProgram)->name }}</b>
                            </div>
                            <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $badge }}">{{ $r->statusLabel() }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Reason: {{ $r->reason }}</p>

                        {{-- Progress timeline --}}
                        @php
                            $steps = ['pending_payment'=>1,'under_review'=>2,'recommended'=>3,'not_recommended'=>3,'approved'=>4,'rejected'=>4];
                            $stage = $steps[$r->status] ?? 1;
                        @endphp
                        <div class="flex items-center gap-2 mt-3 text-[11px]">
                            <span class="{{ $stage>=1?'text-green-600 font-bold':'text-gray-400' }}">Paid</span>
                            <span class="text-gray-300">—</span>
                            <span class="{{ $stage>=2?'text-green-600 font-bold':'text-gray-400' }}">Academic Sec.</span>
                            <span class="text-gray-300">—</span>
                            <span class="{{ $stage>=3?'text-green-600 font-bold':'text-gray-400' }}">Registrar</span>
                            <span class="text-gray-300">—</span>
                            <span class="{{ $r->status==='approved'?'text-green-600 font-bold':($r->status==='rejected'?'text-red-600 font-bold':'text-gray-400') }}">Decision</span>
                        </div>

                        @if($r->status === 'pending_payment' && $r->invoice_id)
                            <a href="{{ route('payments.checkout', $r->invoice_id) }}" class="inline-block mt-3 bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold hover:bg-indigo-700">Pay application fee</a>
                        @endif
                        @if($r->status === 'rejected' && $r->registrar_reason)
                            <p class="text-xs text-red-600 mt-2">Registrar's reason: {{ $r->registrar_reason }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400 italic">You have not applied for a change of course yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
