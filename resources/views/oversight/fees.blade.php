<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">💳 Fee Breakdown</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center gap-2 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                <span>👁️</span> View-only. Fees collected from students, sliced by department, course of study and level.
            </div>

            {{-- ===== Filters ===== --}}
            <form method="GET" action="{{ route('oversight.fees') }}"
                  class="bg-white rounded-2xl shadow-sm border p-6"
                  x-data="{ dept: '{{ $filters['department_id'] }}' }">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                        <select name="department_id" x-model="dept" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All departments</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" @selected($filters['department_id'] == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study</label>
                        <select name="program_id" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All courses of study</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}"
                                        x-show="!dept || dept === '{{ $p->department_id }}'"
                                        @selected($filters['program_id'] == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                        <select name="level" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All levels</option>
                            @foreach($levels as $l)
                                <option value="{{ $l }}" @selected($filters['level'] == $l)>Level {{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button class="btn-brand px-5 py-2 rounded-lg font-semibold text-sm">Apply</button>
                        <a href="{{ route('oversight.fees') }}" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-600 font-semibold text-sm hover:bg-gray-50">Reset</a>
                    </div>
                </div>
            </form>

            {{-- ===== Status bar (paid vs pending) + total ===== --}}
            @php $cohort = $paidCount + $pendingCount; $paidPct = $cohort ? round($paidCount / $cohort * 100) : 0; @endphp
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div class="bg-white p-6 rounded-2xl shadow-sm border-t-4 border-emerald-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Fees Collected (filtered)</p>
                    <h3 class="text-3xl font-black text-emerald-600">{{ money($totalCollected) }}</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-t-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Students (filtered)</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ number_format($cohort) }}</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-t-4 border-amber-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Paid / Pending</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ $paidCount }} <span class="text-gray-300">/</span> <span class="text-amber-600">{{ $pendingCount }}</span></h3>
                </div>
            </div>

            @if($cohort)
            <div class="bg-white rounded-2xl shadow-sm border p-5">
                <div class="flex justify-between text-xs font-bold uppercase text-gray-500 mb-2">
                    <span class="text-emerald-600">Paid ({{ $paidCount }})</span>
                    <span class="text-amber-600">Pending — reg. fee ({{ $pendingCount }})</span>
                </div>
                <div class="w-full h-4 rounded-full bg-amber-200 overflow-hidden">
                    <div class="h-full bg-emerald-500" style="width: {{ $paidPct }}%"></div>
                </div>
            </div>
            @endif

            {{-- ===== Department summary (only when not drilled into one dept) ===== --}}
            @if($deptSummary->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700 flex items-center"><span class="mr-2">🏛️</span> Fees by Department</div>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-6 py-3">Department</th><th class="px-6 py-3">Students</th><th class="px-6 py-3 text-right">Collected</th><th class="px-6 py-3 text-right"></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($deptSummary as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $row['department']->name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ number_format($row['students']) }}</td>
                                <td class="px-6 py-3 text-right font-black text-emerald-600">{{ money($row['collected']) }}</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('oversight.fees', ['department_id' => $row['department']->id]) }}" class="text-xs font-bold text-brand hover:underline uppercase">View →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- ===== Student roll with paid / pending status ===== --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700 flex items-center"><span class="mr-2">🎓</span> Students {{ $filters['level'] ? '· Level '.$filters['level'] : '' }}</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Student</th>
                                <th class="px-6 py-3">Reg. Number</th>
                                <th class="px-6 py-3">Course of Study</th>
                                <th class="px-6 py-3">Level</th>
                                <th class="px-6 py-3 text-right">Collected</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($rows as $row)
                                @php $s = $row['student']; @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 font-semibold text-gray-800">{{ $s->full_name }}</td>
                                    <td class="px-6 py-3 text-gray-500">{{ $s->registration_number ?? $s->admission_number ?? '—' }}</td>
                                    <td class="px-6 py-3 text-gray-500">{{ optional($s->program)->name ?? '—' }}</td>
                                    <td class="px-6 py-3 text-gray-500">{{ $s->level ? 'L'.$s->level : '—' }}</td>
                                    <td class="px-6 py-3 text-right text-gray-700">{{ money($row['collected']) }}</td>
                                    <td class="px-6 py-3 text-center">
                                        @if($row['paid'])
                                            <span class="inline-block text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full">Paid</span>
                                        @else
                                            <span class="inline-block text-[10px] font-bold uppercase bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No students match these filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
