<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏫 Colleges</h2>
            <a href="{{ route('platform.register') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-emerald-700 text-sm">+ Register College</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-3 text-left">College</th><th class="px-4 py-3 text-left">Domain</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Students</th><th class="px-4 py-3 text-right">Staff</th><th class="px-4 py-3 text-right">Revenue</th><th class="px-4 py-3 text-right">Manage</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($rows as $r)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $r['college']->name }}<br><span class="text-xs text-gray-400">{{ $r['college']->acronym }}</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ $r['college']->domain ?? '—' }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $r['college']->is_active ? 'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">{{ $r['college']->is_active ? 'Active':'Suspended' }}</span></td>
                                <td class="px-4 py-3 text-right">{{ number_format($r['students']) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($r['staff']) }}</td>
                                <td class="px-4 py-3 text-right">{{ money($r['revenue']) }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('platform.colleges.show', $r['college']) }}" class="text-indigo-600 font-semibold hover:underline">Manage</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No colleges yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
