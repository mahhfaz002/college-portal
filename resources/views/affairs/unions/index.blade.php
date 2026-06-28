<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏛️ Unions, Groups &amp; Organizations</h2>
            <a href="{{ route('affairs.unions.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700">+ Add Union</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Registered Unions &amp; Associations ({{ $unions->count() }})</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Union / Association</th>
                                <th class="px-6 py-3">President</th>
                                <th class="px-6 py-3">Tenure</th>
                                <th class="px-6 py-3">Members</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($unions as $u)
                                @php $pres = $u->president(); @endphp
                                <tr class="hover:bg-gray-50 {{ $u->isSuspended() ? 'opacity-60' : '' }}">
                                    <td class="px-6 py-3">
                                        <div class="font-bold text-gray-800">{{ $u->name }} @if($u->acronym)<span class="text-gray-400 text-xs">({{ $u->acronym }})</span>@endif</div>
                                        <div class="text-xs text-gray-400">@if($u->year_established)Est. {{ $u->year_established }} · @endif{{ $u->leaders->count() }} officer(s)</div>
                                    </td>
                                    <td class="px-6 py-3">{{ $pres?->name ?? '—' }}<div class="text-xs text-gray-400">{{ $pres?->position }}</div></td>
                                    <td class="px-6 py-3 text-gray-500 text-xs">
                                        @if($pres && $pres->tenure_start){{ $pres->tenure_start->format('d M Y') }} — {{ optional($pres->tenure_end)->format('d M Y') }}@else—@endif
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">{{ number_format($u->members_count) }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $u->isSuspended() ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">{{ ucfirst($u->status) }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('affairs.unions.edit', $u) }}" class="text-indigo-600 text-xs font-bold hover:underline">Edit</a>
                                        <form method="POST" action="{{ route('affairs.unions.suspend', $u) }}" class="inline ml-2">@csrf
                                            <button class="text-amber-600 text-xs font-bold hover:underline">{{ $u->isSuspended() ? 'Reactivate' : 'Suspend' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('affairs.unions.destroy', $u) }}" class="inline ml-2" onsubmit="return confirm('Delete this union?')">@csrf @method('DELETE')
                                            <button class="text-red-500 text-xs font-bold hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No unions registered yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t bg-gray-50 text-right">
                    <a href="{{ route('affairs.unions.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700">+ Add Union</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
