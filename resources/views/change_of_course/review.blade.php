<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔁 Change of Course — Academic Secretary</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            @forelse($requests as $r)
                <div class="bg-white rounded-2xl shadow-sm border p-6 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="text-sm">
                            <b>{{ optional($r->student)->full_name }}</b>
                            <span class="text-gray-400">·</span> {{ optional($r->student)->registration_number ?? optional($r->student)->admission_number }}
                            <div class="mt-1">
                                <b>{{ optional($r->currentProgram)->name ?? '—' }}</b>
                                <span class="text-gray-400">→</span>
                                <b class="text-indigo-700">{{ optional($r->requestedProgram)->name }}</b>
                            </div>
                        </div>
                        <span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-blue-100 text-blue-700">{{ $r->statusLabel() }}</span>
                    </div>

                    <p class="text-xs text-gray-500">Reason: {{ $r->reason }}</p>

                    @if($r->new_hod_comment)
                        <div class="text-xs p-2 rounded bg-gray-50 border"><b>New HOD ({{ $r->new_hod_decision }}):</b> {{ $r->new_hod_comment }}</div>
                    @endif
                    @if($r->current_hod_comment)
                        <div class="text-xs p-2 rounded bg-gray-50 border"><b>Current HOD ({{ $r->current_hod_decision }}):</b> {{ $r->current_hod_comment }}</div>
                    @endif

                    <a href="{{ route('change-of-course.credentials', $r) }}" class="inline-block text-xs font-bold text-indigo-600 hover:underline">View student credentials →</a>

                    <div class="border-t pt-3">
                        @if($r->status === 'secretary_review')
                            <form method="POST" action="{{ route('change-of-course.forward-new-hod', $r) }}" class="space-y-2">
                                @csrf
                                <label class="block text-xs font-bold text-gray-500 uppercase">Your comment</label>
                                <textarea name="comment" rows="2" required class="w-full border-gray-300 rounded-lg text-sm" placeholder="Your processing note for the new department HOD.">{{ old('comment') }}</textarea>
                                <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700">Forward to New Department HOD</button>
                            </form>
                        @elseif($r->status === 'new_hod_approved')
                            <form method="POST" action="{{ route('change-of-course.relay-current-hod', $r) }}">
                                @csrf
                                <p class="text-xs text-green-700 mb-2">New department HOD accepted. Forward to the student's current HOD for clearance.</p>
                                <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700">Forward to Current Department HOD</button>
                            </form>
                        @elseif($r->status === 'current_hod_approved')
                            <form method="POST" action="{{ route('change-of-course.forward-registrar', $r) }}">
                                @csrf
                                <p class="text-xs text-green-700 mb-2">Current HOD cleared the transfer. Forward to the Registrar for final approval.</p>
                                <button class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700">Forward to Registrar</button>
                            </form>
                        @elseif(in_array($r->status, ['new_hod_rejected', 'current_hod_rejected']))
                            <form method="POST" action="{{ route('change-of-course.reject-student', $r) }}">
                                @csrf
                                <p class="text-xs text-red-700 mb-2">A HOD rejected this transfer. Reject the application back to the student (the HOD's reason will be cited).</p>
                                <button class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700">Reject Application to Student</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl shadow-sm border p-10 text-center text-gray-400">No change-of-course applications need your action.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
