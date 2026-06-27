<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Affairs Cases — Registrar Review</h2></x-slot>
    <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Cases for Review ({{ $cases->count() }})</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="text-left px-4 py-3">Student</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Description</th><th class="text-right px-4 py-3">Action</th></tr></thead>
                <tbody class="divide-y">
                    @forelse($cases as $case)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $case->student_name ?? '—' }}<br><span class="text-xs text-gray-400">by {{ $case->loggedByUser->name ?? '—' }}</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $case->category === 'disciplinary' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($case->category) }}</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $case->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst(str_replace('_',' ',$case->status)) }}</span></td>
                        <td class="px-4 py-3 text-xs text-gray-600 max-w-xs truncate">{{ \Illuminate\Support\Str::limit($case->description, 100) }}@if($case->recommendation)<br><strong>Recommendation:</strong> {{ \Illuminate\Support\Str::limit($case->recommendation, 80) }}@endif</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($case->status === 'pending_registrar')
                                <form method="POST" action="{{ route('cases.registrar.resolve', $case) }}" class="inline">@csrf
                                    <input name="registrar_resolution" placeholder="Resolution" required class="border-gray-300 rounded text-xs py-1 w-32">
                                    <button class="text-green-600 text-xs font-bold hover:underline ml-1">Resolve</button>
                                </form>
                                <form method="POST" action="{{ route('cases.registrar.forward', $case) }}" class="inline ml-2">@csrf
                                    <button class="text-amber-600 text-xs font-bold hover:underline">Forward to Provost</button>
                                </form>
                            @elseif($case->status === 'resolved' && !$case->student_notified_at)
                                <form method="POST" action="{{ route('affairs.cases.notify', $case) }}" class="inline">@csrf <button class="text-emerald-600 text-xs font-bold hover:underline">Notify Student</button></form>
                            @else
                                <span class="text-xs text-gray-400">{{ $case->final_resolution ? 'Resolved' : '—' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No cases to review.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</x-app-layout>
