<x-app-layout>
    <div class="py-8 no-print text-center">
        <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold shadow-lg hover:bg-blue-700">
            🖨️ Print ID Card (Front &amp; Back)
        </button>
        <p class="text-gray-500 mt-3 text-sm">Tip: set layout to "Portrait" and Scale to "100%". The front and back both print.</p>
    </div>

    @php
        $brand = current_college()?->primary_color ?? ($school['color'] ?? '#1e3a8a');
        $logo  = current_college()?->logo_path ?? ($school['logo'] ?? null);
        $cname = $school['name'] ?? (current_college()?->name ?? 'College Portal');
    @endphp

    <div class="flex flex-wrap justify-center items-start gap-8 font-sans pb-12">

        {{-- ============================= FRONT ============================= --}}
        <div class="id-card w-[320px] h-[500px] bg-white border border-gray-300 shadow-2xl rounded-2xl overflow-hidden relative">
            <div class="h-24 px-3 text-center text-white flex flex-col items-center justify-center" style="background: {{ $brand }};">
                @if($logo)<img src="{{ media_url($logo) }}" class="h-7 mb-1 object-contain" alt="logo">@endif
                <h1 class="text-sm font-black uppercase leading-tight">{{ $cname }}</h1>
                <p class="text-[9px] italic opacity-90">{{ $school['tagline'] ?? 'Student Identity Card' }}</p>
            </div>

            <div class="flex justify-center -mt-10">
                <div class="w-28 h-28 bg-gray-200 border-4 border-white rounded-xl shadow-md overflow-hidden">
                    @if($student->photo)
                        <img src="{{ media_url($student->photo) }}" class="w-full h-full object-cover" alt="photo">
                    @else
                        <svg class="w-full h-full text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                    @endif
                </div>
            </div>

            <div class="px-5 pt-3 text-center">
                <h2 class="text-lg font-bold text-gray-900 uppercase leading-tight">{{ $student->full_name }}</h2>
                <p class="text-[11px] font-bold mb-3" style="color: {{ $brand }};">STUDENT</p>

                <div class="space-y-1.5 text-left bg-gray-50 p-3 rounded-lg border border-gray-100">
                    <div>
                        <p class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Reg / Admission No</p>
                        <p class="text-xs font-bold text-gray-800">{{ $student->registration_number ?? $student->admission_number }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Programme</p>
                        <p class="text-xs font-bold text-gray-800">{{ $student->class_arm }}</p>
                    </div>
                    <div class="flex gap-6">
                        <div>
                            <p class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Level</p>
                            <p class="text-xs font-bold text-gray-800">{{ $student->level ? 'L'.$student->level : '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] text-gray-500 uppercase font-bold tracking-wider">Blood Group</p>
                            <p class="text-xs font-bold text-gray-800">{{ $student->blood_group ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Holder signature slot (the signature itself is added later) --}}
            <div class="absolute bottom-3 left-0 w-full px-5">
                <div class="w-40">
                    <div class="h-7 flex items-end justify-center">
                        @if(!empty($student->signature))<img src="{{ media_url($student->signature) }}" class="h-7 object-contain" alt="signature">@endif
                    </div>
                    <div class="border-t border-gray-400 pt-0.5">
                        <p class="text-[9px] text-gray-500 text-center">Holder's Signature</p>
                    </div>
                </div>
                <p class="text-[8px] text-gray-400 mt-1">Valid for {{ date('Y') }}/{{ date('Y') + 1 }} Academic Session</p>
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
                    <li>It is not transferable and must be presented to access College facilities and examinations.</li>
                    <li>Loss or damage must be reported to the College authorities immediately.</li>
                </ul>

                <div class="mt-4 text-[10px] text-gray-700 space-y-1">
                    <p><span class="font-bold">Department:</span> {{ optional($student->department)->name ?? '—' }}</p>
                    <p><span class="font-bold">Emergency contact:</span> {{ $student->parent_phone ?? '—' }}</p>
                </div>

                {{-- Code strip --}}
                <div class="mt-4 h-9 bg-gray-100 rounded flex items-center justify-center text-[10px] text-gray-600 font-mono tracking-[0.2em]">
                    {{ $student->registration_number ?? $student->admission_number }}
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
