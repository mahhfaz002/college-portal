<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔁 Change of Course — Registrar Approval</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            @forelse($requests as $r)
                <div class="bg-white rounded-2xl shadow-sm border p-6 space-y-3" x-data="{ decision: '' }">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div>
                            <b>{{ optional($r->student)->full_name }}</b>
                            <span class="text-gray-400">·</span> {{ optional($r->student)->registration_number ?? optional($r->student)->admission_number }}
                            <div class="mt-1">
                                <b>{{ optional($r->currentProgram)->name ?? '—' }}</b>
                                <span class="text-gray-400">→</span>
                                <b class="text-indigo-700">{{ optional($r->requestedProgram)->name }}</b>
                            </div>
                        </div>
                        <span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-indigo-100 text-indigo-700">Awaiting final approval</span>
                    </div>

                    <p class="text-xs text-gray-500">Reason: {{ $r->reason }}</p>

                    <div class="grid sm:grid-cols-3 gap-2 text-xs">
                        @if($r->secretary_comment)<div class="p-2 rounded bg-gray-50 border"><b>Academic Sec.:</b> {{ $r->secretary_comment }}</div>@endif
                        @if($r->new_hod_comment)<div class="p-2 rounded bg-gray-50 border"><b>New HOD (accepted):</b> {{ $r->new_hod_comment }}</div>@endif
                        @if($r->current_hod_comment)<div class="p-2 rounded bg-gray-50 border"><b>Current HOD (accepted):</b> {{ $r->current_hod_comment }}</div>@endif
                    </div>

                    <a href="{{ route('change-of-course.credentials', $r) }}" class="inline-block text-xs font-bold text-indigo-600 hover:underline">View student credentials →</a>

                    <form method="POST" action="{{ route('change-of-course.decide', $r) }}" class="border-t pt-3 space-y-2">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Decision</label>
                            <select name="decision" x-model="decision" required class="w-full sm:w-60 border-gray-300 rounded-lg text-sm">
                                <option value="">— Select —</option>
                                <option value="approve">Approve</option>
                                <option value="reject">Reject</option>
                            </select>
                        </div>
                        <div x-show="decision==='approve'">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Approval note (optional)</label>
                            <input name="comment" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Optional note recorded with the approval.">
                        </div>
                        <div x-show="decision==='reject'">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason for rejection</label>
                            <textarea name="reason" rows="2" class="w-full border-gray-300 rounded-lg text-sm" placeholder="Cited back to the student."></textarea>
                        </div>
                        <button class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700">Submit Decision</button>
                    </form>
                </div>
            @empty
                <div class="bg-white rounded-2xl shadow-sm border p-10 text-center text-gray-400">No applications awaiting final approval.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
