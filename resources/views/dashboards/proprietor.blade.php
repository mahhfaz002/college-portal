<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🏛️ Proprietor Dashboard (Global Overview)
            </h2>
            @if(in_array(auth()->user()->role, ['proprietor', 'ict']))
            <a href="{{ route('superadmin.switchboard') }}" class="text-xs font-bold bg-indigo-600 text-white px-4 py-2 rounded-lg uppercase hover:bg-indigo-700 transition shadow-sm">
                ⚙️ Master Switchboard
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white p-6 rounded-xl shadow-sm mb-8 border border-gray-100">
                <form action="{{ route('dashboard') }}" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Search Students</label>
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Search by name or admission number..."
                               class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Filter by Class</label>
                        <select name="class" onchange="this.form.submit()" class="w-full border-gray-300 rounded-lg shadow-sm">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ $selectedClass == $class ? 'selected' : '' }}>{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                @if($search)
                    <div class="mt-4 p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                        <h4 class="text-sm font-bold text-indigo-800 mb-2">Search Results for "{{ $search }}":</h4>
                        @forelse($searchResults as $result)
                            <a href="{{ route('students.show', $result) }}" class="flex justify-between items-center p-2 hover:bg-white rounded transition">
                                <span class="font-medium text-gray-700">{{ $result->full_name }} ({{ $result->admission_number }})</span>
                                <span class="text-xs bg-indigo-200 px-2 py-1 rounded text-indigo-700 font-bold">{{ $result->class_arm }}</span>
                            </a>
                        @empty
                            <p class="text-sm text-gray-500 italic">No students found matching that criteria.</p>
                        @endforelse
                    </div>
                @endif
            </div>

            @if(auth()->user()->role === 'proprietor')
            <div class="mb-6 flex items-center gap-2 text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                <span>👁️</span> View-only oversight — you can review every record but changes are made by the relevant staff.
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-8 border-green-500">
                    <p class="text-sm text-gray-500 font-bold uppercase">Total Revenue (Paid)</p>
                    <h3 class="text-3xl font-black text-gray-800">₦{{ number_format($totalRevenue, 2) }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-8 border-red-500">
                    <p class="text-sm text-gray-500 font-bold uppercase">Outstanding Debt</p>
                    <h3 class="text-3xl font-black text-gray-800">₦{{ number_format($totalDebt, 2) }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-8 border-blue-500">
                    <p class="text-sm text-gray-500 font-bold uppercase">Student Population</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $studentCount }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-8 border-purple-500">
                    <p class="text-sm text-gray-500 font-bold uppercase">Total Staff</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $staffCount }}</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
                <h3 class="font-bold text-gray-700 mb-4 flex items-center"><span class="mr-2">🏫</span> Students by Class</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                    @forelse($classBreakdown as $row)
                        <a href="{{ route('students.index', ['search' => $row->class_arm]) }}"
                           class="block text-center p-4 bg-gray-50 hover:bg-indigo-50 rounded-lg border border-gray-100 transition">
                            <div class="text-2xl font-black text-indigo-600">{{ $row->total }}</div>
                            <div class="text-xs font-bold text-gray-500 uppercase mt-1">{{ $row->class_arm }}</div>
                        </a>
                    @empty
                        <p class="text-sm text-gray-500 italic col-span-full">No classes with students yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center">
                        <span class="mr-2">🏆</span> Top 5 Performers {{ $selectedClass ? "in $selectedClass" : "(Global)" }}
                    </h3>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-xs font-bold text-gray-400 uppercase border-b">
                                <th class="pb-2">Student</th>
                                <th class="pb-2">Class</th>
                                <th class="pb-2 text-right">Avg Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topStudents as $top)
                            <tr class="border-b last:border-0 hover:bg-gray-50">
                                <td class="py-3 font-bold text-sm">{{ $top->student->full_name }}</td>
                                <td class="py-3 text-xs text-gray-500">{{ $top->student->class_arm }}</td>
                                <td class="py-3 text-right font-black text-indigo-600">{{ number_format($top->average_score, 1) }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center">
                        <span class="mr-2">💳</span> Recent Fee Payments
                    </h3>
                    <div class="space-y-4">
                        @foreach($recentPayments as $payment)
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $payment->student->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-green-600">₦{{ number_format($payment->amount, 2) }}</p>
                                <p class="text-xs italic text-gray-400">{{ $payment->payment_method }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <a href="{{ route('students.index') }}" class="block text-center mt-6 text-xs font-bold text-indigo-600 hover:underline uppercase">View All Accounts →</a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
