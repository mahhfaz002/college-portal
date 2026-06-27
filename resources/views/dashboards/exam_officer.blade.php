<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📝 Exam Officer Dashboard</h2>
            <div class="flex gap-2">
                <a href="{{ route('results.officer.index') }}" class="bg-amber-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-amber-700 text-sm">Result Management</a>
                <a href="{{ route('exams.papers') }}" class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-emerald-700 text-sm">Question Papers</a>
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
                    <p class="text-xs font-bold text-gray-400 uppercase">Courses</p>
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

            {{-- Result Submissions Overview --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-gray-700">Result Submissions</h3>
                    <a href="{{ route('results.officer.index') }}" class="text-sm text-indigo-600 font-bold hover:underline">Manage Results &rarr;</a>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <p class="text-xs font-bold text-blue-500 uppercase">Awaiting Review</p>
                        <p class="text-2xl font-black text-blue-700">{{ $submittedResults }}</p>
                        <p class="text-xs text-blue-500">Submitted by lecturers, ready to edit & transmit</p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                        <p class="text-xs font-bold text-green-500 uppercase">Transmitted</p>
                        <p class="text-2xl font-black text-green-700">{{ $transmittedResults }}</p>
                        <p class="text-xs text-green-500">Finalized and sent to students</p>
                    </div>
                </div>
            </div>

            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <!-- Exam Mode -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-700 mb-1 flex items-center gap-2">⏳ Exam Mode</h3>
                @if($examCycle)
                    <p class="text-sm text-gray-500 mb-4">Exam Mode is <span class="font-bold text-emerald-600">ACTIVE</span> — a countdown is live on every dashboard, and lecturers have a question-submission deadline.</p>
                    <div class="grid sm:grid-cols-3 gap-4 mb-4">
                        <div class="p-4 bg-gray-50 rounded-lg border">
                            <p class="text-xs font-bold text-gray-400 uppercase">Title</p>
                            <p class="font-bold text-gray-800">{{ $examCycle->title }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg border">
                            <p class="text-xs font-bold text-gray-400 uppercase">Exams start</p>
                            <p class="font-bold text-gray-800">{{ $examCycle->exam_start_at->format('D, d M Y g:ia') }}</p>
                        </div>
                        <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                            <p class="text-xs font-bold text-amber-500 uppercase">Questions due (−5 days)</p>
                            <p class="font-bold text-amber-700">{{ $examCycle->submission_deadline_at->format('D, d M Y g:ia') }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('exam-mode.close', $examCycle) }}" onsubmit="return confirm('Close Exam Mode? The countdown will stop showing.')">
                        @csrf
                        <button class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg font-bold hover:bg-gray-300 text-sm">Close Exam Mode</button>
                    </form>
                @else
                    <p class="text-sm text-gray-500 mb-4">Activate Exam Mode to notify all staff &amp; students and start a countdown on every dashboard. The lecturers' question-submission deadline is set automatically to <strong>5 days before</strong> the exam start.</p>
                    <form method="POST" action="{{ route('exam-mode.activate') }}" class="grid sm:grid-cols-3 gap-4 items-end">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Title</label>
                            <input name="title" placeholder="e.g. First Semester Exams" value="{{ old('title') }}" class="w-full border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Exam start date &amp; time *</label>
                            <input name="exam_start_at" type="datetime-local" required value="{{ old('exam_start_at') }}" class="w-full border-gray-300 rounded-lg text-sm">
                        </div>
                        <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-indigo-700 text-sm h-10">⚡ Activate Exam Mode</button>
                    </form>
                @endif
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
                            <th class="p-2">Student</th><th class="p-2">Programme</th><th class="p-2">Fees</th>
                        </tr></thead>
                        <tbody>
                            @foreach($roster as $r)
                            <tr class="border-b">
                                <td class="p-2 font-bold">{{ $r['student']->full_name }}</td>
                                <td class="p-2">{{ $r['student']->class_arm }}</td>
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
