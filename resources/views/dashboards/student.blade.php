<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🎓 Student Portal: <span class="text-blue-600">{{ $student->full_name ?? 'Guest' }}</span>
            </h2>
            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border">
                ID: {{ $student->admission_number ?? 'N/A' }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))<div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            @if(isset($error))
                <div class="bg-red-100 border-l-4 border-red-500 p-4 text-red-700 mb-6 rounded-r-lg">
                    {{ $error }}
                </div>
            @else

            @if(($availableExams ?? 0) > 0)
            <div class="mb-6 p-4 bg-indigo-50 border border-indigo-200 rounded-lg flex justify-between items-center">
                <p class="font-bold text-indigo-800">📝 You have {{ $availableExams }} exam(s) available to take.</p>
                <a href="{{ route('myexams.available') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Go to My Exams →</a>
            </div>
            @endif

            @if(isset($todayLessons) && $todayLessons->count())
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-blue-200 overflow-hidden">
                <div class="px-6 py-3 bg-blue-50 border-b flex justify-between items-center">
                    <h3 class="font-bold text-blue-800">🗓️ Today's Timetable ({{ now()->format('l') }})</h3>
                    <a href="{{ route('timetable.index') }}" class="text-xs font-bold text-blue-700">Full week →</a>
                </div>
                <table class="w-full text-left text-sm">
                    <tbody>
                        @foreach($todayLessons as $lesson)
                        <tr class="border-b">
                            <td class="p-3 font-bold text-gray-500 w-24">{{ $lesson->start_time }}–{{ $lesson->end_time }}</td>
                            <td class="p-3 font-bold text-gray-800">{{ $lesson->subject->name ?? '' }}</td>
                            <td class="p-3 text-gray-400">{{ $lesson->teacher->name ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if(isset($announcements) && $announcements->count())
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-3 bg-amber-50 border-b flex justify-between items-center">
                    <h3 class="font-bold text-amber-800">📢 Announcements
                        <span class="ml-1 text-[10px] font-bold text-white bg-amber-500 px-2 py-0.5 rounded-full">{{ $announcements->count() }}</span>
                    </h3>
                    <a href="{{ route('announcements.index') }}" class="text-xs font-bold text-amber-700">View all →</a>
                </div>
                <div class="divide-y">
                    @foreach($announcements as $a)
                    <div class="px-6 py-3">
                        <p class="font-bold text-gray-800 text-sm">{{ $a->title }}
                            @if($a->audience === 'class')<span class="ml-1 text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">{{ $a->target_class }}</span>@endif
                        </p>
                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($a->body, 160) }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">{{ $a->author->name ?? 'School' }} · {{ $a->created_at->diffForHumans() }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border-b-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Class</p>
                    <h3 class="text-2xl font-black">{{ $student->class_arm }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-b-4 border-green-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Attendance Rate</p>
                    <h3 class="text-2xl font-black text-green-600">{{ $attendanceRate }}%</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-b-4 border-yellow-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Average Score</p>
                    <h3 class="text-2xl font-black text-yellow-600">
                        {{ $scores->avg(fn($s) => $s->ca_score + $s->exam_score) ? number_format($scores->avg(fn($s) => $s->ca_score + $s->exam_score), 1) : '0' }}%
                    </h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-b-4 border-red-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Fees Balance</p>
                    <h3 class="text-2xl font-black text-red-600">₦{{ number_format($student->fees_balance, 2) }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                        <h3 class="font-bold text-gray-700">Termly Results - 1st Term 2025/2026</h3>
                        <a href="{{ route('reports.download', $student->id) }}" class="flex items-center text-xs text-blue-600 font-bold hover:text-blue-900 transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Download Report Card
                        </a>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-gray-100">
                            <tr class="text-xs uppercase text-gray-500">
                                <th class="px-6 py-3">Subject</th>
                                <th class="px-6 py-3 text-center">CA (40)</th>
                                <th class="px-6 py-3 text-center">Exam (60)</th>
                                <th class="px-6 py-3 text-center">Total</th>
                                <th class="px-6 py-3 text-center">Grade</th>
                                <th class="px-6 py-3 text-center">Query</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($scores as $score)
                            @php
                                $total = $score->total ?? ($score->ca_score + $score->exam_score);
                                $grade = $score->grade ?? grade_for($total)['grade'];
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-bold text-gray-800">{{ $score->subject->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-center text-gray-600">{{ $score->ca_score }}</td>
                                <td class="px-6 py-4 text-sm text-center text-gray-600">{{ $score->exam_score }}</td>
                                <td class="px-6 py-4 text-sm text-center font-black text-blue-600">{{ $total }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 text-xs font-black rounded-full
                                        {{ $grade == 'A' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $grade == 'B' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $grade == 'C' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                        {{ in_array($grade,['F','E']) ? 'bg-red-100 text-red-700' : '' }}">
                                        {{ $grade }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <details class="inline-block text-left">
                                        <summary class="cursor-pointer text-xs text-indigo-600 font-bold">Raise</summary>
                                        <form action="{{ route('results.query', $score) }}" method="POST" class="mt-2 w-48">
                                            @csrf
                                            <textarea name="message" rows="2" placeholder="Describe the issue…" class="w-full border-gray-300 rounded text-xs" required></textarea>
                                            <button class="mt-1 bg-indigo-600 text-white text-xs px-3 py-1 rounded font-bold">Send</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-400 italic text-sm">No results published for this term yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h3 class="font-bold text-gray-700">Recent Payment Log</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @forelse($payments as $payment)
                        <div class="flex justify-between items-center pb-3 border-b last:border-0">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Term Fees Payment</p>
                                <p class="text-[10px] text-gray-400">{{ $payment->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-green-600">+ ₦{{ number_format($payment->amount, 2) }}</p>
                                <span class="text-[10px] bg-gray-100 px-2 py-0.5 rounded uppercase font-bold">{{ $payment->payment_method }}</span>
                            </div>
                        </div>
                        @empty
                        <p class="text-center text-gray-400 italic text-sm py-4">No payment records found.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
