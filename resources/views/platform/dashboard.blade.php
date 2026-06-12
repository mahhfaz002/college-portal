<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🛰️ Platform Admin — All Colleges</h2>
            <a href="{{ route('platform.register') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-emerald-700 text-sm">+ Register College</a>
        </div>
    </x-slot>

    <div class="py-10" x-data="platformStats()" x-init="poll()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="flex items-center gap-2 text-xs text-gray-400">
                <span class="inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                Live — refreshes automatically every 15s
            </div>

            {{-- KPI cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                @php $cards = [
                    ['Colleges', 'totalColleges', $totalColleges, 'indigo'],
                    ['Students', 'totalStudents', $totalStudents, 'emerald'],
                    ['Staff', 'totalStaff', $totalStaff, 'blue'],
                    ['Applicants', 'totalApplicants', $totalApplicants, 'amber'],
                    ['Revenue (₦)', 'totalRevenue', $totalRevenue, 'green'],
                ]; @endphp
                @foreach($cards as [$label,$key,$val,$color])
                    <div class="bg-white rounded-xl border p-5 border-t-4 border-{{ $color }}-500">
                        <p class="text-xs uppercase text-gray-400 font-bold">{{ $label }}</p>
                        <p class="text-2xl font-black text-gray-800"
                           x-text="fmt('{{ $key }}', {{ is_numeric($val) ? $val : 0 }})">{{ $key==='totalRevenue' ? money($val) : number_format($val) }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Per-college breakdown --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Colleges ({{ $totalColleges }})</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr><th class="px-4 py-3 text-left">College</th><th class="px-4 py-3 text-left">Domain</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Students</th><th class="px-4 py-3 text-right">Staff</th><th class="px-4 py-3 text-right">Revenue</th><th class="px-4 py-3 text-right"></th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($rows as $r)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-semibold text-gray-800">{{ $r['college']->name }}<br><span class="text-xs text-gray-400">{{ $r['college']->acronym }}</span></td>
                                    <td class="px-4 py-3 text-gray-500">{{ $r['college']->domain ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $r['college']->is_active ? 'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">{{ $r['college']->is_active ? 'Active':'Suspended' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ number_format($r['students']) }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($r['staff']) }}</td>
                                    <td class="px-4 py-3 text-right">{{ money($r['revenue']) }}</td>
                                    <td class="px-4 py-3 text-right"><a href="{{ route('platform.colleges.show', $r['college']) }}" class="text-indigo-600 font-semibold hover:underline">Manage</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No colleges yet. Register the first one.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function platformStats(){
            return {
                vals: {},
                fmt(key, fallback){
                    const v = this.vals[key.replace('total','').toLowerCase()];
                    const n = (v===undefined? fallback : v);
                    return key==='totalRevenue' ? '₦'+Number(n).toLocaleString() : Number(n).toLocaleString();
                },
                async poll(){
                    const tick = async () => {
                        try { const r = await fetch('{{ route('platform.stats') }}'); this.vals = await r.json(); } catch(e){}
                    };
                    await tick();
                    setInterval(tick, 15000);
                }
            }
        }
    </script>
</x-app-layout>
