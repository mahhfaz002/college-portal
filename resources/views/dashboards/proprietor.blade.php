<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Proprietor Dashboard <span class="text-brand">· Global Overview</span>
            </h2>
            @if(in_array(auth()->user()->role, ['proprietor', 'mis']))
            <a href="{{ route('superadmin.switchboard') }}" class="text-xs font-bold bg-gray-800 text-white px-4 py-2 rounded-lg uppercase hover:bg-gray-900 transition shadow-sm">
                ⚙️ Master Switchboard
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if(auth()->user()->role === 'proprietor')
            <div class="flex items-center gap-2 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                <span>👁️</span> View-only oversight — you can review every record; changes are made by the relevant staff.
            </div>
            @endif

            {{-- ===== KEY STATS (up top, stylish) ===== --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="bg-white p-6 rounded-2xl shadow-sm border-t-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Students</p>
                    <h3 class="text-4xl font-black text-gray-900">{{ number_format($studentCount) }}</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-t-4 border-purple-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Staff</p>
                    <h3 class="text-4xl font-black text-gray-900">{{ number_format($staffCount) }}</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-t-4 border-emerald-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Fees Collected</p>
                    <h3 class="text-3xl font-black text-emerald-600">{{ money($totalCollected) }}</h3>
                </div>
            </div>

            {{-- ===== PAYROLL FINAL APPROVAL ===== --}}
            <a href="{{ route('payroll.approval') }}" class="block bg-white p-6 rounded-2xl shadow-sm border hover:shadow-md transition">
                <h3 class="font-bold text-gray-800">Payroll Final Approval</h3>
                <p class="text-sm text-gray-500 mt-1">Give final approval on payslips forwarded by the Provost, or query them back.</p>
            </a>

            {{-- ===== FINANCE BANNER ===== --}}
            <div class="rounded-2xl bg-gradient-to-r from-gray-900 to-indigo-900 p-6 shadow-lg">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-2xl">💰</span>
                    <h3 class="text-lg font-black uppercase tracking-wide text-white">Finance Overview</h3>
                </div>
                <div class="grid grid-cols-1 gap-6">
                    <div class="bg-white/10 backdrop-blur p-6 rounded-xl border-l-8 border-emerald-400">
                        <p class="text-sm font-black uppercase text-emerald-200">Total Collected</p>
                        <h3 class="text-3xl font-black text-white">{{ money($totalCollected) }}</h3>
                    </div>
                </div>
            </div>

            {{-- ===== STUDENTS BY DEPARTMENT ===== --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-700 mb-4 flex items-center"><span class="mr-2">🏛️</span> Students by Department</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @forelse($deptBreakdown as $row)
                        <div class="text-center p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div class="text-2xl font-black text-brand">{{ $row->total }}</div>
                            <div class="text-xs font-bold text-gray-500 uppercase mt-1">{{ optional($row->department)->name ?? 'Unassigned' }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 italic col-span-full">No students enrolled yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Top performers --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center"><span class="mr-2">🏆</span> Top 5 Performers</h3>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-xs font-bold text-gray-400 uppercase border-b">
                                <th class="pb-2">Student</th><th class="pb-2">Programme</th><th class="pb-2 text-right">Avg</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topStudents as $top)
                            <tr class="border-b last:border-0 hover:bg-gray-50">
                                <td class="py-3 font-bold text-sm">{{ optional($top->student)->full_name ?? '—' }}</td>
                                <td class="py-3 text-xs text-gray-500">{{ optional(optional($top->student)->program)->name ?? '—' }}{{ optional($top->student)->level ? ' · L'.$top->student->level : '' }}</td>
                                <td class="py-3 text-right font-black text-brand">{{ number_format($top->average_score, 1) }}%</td>
                            </tr>
                            @empty
                                <tr><td colspan="3" class="py-6 text-center text-gray-400 text-sm">No results published yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Recent payments (Invoice engine) --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center"><span class="mr-2">💳</span> Recent Fee Payments</h3>
                    <div class="space-y-3">
                        @forelse($recentPayments as $payment)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ optional($payment->student)->full_name ?? $payment->payer_email ?? 'Payment' }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->description }} · {{ optional($payment->paid_at)->format('M d, Y') }}</p>
                            </div>
                            <p class="text-sm font-black text-emerald-600">{{ money($payment->amount) }}</p>
                        </div>
                        @empty
                            <p class="text-sm text-gray-400 italic text-center py-6">No payments recorded yet.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('students.index') }}" class="block text-center mt-6 text-xs font-bold text-brand hover:underline uppercase">View All Students →</a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
