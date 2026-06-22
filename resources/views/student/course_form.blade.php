<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📘 My Course Form</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex justify-end">
                <a href="{{ route('student.course-form.pdf') }}" class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">⬇ Download PDF</a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border p-6">
                {{-- Biodata --}}
                <div class="grid sm:grid-cols-3 gap-4 text-sm border-b pb-4 mb-4">
                    <div><p class="text-[10px] uppercase font-bold text-gray-400">Name</p><p class="font-semibold text-gray-800">{{ $student->full_name }}</p></div>
                    <div><p class="text-[10px] uppercase font-bold text-gray-400">Reg. Number</p><p class="font-semibold text-gray-800 font-mono">{{ $student->registration_number }}</p></div>
                    <div><p class="text-[10px] uppercase font-bold text-gray-400">Session</p><p class="font-semibold text-gray-800">{{ $session }}</p></div>
                    <div><p class="text-[10px] uppercase font-bold text-gray-400">Department</p><p class="font-semibold text-gray-800">{{ $student->department->name ?? '—' }}</p></div>
                    <div><p class="text-[10px] uppercase font-bold text-gray-400">Course of Study</p><p class="font-semibold text-gray-800">{{ $student->program->name ?? '—' }}</p></div>
                    <div><p class="text-[10px] uppercase font-bold text-gray-400">Level</p><p class="font-semibold text-gray-800">{{ $student->level }}</p></div>
                </div>

                @forelse($bySemester as $semester => $courses)
                    <h3 class="font-bold text-gray-700 mt-4 mb-1">{{ $semester }} — Level {{ $student->level }}</h3>
                    <div class="overflow-x-auto border rounded-xl">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-4 py-2 text-left">S/N</th>
                                    <th class="px-4 py-2 text-left">Course Code</th>
                                    <th class="px-4 py-2 text-left">Course Title</th>
                                    <th class="px-4 py-2 text-center">Unit</th>
                                    <th class="px-4 py-2 text-left">Course Registration</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @foreach($courses as $i => $c)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-4 py-2 font-mono text-gray-700">{{ $c->course_code }}</td>
                                        <td class="px-4 py-2 font-semibold text-gray-800">{{ $c->name }}</td>
                                        <td class="px-4 py-2 text-center">{{ $c->course_unit }}</td>
                                        <td class="px-4 py-2 text-gray-300 italic">(lecturer signs)</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 font-bold">
                                    <td colspan="3" class="px-4 py-2 text-right">{{ $semester }} total units</td>
                                    <td class="px-4 py-2 text-center">{{ (int) $courses->sum('course_unit') }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-400">No courses have been published for your programme and level yet. They'll appear here automatically once your department adds them.</div>
                @endforelse

                @if($totalUnits)
                    <p class="text-right font-bold text-gray-800 mt-4">Total Credit Units: {{ $totalUnits }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
