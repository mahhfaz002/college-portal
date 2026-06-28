<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📋 Applicant Credentials</h2>
            <a href="{{ url()->previous() }}" class="text-sm text-gray-500 font-bold">← Back</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- The request --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h3 class="font-bold text-gray-700 mb-3">Change-of-Course Request</h3>
                <div class="grid sm:grid-cols-2 gap-3 text-sm">
                    <div><span class="text-gray-400 text-xs uppercase font-bold">Student</span><p class="font-semibold">{{ optional($student)->full_name ?? '—' }}</p></div>
                    <div><span class="text-gray-400 text-xs uppercase font-bold">Reg. Number</span><p class="font-semibold">{{ optional($student)->registration_number ?? optional($student)->admission_number ?? '—' }}</p></div>
                    <div><span class="text-gray-400 text-xs uppercase font-bold">Current Course</span><p class="font-semibold">{{ optional($changeOfCourse->currentProgram)->name ?? optional($student)->class_arm ?? '—' }} <span class="text-gray-400">· {{ optional(optional($changeOfCourse->currentProgram)->department)->name }}</span></p></div>
                    <div><span class="text-gray-400 text-xs uppercase font-bold">Requested Course</span><p class="font-semibold text-indigo-700">{{ optional($changeOfCourse->requestedProgram)->name ?? '—' }} <span class="text-gray-400">· {{ optional(optional($changeOfCourse->requestedProgram)->department)->name }}</span></p></div>
                </div>
                <div class="mt-3"><span class="text-gray-400 text-xs uppercase font-bold">Student's Reason</span><p class="text-sm text-gray-700">{{ $changeOfCourse->reason }}</p></div>
            </div>

            {{-- Bio --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h3 class="font-bold text-gray-700 mb-3">Student Information</h3>
                <div class="grid sm:grid-cols-3 gap-3 text-sm">
                    <div><span class="text-gray-400 text-xs uppercase font-bold">Email</span><p>{{ optional($student)->email ?? '—' }}</p></div>
                    <div><span class="text-gray-400 text-xs uppercase font-bold">Phone</span><p>{{ optional($student)->parent_phone ?? '—' }}</p></div>
                    <div><span class="text-gray-400 text-xs uppercase font-bold">Level</span><p>{{ optional($student)->level ? 'L'.$student->level : '—' }}</p></div>
                </div>
            </div>

            {{-- Academic record (results carried over) --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Academic Record (Results)</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-6 py-2 text-left">Course</th><th class="px-6 py-2 text-left">Code</th><th class="px-6 py-2 text-right">Total</th><th class="px-6 py-2 text-left">Grade</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($scores as $s)
                            @php $total = (float) ($s->total ?? ($s->ca_score + $s->exam_score)); @endphp
                            <tr>
                                <td class="px-6 py-2">{{ optional(optional($s->exam)->subject)->name ?? '—' }}</td>
                                <td class="px-6 py-2 text-gray-500">{{ optional(optional($s->exam)->subject)->course_code ?? '—' }}</td>
                                <td class="px-6 py-2 text-right font-semibold">{{ number_format($total, 1) }}</td>
                                <td class="px-6 py-2">{{ function_exists('grade_for') ? grade_for($total) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No results recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Uploaded documents --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Uploaded Documents</div>
                <div class="p-6 grid sm:grid-cols-2 gap-3">
                    @forelse($documents as $doc)
                        <a href="{{ media_url($doc->path) }}" target="_blank" class="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 text-sm">
                            <span>📎</span>
                            <span class="font-semibold">{{ ucwords(str_replace('_',' ', $doc->type)) }}</span>
                            <span class="text-gray-400 text-xs ml-auto">{{ $doc->original_name ?? 'view' }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-gray-400 col-span-full">No documents on file.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
