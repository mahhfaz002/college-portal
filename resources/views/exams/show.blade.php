<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $exam->title }}</h2>
            <a href="{{ route('exams.index') }}" class="text-sm text-gray-500 font-bold">← All Exams</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <!-- Status + lifecycle -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div class="text-sm space-y-1">
                        <p><span class="text-gray-400">Subject:</span> <strong>{{ $exam->subject->name ?? '—' }}</strong></p>
                        <p><span class="text-gray-400">Classes:</span> {{ implode(', ', $exam->class_arms) }}</p>
                        <p><span class="text-gray-400">Questions:</span> {{ $exam->questions->count() }} · <span class="text-gray-400">Total marks:</span> {{ $exam->totalMarks() }}</p>
                        <p><span class="text-gray-400">Status:</span> <span class="uppercase font-bold">{{ $exam->status }}</span></p>
                    </div>
                    @can('manage_exams')
                    <div class="flex flex-wrap gap-2">
                        @if($exam->status === 'draft')
                        <form action="{{ route('exams.release', $exam) }}" method="POST" class="flex items-end gap-2">
                            @csrf
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase">Access Password</label>
                                <input type="text" name="access_password" class="border-gray-300 rounded text-sm" placeholder="set password" required>
                            </div>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 text-sm">Release Exam</button>
                        </form>
                        @elseif($exam->status === 'released')
                        <span class="text-xs bg-blue-50 text-blue-700 px-3 py-2 rounded">🔑 Password: <strong>{{ $exam->access_password }}</strong></span>
                        <form action="{{ route('exams.close', $exam) }}" method="POST">@csrf
                            <button class="bg-yellow-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-yellow-700 text-sm">Close & Send for Grading</button>
                        </form>
                        @endif
                        <a href="{{ route('exams.compile', $exam) }}" class="bg-gray-700 text-white px-4 py-2 rounded-lg font-bold hover:bg-gray-800 text-sm">Compile Results</a>
                    </div>
                    @endcan
                </div>
            </div>

            <!-- Eligible -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-3 bg-green-50 border-b"><h3 class="font-bold text-green-800">✅ Eligible to Sit ({{ $eligible->count() }})</h3></div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="text-xs uppercase text-gray-500 border-b">
                            <th class="p-2">Student</th><th class="p-2">Class</th><th class="p-2">Attendance</th><th class="p-2">Fees</th><th class="p-2 text-right">Action</th>
                        </tr></thead>
                        <tbody>
                            @forelse($eligible as $r)
                            <tr class="border-b">
                                <td class="p-2 font-bold">{{ $r['student']->full_name }}</td>
                                <td class="p-2">{{ $r['student']->class_arm }}</td>
                                <td class="p-2">{{ $r['attendance_pct'] }}%</td>
                                <td class="p-2">{{ $r['fees_ok'] ? '✅ Cleared' : '⚠️ '.money($r['student']->fees_balance) }}</td>
                                <td class="p-2 text-right">
                                    @can('manage_exams')
                                    <form action="{{ route('exams.eligibility', [$exam, $r['student']]) }}" method="POST" class="inline">
                                        @csrf <input type="hidden" name="status" value="blocked">
                                        <button class="text-red-600 text-xs font-bold hover:underline">Block</button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="p-4 text-center text-gray-400 italic">None eligible.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ineligible -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-3 bg-red-50 border-b"><h3 class="font-bold text-red-800">⛔ Not Eligible ({{ $ineligible->count() }})</h3></div>
                <div class="p-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="text-xs uppercase text-gray-500 border-b">
                            <th class="p-2">Student</th><th class="p-2">Class</th><th class="p-2">Reason</th><th class="p-2 text-right">Action</th>
                        </tr></thead>
                        <tbody>
                            @forelse($ineligible as $r)
                            <tr class="border-b">
                                <td class="p-2 font-bold">{{ $r['student']->full_name }}</td>
                                <td class="p-2">{{ $r['student']->class_arm }}</td>
                                <td class="p-2 text-red-600 text-xs">{{ $r['overridden'] ? 'Blocked by officer' : implode('; ', $r['reasons']) }}</td>
                                <td class="p-2 text-right">
                                    @can('manage_exams')
                                    <form action="{{ route('exams.eligibility', [$exam, $r['student']]) }}" method="POST" class="inline">
                                        @csrf <input type="hidden" name="status" value="eligible">
                                        <input type="hidden" name="reason" value="Admitted by resolution">
                                        <button class="text-green-700 text-xs font-bold hover:underline">Admit (override)</button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="p-4 text-center text-gray-400 italic">Everyone in these classes is eligible.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
