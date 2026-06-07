<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff ID Card</h2>
            <button onclick="window.print()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm no-print">🖨️ Print</button>
        </div>
    </x-slot>

    <style>
        @media print {
            nav, header, .no-print { display: none !important; }
            body { background: white !important; }
            .py-12 { padding: 0 !important; }
        }
    </style>

    <div class="py-12">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-lg border overflow-hidden" style="border-top: 8px solid {{ $school['color'] ?? '#2563eb' }};">
                <div class="p-4 flex items-center gap-3 border-b" style="background: {{ $school['color'] ?? '#2563eb' }}10;">
                    @if(!empty($school['logo']))
                        <img src="{{ media_url($school['logo']) }}" class="h-10 w-10 object-contain" alt="">
                    @endif
                    <div>
                        <p class="font-black text-gray-800 leading-tight">{{ $school['name'] ?? 'School' }}</p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-wide">Staff Identification Card</p>
                    </div>
                </div>

                <div class="p-6 flex gap-5">
                    @if($staff->passport)
                        <img src="{{ $staff->passport }}" class="w-28 h-32 object-cover rounded-lg border-2" style="border-color: {{ $school['color'] ?? '#2563eb' }};" alt="">
                    @else
                        <div class="w-28 h-32 rounded-lg bg-gray-100 flex items-center justify-center text-5xl font-black text-gray-300">{{ strtoupper(substr($staff->name,0,1)) }}</div>
                    @endif
                    <div class="flex-1 text-sm space-y-1">
                        <p class="text-lg font-black text-gray-800 leading-tight">{{ $staff->name }}</p>
                        <p class="uppercase text-[10px] font-bold inline-block px-2 py-0.5 rounded text-white" style="background: {{ $school['color'] ?? '#2563eb' }};">{{ str_replace('_',' ',$staff->role) }}</p>
                        <p class="pt-2"><span class="text-gray-400 text-xs">ID No:</span><br><span class="font-mono font-bold">{{ $staff->staff_id ?? '—' }}</span></p>
                        @if($staff->department)<p><span class="text-gray-400 text-xs">Dept:</span> {{ $staff->department }}</p>@endif
                        @if($staff->phone)<p><span class="text-gray-400 text-xs">Phone:</span> {{ $staff->phone }}</p>@endif
                    </div>
                </div>

                <div class="px-6 pb-5">
                    @if($staff->classes->count())
                        <p class="text-[11px] text-gray-500"><span class="font-bold uppercase">Classes:</span> {{ $staff->classes->pluck('name')->implode(', ') }}</p>
                    @endif
                    <div class="mt-4 flex justify-between items-end">
                        <div class="text-[10px] text-gray-400">
                            <p>{{ $school['address'] ?? '' }}</p>
                            <p>Issued {{ now()->format('M Y') }}</p>
                        </div>
                        <div class="text-center">
                            <div class="w-24 border-b border-gray-400"></div>
                            <p class="text-[9px] text-gray-400 mt-1">Authorised Signature</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4 no-print">
                <a href="{{ route('staff.show', $staff) }}" class="text-sm text-gray-500 font-bold">← Back to Profile</a>
            </div>
        </div>
    </div>
</x-app-layout>
