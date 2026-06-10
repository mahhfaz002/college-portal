<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📚 Courses</h2>
    </x-slot>

    @php $canManage = auth()->user()->canManage('manage_subjects'); @endphp

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            {{-- Department -> Program filter --}}
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <select name="department_id" onchange="this.form.submit()" class="border-gray-300 rounded-lg">
                        <option value="">All departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected($selectedDept == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <select name="program_id" onchange="this.form.submit()" class="border-gray-300 rounded-lg">
                        <option value="">All programs</option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}" @selected($selectedProg == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($canManage)
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-gray-700 mb-4">Add Course</h3>
                <form method="POST" action="{{ route('subjects.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @csrf
                    <input name="name" required placeholder="Course title" class="border-gray-300 rounded-lg md:col-span-2">
                    <input name="course_code" placeholder="Course code (e.g. SLT 101)" class="border-gray-300 rounded-lg">
                    <input name="course_unit" type="number" min="0" max="12" placeholder="Unit" class="border-gray-300 rounded-lg">
                    <select name="department_id" class="border-gray-300 rounded-lg">
                        <option value="">— Department —</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected($selectedDept == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <select name="program_id" class="border-gray-300 rounded-lg">
                        <option value="">— Program —</option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}" @selected($selectedProg == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <button class="bg-indigo-600 text-white rounded-lg font-bold px-4 py-2 hover:bg-indigo-700 md:col-span-2">+ Add Course</button>
                </form>
            </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="text-left px-6 py-3">Title</th>
                            <th class="text-left px-6 py-3">Code</th>
                            <th class="text-left px-6 py-3">Unit</th>
                            <th class="text-left px-6 py-3">Program</th>
                            <th class="text-left px-6 py-3">Lecturer(s)</th>
                            @if($canManage)<th class="text-right px-6 py-3">Actions</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($subjects as $course)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $course->name }}</td>
                                <td class="px-6 py-3">{{ $course->course_code ?? '—' }}</td>
                                <td class="px-6 py-3">{{ $course->course_unit ?? '—' }}</td>
                                <td class="px-6 py-3">{{ $course->program->name ?? ($course->department->name ?? '—') }}</td>
                                <td class="px-6 py-3">{{ $course->teachers->pluck('name')->join(', ') ?: '—' }}</td>
                                @if($canManage)
                                <td class="px-6 py-3 text-right">
                                    <form method="POST" action="{{ route('subjects.destroy', $course) }}" onsubmit="return confirm('Remove this course?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-6 text-center text-gray-400">No courses yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
