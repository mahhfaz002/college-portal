<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📝 Exam Officer Dashboard</h2>
            <div class="flex gap-2">
                <a href="{{ route('exams.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">+ Create Exam</a>
                <a href="{{ route('exams.queries') }}" class="bg-gray-700 text-white px-4 py-2 rounded-lg font-bold hover:bg-gray-800 text-sm">Queries ({{ $openQueries }})</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Students</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $totalStudents }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Subjects</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $subjectsCount }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-yellow-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Awaiting Compilation</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $pendingGrading }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Open Queries</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $openQueries }}</h3>
                </div>
            </div>

            <!-- Exams -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">Exams</h3>
                    <a href="{{ route('exams.index') }}" class="text-xs font-bold text-indigo-600">View all →</a>
                </div>
                <table class="w-full text-left text-sm">
                    <tbody>
                        @forelse($exams as $exam)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-bold">{{ $exam->title }}</td>
                            <td class="p-3 text-gray-500">{{ $exam->subject->name ?? '' }}</td>
                            <td class="p-3"><span class="text-[10px] uppercase font-bold px-2 py-1 rounded bg-gray-100">{{ $exam->status }}</span></td>
                            <td class="p-3 text-right"><a href="{{ route('exams.show', $exam) }}" class="text-indigo-600 font-bold text-xs">Manage →</a></td>
                        </tr>
                        @empty
                        <tr><td class="p-6 text-center text-gray-400 italic">No exams yet. Create one to begin.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Student roster: attendance + fee status -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">Student Eligibility Overview</h3></div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="text-xs uppercase text-gray-500 border-b">
                            <th class="p-2">Student</th><th class="p-2">Class</th><th class="p-2">Attendance</th><th class="p-2">Fees</th>
                        </tr></thead>
                        <tbody>
                            @foreach($roster as $r)
                            <tr class="border-b">
                                <td class="p-2 font-bold">{{ $r['student']->full_name }}</td>
                                <td class="p-2">{{ $r['student']->class_arm }}</td>
                                <td class="p-2">
                                    <span class="{{ $r['attendance_pct'] >= (int) setting('min_attendance_percent',75) ? 'text-green-600' : 'text-red-600' }} font-bold">{{ $r['attendance_pct'] }}%</span>
                                </td>
                                <td class="p-2">{{ $r['fees_cleared'] ? '✅ Cleared' : '⚠️ Owing' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
