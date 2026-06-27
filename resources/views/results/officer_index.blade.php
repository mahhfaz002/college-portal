<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Result Management</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('results.officer.index') }}" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Programme</label>
                        <select name="program_id" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">— Select Programme —</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}" {{ $filterProgram == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ optional($p->department)->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Level</label>
                        <select name="level" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">— All Levels —</option>
                            @foreach($levels as $lv)
                                <option value="{{ $lv }}" {{ $filterLevel == $lv ? 'selected' : '' }}>Level {{ $lv }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Semester</label>
                        <select name="semester" onchange="this.form.submit()" class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="First Semester" {{ $filterSemester === 'First Semester' ? 'selected' : '' }}>First Semester</option>
                            <option value="Second Semester" {{ $filterSemester === 'Second Semester' ? 'selected' : '' }}>Second Semester</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <p class="text-xs text-gray-400 italic">{{ $term }} &bull; {{ $session }}</p>
                    </div>
                </div>
            </form>

            {{-- Course list with status --}}
            @if($filterProgram && $courses->count())
                @php
                    $grouped = $courses->groupBy('level');
                @endphp

                @foreach($grouped as $level => $levelCourses)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                        <h3 class="font-bold text-gray-700">Level {{ $level }} — {{ $filterSemester }}</h3>
                        @php
                            $allSubmitted = $levelCourses->every(fn($c) => in_array($c->result_status, ['submitted','transmitted']));
                            $anyTransmitted = $levelCourses->contains(fn($c) => $c->result_status === 'transmitted');
                            $allTransmitted = $levelCourses->every(fn($c) => $c->result_status === 'transmitted');
                        @endphp
                        @if($allTransmitted)
                            <span class="text-xs font-bold text-green-700 bg-green-100 px-3 py-1 rounded-full">All Transmitted</span>
                        @endif
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="text-left px-6 py-3">Course</th>
                                <th class="text-left px-6 py-3">Code</th>
                                <th class="text-center px-6 py-3">Units</th>
                                <th class="text-center px-6 py-3">Status</th>
                                <th class="text-right px-6 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($levelCourses as $course)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $course->name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $course->course_code }}</td>
                                <td class="px-6 py-3 text-center">{{ $course->course_unit }}</td>
                                <td class="px-6 py-3 text-center">
                                    @if($course->result_status === 'transmitted')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">Transmitted</span>
                                    @elseif($course->result_status === 'submitted')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Submitted</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if($course->result_status === 'submitted')
                                        <a href="{{ route('results.officer.show', $course) }}" class="text-indigo-600 font-bold hover:underline text-sm">View & Edit</a>
                                    @elseif($course->result_status === 'transmitted')
                                        <span class="text-xs text-gray-400">Locked</span>
                                    @else
                                        <span class="text-xs text-gray-400">Awaiting lecturer</span>
                                    @endif
                                    @if($course->submission && $course->submission->scan_path)
                                        <a href="{{ route('results.officer.scan', $course->submission) }}" target="_blank" class="ml-2 text-xs text-blue-600 hover:underline">Scan</a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if($allSubmitted && !$allTransmitted)
                    <div class="px-6 py-4 border-t bg-gray-50 flex justify-end">
                        <button type="button" onclick="showTransmitModal({{ $level }}, '{{ $filterSemester }}')"
                                class="bg-red-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-red-700 transition">
                            Transmit Results for Level {{ $level }}
                        </button>
                    </div>
                    @endif
                </div>
                @endforeach
            @elseif($filterProgram)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-6 py-4 rounded-lg text-sm">
                    No courses found for the selected programme and filters.
                </div>
            @else
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-6 py-4 rounded-lg text-sm">
                    Select a programme above to view submitted results.
                </div>
            @endif
        </div>
    </div>

    {{-- Transmit Confirmation Modal --}}
    <div id="transmitModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-4 p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900">Transmit Results?</h3>
            </div>
            <div class="space-y-3 text-sm text-gray-600">
                <p>Once you transmit these results, <strong class="text-red-600">you will no longer be able to edit them</strong>.</p>
                <p>The results will be automatically made available to all registered students (who pay the result viewing fee).</p>
            </div>
            <div class="mt-8 flex gap-3">
                <button type="button" onclick="hideTransmitModal()"
                        class="flex-1 bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition">
                    Continue Editing
                </button>
                <form method="POST" action="{{ route('results.officer.transmit') }}" id="transmitForm" class="flex-1">
                    @csrf
                    <input type="hidden" name="program_id" value="{{ $filterProgram }}">
                    <input type="hidden" name="level" id="transmitLevel" value="">
                    <input type="hidden" name="semester" id="transmitSemester" value="">
                    <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 transition">
                        Transmit
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTransmitModal(level, semester) {
            document.getElementById('transmitLevel').value = level;
            document.getElementById('transmitSemester').value = semester;
            document.getElementById('transmitModal').classList.remove('hidden');
            document.getElementById('transmitModal').classList.add('flex');
        }
        function hideTransmitModal() {
            document.getElementById('transmitModal').classList.add('hidden');
            document.getElementById('transmitModal').classList.remove('flex');
        }
    </script>
</x-app-layout>
