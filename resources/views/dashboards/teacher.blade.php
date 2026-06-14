<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            👩‍🏫 Lecturer Portal
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
                <div class="mb-2 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-medium">{{ session('success') }}</div>
            @endif

            {{-- Exam tasks --}}
            @if((isset($authorExams) && $authorExams->count()) || (isset($gradeExams) && $gradeExams->count()))
            <div class="bg-white rounded-xl shadow-sm border border-indigo-200 overflow-hidden">
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

            {{-- Assigned courses — click a course to see its registered students --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">My Courses</h3></div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @forelse($courses as $c)
                        <a href="{{ route('lecturer.course-students', $c) }}"
                           class="block p-4 border rounded-lg hover:border-indigo-500 hover:shadow-sm transition">
                            <p class="font-bold text-gray-800">{{ $c->name }}</p>
                            <p class="text-xs text-gray-500">{{ $c->course_code }} · {{ optional($c->program)->name }}{{ $c->level ? ' · L'.$c->level : '' }}</p>
                            <p class="text-xs font-bold text-indigo-600 mt-2">View registered students →</p>
                        </a>
                    @empty
                        <p class="text-sm text-gray-400 col-span-full text-center py-8">
                            No courses have been assigned to you yet. Your HOD or the Academic Secretary will assign your courses.
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('scores.create') }}" class="flex items-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:border-green-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-2xl mr-4 group-hover:bg-green-600 group-hover:text-white transition">📊</div>
                    <div>
                        <h4 class="font-bold text-gray-800">Score Entry</h4>
                        <p class="text-sm text-gray-500">Enter CA and Examination marks for your course.</p>
                    </div>
                </a>

                @can('author_questions')
                <a href="{{ route('exams.my') }}" class="flex items-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:border-indigo-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-2xl mr-4 group-hover:bg-indigo-600 group-hover:text-white transition">📝</div>
                    <div>
                        <h4 class="font-bold text-gray-800">Set Exam Questions</h4>
                        <p class="text-sm text-gray-500">Author objective &amp; theory questions for your courses.</p>
                    </div>
                </a>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
