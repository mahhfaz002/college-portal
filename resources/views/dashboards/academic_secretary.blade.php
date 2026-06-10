<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗂️ Academic Secretary Dashboard</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Courses</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $totalCourses }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Assigned</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $assignedCourses }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-amber-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Unassigned</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $totalCourses - $assignedCourses }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Lecturers</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $lecturers->count() }}</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-gray-700 mb-4">Assign a Course to a Lecturer</h3>
                <form method="POST" action="{{ route('course-assignments.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <select name="subject_id" required class="border-gray-300 rounded-lg">
                        <option value="">— Course —</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} {{ $c->course_code ? "($c->course_code)" : '' }}</option>
                        @endforeach
                    </select>
                    <select name="user_id" required class="border-gray-300 rounded-lg">
                        <option value="">— Lecturer —</option>
                        @foreach($lecturers as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                        @endforeach
                    </select>
                    <button class="bg-indigo-600 text-white rounded-lg font-bold px-4 py-2 hover:bg-indigo-700">Assign</button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b font-bold text-gray-700">Courses &amp; Assigned Lecturers</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="text-left px-6 py-3">Course</th>
                            <th class="text-left px-6 py-3">Code</th>
                            <th class="text-left px-6 py-3">Lecturer(s)</th>
                            <th class="text-right px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($courses as $c)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $c->name }}</td>
                                <td class="px-6 py-3">{{ $c->course_code ?? '—' }}</td>
                                <td class="px-6 py-3">
                                    @forelse($c->teachers as $t)
                                        <span class="inline-flex items-center gap-1 bg-gray-100 rounded-full px-2 py-0.5 text-xs mr-1">
                                            {{ $t->name }}
                                            <form method="POST" action="{{ route('course-assignments.destroy') }}" class="inline">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="subject_id" value="{{ $c->id }}">
                                                <input type="hidden" name="user_id" value="{{ $t->id }}">
                                                <button class="text-red-500 font-bold">&times;</button>
                                            </form>
                                        </span>
                                    @empty
                                        <span class="text-gray-400">Unassigned</span>
                                    @endforelse
                                </td>
                                <td></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No courses created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
