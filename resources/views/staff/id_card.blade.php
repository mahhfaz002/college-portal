<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff ID Card</h2>
            <button onclick="window.print()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm no-print">🖨️ Print (Front &amp; Back)</button>
        </div>
    </x-slot>

    @php
        $brand = current_college()?->primary_color ?? ($school['color'] ?? '#1e3a8a');
        $logo  = current_college()?->logo_path ?? ($school['logo'] ?? null);
        $cname = $school['name'] ?? (current_college()?->name ?? 'College Portal');
    @endphp

    <div class="flex flex-wrap justify-center items-start gap-8 font-sans py-10">

        {{-- ============================= FRONT ============================= --}}
        <div class="id-card w-[320px] h-[500px] bg-white border border-gray-300 shadow-2xl rounded-2xl overflow-hidden relative">
            <div class="h-24 px-3 text-center text-white flex flex-col items-center justify-center" style="background: {{ $brand }};">
                @if($logo)<img src="{{ media_url($logo) }}" class="h-7 mb-1 object-contain" alt="logo">@endif
                <h1 class="text-sm font-black uppercase leading-tight">{{ $cname }}</h1>
                <p class="text-[9px] italic opacity-90">Staff Identity Card</p>
            </div>

            <div class="flex justify-center -mt-10">
                <div class="w-28 h-28 bg-gray-200 border-4 border-white rounded-xl shadow-md overflow-hidden">
                    @if($staff->passport)
                        <img src="{{ $staff->passport }}" class="w-full h-full object-cover" alt="photo">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-4xl font-black text-gray-300">{{ strtoupper(substr($staff->name,0,1)) }}</div>
                    @endif
                </div>
            </div>

            <div class="px-5 pt-3 text-center">
                <h2 class="text-lg font-bold text-gray-900 uppercase leading-tight">{{ $staff->name }}</h2>
                <p class="text-[11px] font-bold mb-3" style="color: {{ $brand }};">{{ strtoupper(str_replace('_',' ',$staff->role)) }}</p>

                <div class="space-y-1.5 text-left bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <div>
                        <p class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Staff ID</p>
                        <p class="text-xs font-bold text-gray-800 font-mono">{{ $staff->staff_id ?? '—' }}</p>
                    </div>
                    @if($staff->department)
                    <div>
                        <p class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Department</p>
                        <p class="text-xs font-bold text-gray-800">{{ $staff->department }}</p>
                    </div>
                    @endif
                    @if($staff->phone)
                    <div>
                        <p class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Phone</p>
                        <p class="text-xs font-bold text-gray-800">{{ $staff->phone }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Holder signature slot (the signature itself is added later) --}}
            <div class="absolute bottom-3 left-0 w-full px-5">
                <div class="w-40">
                    <div class="h-7 flex items-end justify-center">
                        @if(!empty($staff->signature))<img src="{{ media_url($staff->signature) }}" class="h-7 object-contain" alt="signature">@endif
                    </div>
                    <div class="border-t border-gray-400 pt-0.5">
                        <p class="text-[9px] text-gray-500 text-center">Holder's Signature</p>
                    </div>
                </div>
                <p class="text-[8px] text-gray-400 mt-1">Issued {{ now()->format('M Y') }}</p>
            </div>
        </div>

        {{-- ============================= BACK ============================= --}}
        <div class="id-card w-[320px] h-[500px] bg-white border border-gray-300 shadow-2xl rounded-2xl overflow-hidden relative flex flex-col">
            <div class="h-9 text-white flex items-center justify-center text-[11px] font-bold uppercase tracking-wide shrink-0" style="background: {{ $brand }};">
                {{ $cname }}
            </div>

            <div class="p-5 flex-1 flex flex-col">
                <h3 class="text-[11px] font-black uppercase text-gray-700 mb-2">Conditions of Use</h3>
                <ul class="text-[9px] text-gray-600 space-y-1 list-disc ml-3">
                    <li>This card remains the property of the College and must be surrendered on demand.</li>
                    <li>It is not transferable and identifies the holder as a member of staff.</li>
                    <li>Loss or damage must be reported to the College authorities immediately.</li>
                </ul>

                <div class="mt-4 text-[10px] text-gray-700 space-y-1">
                    <p><span class="font-bold">Email:</span> {{ $staff->email }}</p>
                    <p><span class="font-bold">College:</span> {{ $cname }}</p>
                </div>

                {{-- Code strip --}}
                <div class="mt-4 h-9 bg-gray-100 rounded flex items-center justify-center text-[10px] text-gray-600 font-mono tracking-[0.2em]">
                    {{ $staff->staff_id ?? $staff->email }}
                </div>

                {{-- Authorising signature slot --}}
                <div class="mt-auto pt-6">
                    <div class="w-44">
                        <div class="h-6"></div>
                        <div class="border-t border-gray-400 pt-0.5">
                            <p class="text-[9px] text-gray-500">Authorised Signatory · Registrar</p>
                        </div>
                    </div>
                    <p class="text-[8px] text-gray-400 mt-3 text-center">If found, please return to {{ $cname }}.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-2 no-print pb-10">
        <a href="{{ route('staff.show', $staff) }}" class="text-sm text-gray-500 font-bold">← Back to Profile</a>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            .id-card, .id-card * { visibility: visible; }
            .id-card { box-shadow: none !important; border: 1px solid #bbb !important; }
            .no-print { display: none !important; }
            @page { margin: 12mm; }
        }
    </style>
</x-app-layout>
