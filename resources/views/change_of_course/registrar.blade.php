<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔁 Change of Course — Registrar Approval</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4" x-data="{ rejecting: null }">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">Applications reviewed by the Academic Secretary</h3></div>
                @forelse($requests as $r)
                    <div class="p-6 border-b">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm">
                                <b>{{ optional($r->student)->full_name }}</b>
                                <span class="text-xs text-gray-400 font-mono">{{ optional($r->student)->registration_number ?: optional($r->student)->admission_number }}</span>
                            </div>
                            <div class="text-sm">{{ optional($r->currentProgram)->name ?? '—' }} <span class="text-gray-400">→</span> <b class="text-indigo-700">{{ optional($r->requestedProgram)->name }}</b></div>
                        </div>
                        <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">{{ $r->reason }}</p>

                        <div class="mt-2 text-xs">
                            <span class="font-bold {{ $r->status === 'recommended' ? 'text-green-700' : 'text-orange-700' }}">
                                Academic Secretary: {{ $r->status === 'recommended' ? 'Recommended' : 'Recommended Against' }}
                            </span>
                            @if($r->secretary_note)<span class="text-gray-500">— {{ $r->secretary_note }}</span>@endif
                        </div>

                        <div class="mt-3 flex flex-col gap-2">
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('change-of-course.decide', $r) }}" onsubmit="return confirm('Approve this change of course? The student will be moved to the new course.')">
                                    @csrf
                                    <input type="hidden" name="decision" value="approve">
                                    <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-700">Approve</button>
                                </form>
                                <button type="button" @click="rejecting = (rejecting === {{ $r->id }} ? null : {{ $r->id }})" class="bg-red-100 text-red-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-200">Reject…</button>
                            </div>
                            <form method="POST" action="{{ route('change-of-course.decide', $r) }}" x-show="rejecting === {{ $r->id }}" x-cloak class="flex gap-2" style="display:none">
                                @csrf
                                <input type="hidden" name="decision" value="reject">
                                <input name="reason" required placeholder="Reason for rejection" class="flex-1 border-gray-300 rounded-lg text-sm">
                                <button class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700">Confirm Reject</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="p-8 text-center text-gray-400 italic">No applications awaiting your decision.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
