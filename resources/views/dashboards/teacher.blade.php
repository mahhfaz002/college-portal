<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lecturer Portal
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
                <div class="mb-2 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-medium">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-2 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm font-medium">{{ session('error') }}</div>
            @endif

            {{-- All results submitted: thank you banner --}}
            @if($allSubmitted)
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-black text-emerald-900 text-lg">Thank You!</h3>
                            <p class="text-sm text-emerald-800 mt-1">You have successfully submitted all results for {{ $term }}, {{ $session }}. Thank you for a complete and successful semester.</p>
                            <p class="text-sm text-emerald-700 mt-2">Please anticipate another course allocation when the new semester starts.</p>
                            <p class="text-xs text-emerald-600 mt-3 font-bold">Remember to submit the physical copies of all results to the Exam Officer within 72 hours of digital submission.</p>
                        </div>
                    </div>
                </div>

            @endif

            @if(!($allSubmitted && !$isHod))
            {{-- Exam tasks --}}
            @if((isset($authorExams) && $authorExams->count()) || (isset($gradeExams) && $gradeExams->count()))
            <div class="bg-white rounded-xl shadow-sm border border-indigo-200 overflow-hidden">
                <div class="px-6 py-4 bg-indigo-50 border-b"><h3 class="font-bold text-indigo-800">Exam Tasks</h3></div>
                <div class="p-4 space-y-2">
                    @foreach($authorExams as $exam)
                    <div class="flex justify-between items-center p-3 border rounded-lg">
                        <div>
                            <span class="font-bold text-gray-800">{{ $exam->title }}</span>
                            <span class="text-xs text-gray-500">— {{ $exam->subject->name ?? '' }}</span>
                            <span class="ml-2 text-[10px] font-bold text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded-full">Author questions</span>
                        </div>
                        <a href="{{ route('exams.questions', $exam) }}" class="bg-indigo-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-indigo-700">Set Questions</a>
                    </div>
                    @endforeach
                    @foreach($gradeExams as $exam)
                    <div class="flex justify-between items-center p-3 border rounded-lg">
                        <div>
                            <span class="font-bold text-gray-800">{{ $exam->title }}</span>
                            <span class="text-xs text-gray-500">— {{ $exam->subject->name ?? '' }}</span>
                            <span class="ml-2 text-[10px] font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">{{ $exam->submissions_count }} to grade</span>
                        </div>
                        <a href="{{ route('exams.grade', $exam) }}" class="bg-green-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-green-700">Grade</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Assigned courses --}}
            @if($courses->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">My Courses</h3></div>
                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($courses as $c)
                        @php $isSubmitted = in_array($c->id, $submittedIds); @endphp
                        <div class="block p-4 border rounded-lg {{ $isSubmitted ? 'bg-gray-50 opacity-60' : 'hover:border-indigo-500 hover:shadow-sm' }} transition">
                            <p class="font-bold text-gray-800">{{ $c->name }}</p>
                            <p class="text-xs text-gray-500">{{ $c->course_code }} · {{ optional($c->program)->name }}{{ $c->level ? ' · L'.$c->level : '' }}</p>
                            @if($isSubmitted)
                                <p class="text-xs font-bold text-green-600 mt-2">Results Submitted</p>
                            @else
                                <div class="mt-2 flex gap-2">
                                    <a href="{{ route('lecturer.course-students', $c) }}" class="text-xs font-bold text-indigo-600 hover:underline">Students</a>
                                    <a href="{{ route('results.submit.create', $c) }}" class="text-xs font-bold text-emerald-600 hover:underline">Submit Results</a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Quick actions --}}
            @if(!$allSubmitted)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <a href="{{ route('scores.create') }}" class="flex items-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:border-green-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center text-2xl mr-4 group-hover:bg-green-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">Score Entry</h4>
                        <p class="text-sm text-gray-500">Enter CA and Examination marks for your course.</p>
                    </div>
                </a>

                @can('author_questions')
                <a href="{{ route('exams.my') }}" class="flex items-center p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:border-indigo-500 hover:shadow-md transition group">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-2xl mr-4 group-hover:bg-indigo-600 group-hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800">Set Exam Questions</h4>
                        <p class="text-sm text-gray-500">Author objective & theory questions for your courses.</p>
                    </div>
                </a>
                @endcan
            </div>
            @endif
            @endif {{-- end: not (allSubmitted && not HOD) --}}
        </div>
    </div>
</x-app-layout>
