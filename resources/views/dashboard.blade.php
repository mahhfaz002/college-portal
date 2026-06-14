<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('College Management Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-10 relative">
                <div class="max-w-2xl mx-auto">
                    <form action="{{ route('dashboard') }}" method="GET" class="relative">
                        <input type="text" name="search" value="{{ $search ?? '' }}"
                            placeholder="Find a student by name or admission number..."
                            class="w-full pl-14 pr-4 py-4 rounded-2xl border-none shadow-xl focus:ring-2 focus:ring-blue-500 transition-all text-lg">

                        <div class="absolute left-5 top-4.5 text-gray-400">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>

                        @if($search)
                            <a href="{{ route('dashboard') }}" class="absolute right-5 top-5 text-gray-400 hover:text-red-500 transition">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        @endif
                    </form>

                    @if(isset($searchResults) && count($searchResults) > 0)
                        <div class="absolute mt-2 w-full bg-white rounded-xl shadow-2xl z-50 border border-gray-100 overflow-hidden">
                            <div class="px-4 py-2 bg-blue-50 text-[10px] font-bold text-blue-600 uppercase tracking-widest">Matching Students</div>
                            @foreach($searchResults as $student)
                                <a href="{{ route('students.show', $student->id) }}" class="flex items-center justify-between p-4 hover:bg-gray-50 transition border-b border-gray-50 last:border-0">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold">
                                            {{ substr($student->full_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800">{{ $student->full_name }}</p>
                                            <p class="text-xs text-gray-500">{{ $student->admission_number }} • {{ $student->class_arm }}</p>
                                        </div>
                                    </div>
                                    <span class="text-blue-500 text-xs font-bold uppercase tracking-tighter">Profile →</span>
                                </a>
                            @endforeach
                        </div>
                    @elseif(isset($search) && $search != '')
                        <div class="absolute mt-2 w-full bg-white p-6 rounded-xl shadow-2xl z-50 text-center text-gray-500 border border-gray-100">
                            No student found matching "<span class="font-bold text-gray-800">{{ $search }}</span>"
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white p-4 rounded-lg shadow mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-gray-600 font-bold">Filter by Programme:</span>
                    <form action="{{ route('dashboard') }}" method="GET" class="flex gap-2">
                        <select name="class" class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Programmes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ $selectedClass == $class ? 'selected' : '' }}>
                                    {{ $class }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-gray-800 text-white px-4 py-1 rounded text-sm font-bold shadow-sm hover:bg-gray-700">
                            Filter
                        </button>
                        @if($selectedClass)
                            <a href="{{ route('dashboard') }}" class="text-red-500 text-xs flex items-center underline ml-2">Clear</a>
                        @endif
                    </form>
                </div>

                @if($selectedClass)
                    <div class="text-sm font-semibold text-blue-700 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                        Active Filter: {{ $selectedClass }}
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500 hover:scale-105 transition-transform">
                    <p class="text-sm text-gray-500 uppercase font-bold">Total Revenue</p>
                    <p class="text-3xl font-black text-gray-800">₦{{ number_format($totalRevenue, 2) }}</p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500 hover:scale-105 transition-transform">
                    <p class="text-sm text-gray-500 uppercase font-bold">Outstanding Debt</p>
                    <p class="text-3xl font-black text-gray-800">₦{{ number_format($totalDebt, 2) }}</p>
                </div>

                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500 hover:scale-105 transition-transform">
                    <p class="text-sm text-gray-500 uppercase font-bold">Students Count</p>
                    <p class="text-3xl font-black text-gray-800">{{ $studentCount }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="font-bold text-lg mb-4 text-blue-800 border-b pb-2">🌟 Top Students (Avg Score)</h3>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-400 text-sm uppercase">
                                <th class="pb-2">Student</th>
                                <th class="pb-2 text-right">Average</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topStudents as $index => $stat)
                                <tr class="border-b last:border-0 hover:bg-gray-50">
                                    <td class="py-3 font-semibold">
                                        <span class="text-gray-400 mr-2">#{{ $index + 1 }}</span>
                                        {{ $stat->student->full_name }}
                                    </td>
                                    <td class="py-3 text-right font-bold text-blue-600">
                                        {{ round($stat->average_score, 1) }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-4 text-center text-gray-400 italic">No academic data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="font-bold text-lg mb-4 text-green-800 border-b pb-2">💸 Recent Transactions</h3>
                    <ul class="divide-y divide-gray-100">
                        @forelse($recentPayments as $pay)
                            <li class="py-3 flex justify-between items-center hover:bg-gray-50 px-2 transition">
                                <div>
                                    <p class="font-bold text-sm text-gray-800">{{ $pay->student->full_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $pay->created_at->diffForHumans() }}</p>
                                </div>
                                <p class="font-bold text-green-600">+₦{{ number_format($pay->amount) }}</p>
                            </li>
                        @empty
                            <li class="py-4 text-center text-gray-400 italic">No recent payments found.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
