<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🎓 Principal's Office: <span class="text-indigo-600">Administrative Hub</span>
            </h2>
            <a href="{{ route('staff.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-sm">
                👥 Manage All Staff
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if($unassignedTeachers > 0)
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 flex justify-between items-center rounded-r-lg">
                <p class="font-bold">⚠️ There are {{ $unassignedTeachers }} teachers without an assigned class!</p>
                <a href="{{ route('staff.index') }}" class="underline font-black italic">Fix Now →</a>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Staff</p>
                    <h3 class="text-2xl font-black">{{ $staffCount }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Students</p>
                    <h3 class="text-2xl font-black">{{ $totalStudents }}</h3>
                </div>
                <a href="{{ route('staff.attendance') }}" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-indigo-300 transition">
                    <p class="text-xs font-bold text-gray-400 uppercase">Teachers Active Today</p>
                    <h3 class="text-2xl font-black text-green-600">{{ $activeTeachers }}<span class="text-gray-300 text-lg">/{{ $teacherTotal }}</span></h3>
                    <p class="text-[11px] text-indigo-600 font-bold mt-1">View classroom activity →</p>
                </a>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase">Current Term</p>
                    <h3 class="text-2xl font-black text-indigo-600">{{ $school['term'] ?: '1st Term' }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h3 class="font-bold text-gray-700 mb-4 border-b pb-2 text-lg">Administrative Quick Links</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <a href="{{ route('staff.index') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-indigo-50 border border-transparent hover:border-indigo-200 transition">
                                <span class="block font-bold text-indigo-700">Staff &amp; Assignments</span>
                                <span class="text-xs text-gray-500">Register staff, assign classes &amp; subjects</span>
                            </a>
                            <a href="{{ route('announcements.index') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-amber-50 border border-transparent hover:border-amber-200 transition">
                                <span class="block font-bold text-amber-700">📢 Post Announcement</span>
                                <span class="text-xs text-gray-500">To everyone, staff, students, or a class</span>
                            </a>
                            <a href="{{ route('classes.index') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-green-50 border border-transparent hover:border-green-200 transition">
                                <span class="block font-bold text-green-700">🏫 Manage Classes</span>
                                <span class="text-xs text-gray-500">Add classes &amp; view enrolment</span>
                            </a>
                            @if(\Illuminate\Support\Facades\Route::has('payroll.review'))
                            <a href="{{ route('payroll.review') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-purple-50 border border-transparent hover:border-purple-200 transition">
                                <span class="block font-bold text-purple-700">💰 Payroll Approvals @if($pendingPayroll)<span class="ml-1 text-[10px] text-white bg-red-500 px-2 py-0.5 rounded-full">{{ $pendingPayroll }}</span>@endif</span>
                                <span class="text-xs text-gray-500">Review &amp; approve bursar payslips</span>
                            </a>
                            @endif
                            @if(\Illuminate\Support\Facades\Route::has('timetable.index'))
                            <a href="{{ route('timetable.index') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-blue-50 border border-transparent hover:border-blue-200 transition">
                                <span class="block font-bold text-blue-700">🗓️ Timetable</span>
                                <span class="text-xs text-gray-500">Generate &amp; approve weekly timetable</span>
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Today's clock in/out -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                            <h3 class="font-bold text-gray-700">🕒 Today's Clock In / Out</h3>
                            <a href="{{ route('staff.attendance') }}" class="text-xs font-bold text-indigo-600">Full report →</a>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead><tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                                <th class="p-3">Teacher</th><th class="p-3">Clock In</th><th class="p-3">Clock Out</th><th class="p-3">Status</th>
                            </tr></thead>
                            <tbody>
                                @forelse($teacherClock as $row)
                                <tr class="border-b">
                                    <td class="p-3 font-bold">{{ $row['teacher']->name }}</td>
                                    <td class="p-3 text-green-700">{{ $row['clock_in'] ? \Illuminate\Support\Carbon::parse($row['clock_in'])->format('h:i A') : '—' }}</td>
                                    <td class="p-3 text-red-600">{{ $row['clock_out'] ? \Illuminate\Support\Carbon::parse($row['clock_out'])->format('h:i A') : '—' }}</td>
                                    <td class="p-3">
                                        @if($row['clock_in'] && !$row['clock_out'])<span class="text-[10px] font-bold text-green-700 bg-green-100 px-2 py-1 rounded-full">● ON SITE</span>
                                        @elseif($row['clock_out'])<span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded-full">left</span>
                                        @else<span class="text-[10px] font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded-full">not in</span>@endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="p-6 text-center text-gray-400 italic">No teachers yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Recent Staff Logins</h3>
                    <div class="space-y-4">
                        @foreach($staffList as $staff)
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center font-bold text-indigo-700">
                                {{ substr($staff->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $staff->name }}</p>
                                <p class="text-[10px] text-gray-400 uppercase">{{ $staff->role }} — {{ $staff->class_assigned ?? 'Admin' }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
