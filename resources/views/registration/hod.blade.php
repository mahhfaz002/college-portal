<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">✅ Registration Approvals</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Department Registrations</div>
                <div class="divide-y">
                    @forelse($students as $s)
                        <div class="p-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-bold text-gray-800">{{ $s->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $s->registration_number }} · {{ $s->program->name ?? '' }}</p>
                                </div>
                                @php $rs = $s->registration_status;
                                    $b = match($rs){
                                        'pending_hod'=>['Pending review','bg-blue-100 text-blue-700'],
                                        'registered'=>['Registered','bg-green-100 text-green-700'],
                                        'documents_rejected'=>['Returned','bg-red-100 text-red-700'],
                                        default=>[$rs,'bg-gray-100 text-gray-600'],
                                    }; @endphp
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $b[1] }}">{{ $b[0] }}</span>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse($docs->get($s->id, collect()) as $d)
                                    <a href="{{ route('documents.show', $d) }}" target="_blank"
                                       class="text-xs bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full hover:bg-indigo-100">
                                        📎 {{ $d->label ?? $d->type }}
                                    </a>
                                @empty
                                    <span class="text-xs text-gray-400">No documents uploaded.</span>
                                @endforelse
                            </div>

                            @if($rs !== 'registered')
                                <div class="mt-4 flex gap-2">
                                    <form method="POST" action="{{ route('hod.registrations.approve', $s) }}"
                                          onsubmit="return confirm('Approve and fully register this student?')">
                                        @csrf
                                        <button class="bg-emerald-600 text-white px-5 py-1.5 rounded-lg text-sm font-bold hover:bg-emerald-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('hod.registrations.reject', $s) }}">
                                        @csrf
                                        <button class="bg-white border border-red-300 text-red-600 px-5 py-1.5 rounded-lg text-sm font-bold hover:bg-red-50">Return for correction</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400">No registrations to review.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
