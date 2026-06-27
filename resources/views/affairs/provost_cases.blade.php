<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Affairs Cases — Provost Review</h2></x-slot>
    <div class="py-12"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Cases Escalated to Provost ({{ $cases->count() }})</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="text-left px-4 py-3">Student</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Description</th><th class="text-right px-4 py-3">Action</th></tr></thead>
                <tbody class="divide-y">
                    @forelse($cases as $case)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $case->student_name ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $case->category === 'disciplinary' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($case->category) }}</span></td>
                        <td class="px-4 py-3 text-xs text-gray-600 max-w-xs truncate">{{ \Illuminate\Support\Str::limit($case->description, 120) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($case->status === 'pending_provost')
                                <form method="POST" action="{{ route('cases.provost.resolve', $case) }}" class="inline">@csrf
                                    <input name="provost_resolution" placeholder="Final resolution" required class="border-gray-300 rounded text-xs py-1 w-32">
                                    <button class="text-green-600 text-xs font-bold hover:underline ml-1">Resolve</button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">{{ $case->provost_resolution ? 'Resolved' : '—' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No escalated cases.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</x-app-layout>
