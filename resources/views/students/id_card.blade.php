<x-app-layout>
    <div class="py-12 no-print">
        <div class="max-w-md mx-auto text-center">
            <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold shadow-lg hover:bg-blue-700">
                🖨️ Print ID Card
            </button>
            <p class="text-gray-500 mt-4 text-sm">Tip: Set layout to "Portrait" and Scale to "100%" in print settings.</p>
        </div>
    </div>

    <div class="flex justify-center items-center font-sans">
        <div id="id-card" class="w-[320px] h-[500px] bg-white border border-gray-300 shadow-2xl rounded-2xl overflow-hidden relative">

            <div class="bg-blue-800 h-24 p-4 text-center text-white">
                <h1 class="text-lg font-black uppercase leading-tight">{{ $school['name'] ?? 'School Portal' }}</h1>
                <p class="text-[10px] italic">{{ $school['tagline'] ?? '' }}</p>
            </div>

            <div class="flex justify-center -mt-10">
                <div class="w-32 h-32 bg-gray-200 border-4 border-white rounded-xl shadow-md overflow-hidden">
                    @if($student->photo)
                        <img src="{{ media_url($student->photo) }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-full h-full text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </div>
            </div>

            <div class="p-6 text-center">
                <h2 class="text-xl font-bold text-gray-900 uppercase">{{ $student->full_name }}</h2>
                <p class="text-blue-600 font-bold text-sm mb-4">STUDENT</p>

                <div class="space-y-2 text-left bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Admission No</p>
                    <p class="text-sm font-bold text-gray-800 -mt-1">{{ $student->admission_number }}</p>

                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Programme</p>
                    <p class="text-sm font-bold text-gray-800 -mt-1">{{ $student->class_arm }}</p>

                    <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider">Blood Group</p>
                    <p class="text-sm font-bold text-gray-800 -mt-1">{{ $student->blood_group ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="absolute bottom-0 w-full p-4 border-t bg-gray-50 flex flex-col items-center">
                <div class="h-8 w-48 bg-gray-300 mb-1 rounded flex items-center justify-center text-[10px] text-gray-500 font-mono italic">
                    ||||||||||||||||||||||||||||||||||
                </div>
                <p class="text-[8px] text-gray-400">Valid for {{ date('Y') }}/{{ date('Y') + 1 }} Academic Session</p>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #id-card, #id-card * { visibility: visible; }
            #id-card {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                border: none !important;
                box-shadow: none !important;
            }
            .no-print { display: none !important; }
        }
    </style>
</x-app-layout>
