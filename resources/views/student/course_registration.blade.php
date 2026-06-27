<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Course Registration</h2>
            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border">
                {{ $term }} &bull; {{ $session }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Credit Unit Tracker --}}
            @if($maxUnits)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold text-gray-700">Credit Units</h3>
                    <span class="text-sm font-bold {{ $registeredUnits >= $maxUnits ? 'text-red-600' : 'text-emerald-600' }}">
                        {{ $registeredUnits }} / {{ $maxUnits }} units used
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="h-3 rounded-full transition-all {{ $registeredUnits >= $maxUnits ? 'bg-red-500' : 'bg-emerald-500' }}"
                         style="width: {{ min(100, ($registeredUnits / max(1, $maxUnits)) * 100) }}%"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    {{ max(0, $maxUnits - $registeredUnits) }} unit(s) remaining
                    @if($isUG) (UG max: {{ $maxUnits }} per semester) @endif
                </p>
            </div>
            @endif

            {{-- Registered Courses --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-emerald-50 border-b">
                    <h3 class="font-bold text-emerald-900">Registered Courses ({{ $registrations->count() }})</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="text-left px-6 py-3">Course</th>
                            <th class="text-left px-6 py-3">Code</th>
                            <th class="text-center px-6 py-3">Units</th>
                            <th class="text-center px-6 py-3">Type</th>
                            <th class="text-right px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($registrations as $reg)
                        <tr>
                            <td class="px-6 py-3 font-semibold text-gray-800">{{ $reg->subject->name ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $reg->subject->course_code ?? '—' }}</td>
                            <td class="px-6 py-3 text-center font-bold">{{ $reg->subject->course_unit ?? 0 }}</td>
                            <td class="px-6 py-3 text-center">
                                @if($reg->is_carryover)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Carryover</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Regular</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right">
                                @if($reg->is_carryover)
                                    <span class="text-xs text-gray-400">Cannot drop</span>
                                @else
                                    <form method="POST" action="{{ route('course-registration.drop') }}"
                                          onsubmit="return confirm('Drop this course?')">
                                        @csrf
                                        <input type="hidden" name="registration_id" value="{{ $reg->id }}">
                                        <button class="text-red-600 text-xs font-bold hover:underline">Drop</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400">No courses registered yet.</td></tr>
                        @endforelse
                    </tbody>
                    @if($registrations->isNotEmpty())
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="px-6 py-3 font-bold text-gray-700">Total</td>
                            <td class="px-6 py-3 text-center font-black text-indigo-600">{{ $registeredUnits }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            {{-- Available Courses to Add --}}
            @if($availableCourses->isNotEmpty())
            <form method="POST" action="{{ route('course-registration.add') }}" id="addForm"
                  class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @csrf
                <div class="px-6 py-4 bg-blue-50 border-b">
                    <h3 class="font-bold text-blue-900">Available Courses — Add to Registration</h3>
                    <p class="text-xs text-blue-700">Select courses to register then click "Add Selected Courses".</p>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="text-center px-4 py-3 w-12">Select</th>
                            <th class="text-left px-4 py-3">Course</th>
                            <th class="text-left px-4 py-3">Code</th>
                            <th class="text-center px-4 py-3">Units</th>
                            <th class="text-left px-4 py-3">Level</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($availableCourses as $course)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" name="subject_ids[]" value="{{ $course->id }}"
                                       data-units="{{ $course->course_unit }}"
                                       onchange="updateUnitPreview()"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $course->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $course->course_code }}</td>
                            <td class="px-4 py-3 text-center font-bold">{{ $course->course_unit }}</td>
                            <td class="px-4 py-3 text-gray-500">L{{ $course->level }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t bg-gray-50 flex items-center justify-between">
                    <div>
                        <span class="text-sm text-gray-600">Selected: <strong id="selectedUnits">0</strong> unit(s)</span>
                        @if($maxUnits)
                            <span class="text-sm text-gray-600 ml-3">|</span>
                            <span class="text-sm text-gray-600 ml-3">Will total: <strong id="totalAfterAdd">{{ $registeredUnits }}</strong> / {{ $maxUnits }}</span>
                        @endif
                    </div>
                    <button type="button" onclick="submitAddForm()"
                            class="bg-emerald-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-emerald-700 transition">
                        Add Selected Courses
                    </button>
                </div>
            </form>
            @else
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center text-gray-500 text-sm">
                No additional courses available for this semester and level.
            </div>
            @endif
        </div>
    </div>

    <script>
        const maxUnits = {{ $maxUnits ?? 'null' }};
        const currentUnits = {{ $registeredUnits }};

        function updateUnitPreview() {
            const checkboxes = document.querySelectorAll('input[name="subject_ids[]"]:checked');
            let selectedUnits = 0;
            checkboxes.forEach(cb => { selectedUnits += parseInt(cb.dataset.units || 0); });
            document.getElementById('selectedUnits').textContent = selectedUnits;
            const totalEl = document.getElementById('totalAfterAdd');
            if (totalEl) totalEl.textContent = currentUnits + selectedUnits;
        }

        function submitAddForm() {
            const checkboxes = document.querySelectorAll('input[name="subject_ids[]"]:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one course to add.');
                return;
            }
            let selectedUnits = 0;
            checkboxes.forEach(cb => { selectedUnits += parseInt(cb.dataset.units || 0); });

            if (maxUnits && (currentUnits + selectedUnits) > maxUnits) {
                const remaining = maxUnits - currentUnits;
                alert('You have ' + remaining + ' credit unit(s) remaining this semester. The selected courses total ' + selectedUnits + ' units. Please choose courses with fewer credit units.');
                return;
            }
            document.getElementById('addForm').submit();
        }
    </script>
</x-app-layout>
