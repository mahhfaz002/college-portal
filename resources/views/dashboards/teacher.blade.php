<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                👩‍🏫 Teacher Portal: <span class="text-indigo-600">{{ $selectedClass }}</span>
            </h2>
            <div class="text-sm font-medium text-gray-500">
                Session: 2025/2026 | Term: 1st Term
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-medium">{{ session('success') }}</div>
            @endif

            <!-- Clock in / out -->
            <div class="mb-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Daily Clock</p>
                    <div class="mt-1 text-sm text-gray-700">
                        @if($clock && $clock->clock_in)
                            <span class="font-bold text-green-600">In:</span> {{ $clock->clock_in->format('h:i A') }}
                        @else
                            <span class="text-gray-400">Not clocked in yet</span>
                        @endif
                        @if($clock && $clock->clock_out)
                            &nbsp;|&nbsp; <span class="font-bold text-red-600">Out:</span> {{ $clock->clock_out->format('h:i A') }}
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <form action="{{ route('clock.in') }}" method="POST">@csrf
                        <button class="bg-green-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-green-700 text-sm" {{ $clock && $clock->clock_in ? 'disabled style=opacity:.5' : '' }}>🟢 Clock In</button>
                    </form>
                    <form action="{{ route('clock.out') }}" method="POST">@csrf
                        <button class="bg-red-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-red-700 text-sm">🔴 Clock Out</button>
                    </form>
                </div>
            </div>

            <!-- Today's lessons (from approved timetable) -->
            @if(isset($todayLessons) && $todayLessons->count())
            <div class="mb-8 bg-white rounded-xl shadow-sm border border-blue-200 overflow-hidden">
                <div class="px-6 py-4 bg-blue-50 border-b flex justify-between items-center">
                    <h3 class="font-bold text-blue-800">🗓️ Today's Lessons ({{ now()->format('l') }})</h3>
                    <a href="{{ route('timetable.index') }}" class="text-xs font-bold text-blue-700">Full week →</a>
                </div>
                <table class="w-full text-left text-sm">
                    <tbody>
                        @foreach($todayLessons as $lesson)
                        <tr class="border-b">
                            <td class="p-3 font-bold text-gray-500 w-24">{{ $lesson->start_time }}–{{ $lesson->end_time }}</td>
                            <td class="p-3 font-bold text-gray-800">{{ $lesson->subject->name ?? '' }}</td>
                            <td class="p-3 text-indigo-600">{{ $lesson->class_arm }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <!-- My classes + attendance status -->
            @if(isset($classStatus) && $classStatus->count())
            <div class="mb-8 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">My Classes</h3></div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($classStatus as $cls)
                        <div class="p-4 border rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800">{{ $cls['name'] }}</span>
                                @if($cls['attendance_taken'])
                                    <span class="text-[10px] font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">✅ Marked</span>
                                @else
                                    <span class="text-[10px] font-bold text-red-700 bg-red-100 px-2 py-0.5 rounded-full">Pending</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ $cls['student_count'] }} students</p>
                            <a href="{{ route('attendance.index', ['class' => $cls['name']]) }}" class="inline-block mt-2 text-xs font-bold text-indigo-600 hover:underline">Mark attendance →</a>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Exam tasks -->
            @if((isset($authorExams) && $authorExams->count()) || (isset($gradeExams) && $gradeExams->count()))
            <div class="mb-8 bg-white rounded-xl shadow-sm border border-indigo-200 overflow-hidden">
                <div class="px-6 py-4 bg-indigo-50 border-b"><h3 class="font-bold text-indigo-800">📋 Exam Tasks</h3></div>
                <div class="p-4 space-y-2">
                    @foreach($authorExams as $exam)
                    <div class="flex justify-between items-center p-3 border rounded-lg">
                        <div>
                            <span class="font-bold text-gray-800">{{ $exam->title }}</span>
                            <span class="text-xs text-gray-500">— {{ $exam->subject->name ?? '' }}</span>
                            <span class="ml-2 text-[10px] font-bold text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded-full">Author questions</span>
                        </div>
                        <a href="{{ route('exams.questions', $exam) }}" class="bg-indigo-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-indigo-700">Set Questions →</a>
                    </div>
                    @endforeach
                    @foreach($gradeExams as $exam)
                    <div class="flex justify-between items-center p-3 border rounded-lg">
                        <div>
                            <span class="font-bold text-gray-800">{{ $exam->title }}</span>
                            <span class="text-xs text-gray-500">— {{ $exam->subject->name ?? '' }}</span>
                            <span class="ml-2 text-[10px] font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">{{ $exam->submissions_count }} to grade</span>
                        </div>
                        <a href="{{ route('exams.grade', $exam) }}" class="bg-green-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-green-700">Grade →</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!$attendanceTaken)
            <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 flex justify-between items-center rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <span class="text-2xl mr-3">⚠️</span>
                    <p class="font-bold">Attendance for today has not been marked yet.</p>
                </div>
                <a href="{{ route('attendance.index') }}" class="bg-yellow-600 text-white px-4 py-2 rounded font-bold hover:bg-yellow-700 transition">Mark Now</a>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">My Students</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $studentCount }}</h3>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Attendance Today</p>
                    <div class="flex items-center mt-1">
                        @if($attendanceTaken)
                            <span class="text-green-500 font-bold text-lg">✅ Completed</span>
                        @else
                            <span class="text-red-500 font-bold text-lg">❌ Pending</span>
                        @endif
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Results Status</p>
                    <span class="inline-block mt-1 px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">Mid-Term Processing</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <a href="{{ route('attendance.index') }}" class="flex items-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:border-indigo-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-2xl mr-4 group-hover:bg-indigo-600 group-hover:text-white transition">📝</div>
                    <div>
                        <h4 class="font-bold text-gray-800">Attendance Register</h4>
                        <p class="text-sm text-gray-500">Mark daily student presence and lateness.</p>
                    </div>
                </a>

                <a href="{{ route('scores.create') }}" class="flex items-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:border-green-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-2xl mr-4 group-hover:bg-green-600 group-hover:text-white transition">📊</div>
                    <div>
                        <h4 class="font-bold text-gray-800">Score Entry</h4>
                        <p class="text-sm text-gray-500">Enter CA and Examination marks for your class.</p>
                    </div>
                </a>
            </div>

            <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                    <h3 class="font-bold text-gray-700">Class List - {{ $selectedClass }}</h3>
                </div>
                <table class="w-full text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Admission No</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($students as $student)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $student->admission_number }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-800">{{ $student->full_name }}</td>
                            <td class="px-6 py-4">
                                <a href="#" class="text-indigo-600 hover:text-indigo-900 text-xs font-bold">Profile</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
