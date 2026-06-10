<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗃️ Office Secretary — Correspondence Register</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([['Incoming',$stats['incoming'],'indigo'],['Outgoing',$stats['outgoing'],'emerald'],['Pending',$stats['pending'],'amber'],['Total',$stats['total'],'gray']] as [$l,$v,$c])
                    <div class="bg-white rounded-xl border p-4 border-t-4 border-{{ $c }}-500">
                        <p class="text-xs uppercase text-gray-400 font-bold">{{ $l }}</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $v }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Log correspondence --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h3 class="font-bold text-gray-800 mb-4">Log Correspondence</h3>
                <form method="POST" action="{{ route('office.correspondence.store') }}" class="grid md:grid-cols-3 gap-3">
                    @csrf
                    <input name="ref_no" placeholder="Ref. No" class="border-gray-300 rounded-lg">
                    <select name="direction" required class="border-gray-300 rounded-lg">
                        <option value="incoming">Incoming</option>
                        <option value="outgoing">Outgoing</option>
                    </select>
                    <input name="dated" type="date" class="border-gray-300 rounded-lg">
                    <input name="subject" required placeholder="Subject *" class="border-gray-300 rounded-lg md:col-span-2">
                    <input name="party" placeholder="From / To" class="border-gray-300 rounded-lg">
                    <input name="notes" placeholder="Notes" class="border-gray-300 rounded-lg md:col-span-2">
                    <button class="bg-emerald-600 text-white px-6 rounded-lg font-bold hover:bg-emerald-700">Log</button>
                </form>
            </div>

            {{-- Register --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Register</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr><th class="px-4 py-2 text-left">Ref</th><th class="px-4 py-2 text-left">Dir</th><th class="px-4 py-2 text-left">Subject</th><th class="px-4 py-2 text-left">Party</th><th class="px-4 py-2 text-left">Date</th><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2 text-right"></th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($items as $c)
                                <tr>
                                    <td class="px-4 py-2 text-gray-500">{{ $c->ref_no ?? '—' }}</td>
                                    <td class="px-4 py-2"><span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $c->direction==='incoming'?'bg-indigo-100 text-indigo-700':'bg-emerald-100 text-emerald-700' }}">{{ $c->direction }}</span></td>
                                    <td class="px-4 py-2 font-semibold text-gray-800">{{ $c->subject }}<br><span class="text-xs text-gray-400">{{ $c->notes }}</span></td>
                                    <td class="px-4 py-2 text-gray-500">{{ $c->party }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ optional($c->dated)->format('d M Y') }}</td>
                                    <td class="px-4 py-2">
                                        <form method="POST" action="{{ route('office.correspondence.status', $c) }}">@csrf
                                            <select name="status" onchange="this.form.submit()" class="border-gray-300 rounded text-xs py-1">
                                                @foreach(['received','filed','forwarded'] as $st)<option value="{{ $st }}" @selected($c->status===$st)>{{ ucfirst($st) }}</option>@endforeach
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="{{ route('office.correspondence.destroy', $c) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')
                                            <button class="text-xs text-red-500 font-bold hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No correspondence logged.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
