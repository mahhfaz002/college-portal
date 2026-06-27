<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Results</h2>
            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border">
                {{ $student->registration_number ?? $student->admission_number ?? 'N/A' }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Semester/Session Filter --}}
            <form method="GET" action="{{ route('results.student.index') }}" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-wrap items-center gap-4">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Session</label>
                    <select name="session" onchange="this.form.submit()" class="mt-1 rounded-lg border-gray-300">
                        <option value="{{ $currentSession }}" {{ $selectedSession === $currentSession ? 'selected' : '' }}>{{ $currentSession }}</option>
                        @foreach($availableSessions->pluck('session')->unique() as $sess)
                            @if($sess !== $currentSession)
                                <option value="{{ $sess }}" {{ $selectedSession === $sess ? 'selected' : '' }}>{{ $sess }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Semester</label>
                    <select name="term" onchange="this.form.submit()" class="mt-1 rounded-lg border-gray-300">
                        <option value="First Semester" {{ $selectedTerm === 'First Semester' ? 'selected' : '' }}>First Semester</option>
                        <option value="Second Semester" {{ $selectedTerm === 'Second Semester' ? 'selected' : '' }}>Second Semester</option>
                    </select>
                </div>
            </form>

            {{-- Fee blocked --}}
            @if($feeBlocked)
                <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-black text-red-900">Pending Fees</h3>
                    <p class="text-sm text-red-700 mt-2">You have pending fees to clear before you can view your results. Please settle all outstanding payments first.</p>
                    <a href="{{ route('dashboard') }}#payments" class="mt-4 inline-block bg-red-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-red-700">View Pending Payments</a>
                </div>

            {{-- Results transmitted but not paid --}}
            @elseif($hasTransmittedResults && !$hasPaid)
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 text-center">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-black text-emerald-900">Your Results Are Ready!</h3>
                    <p class="text-sm text-emerald-700 mt-2">Pay the result viewing fee to access your {{ $selectedTerm }} {{ $selectedSession }} results.</p>

                    <div class="mt-4 bg-white rounded-lg border p-4 max-w-sm mx-auto text-left">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Result Viewing Fee</span>
                            <span class="font-bold">{{ money(\App\Http\Controllers\ResultViewingController::RESULT_VIEWING_FEE) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold border-t pt-2">
                            <span>Semester</span>
                            <span>{{ $selectedTerm }}, {{ $selectedSession }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('results.student.pay') }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="term" value="{{ $selectedTerm }}">
                        <input type="hidden" name="session" value="{{ $selectedSession }}">
                        <button type="submit" class="bg-emerald-600 text-white px-8 py-3 rounded-full font-black hover:bg-emerald-700 transition">
                            Pay to View Results
                        </button>
                    </form>

                    @if($pendingInvoice)
                        <p class="text-xs text-emerald-600 mt-2">
                            Or <a href="{{ route('payments.checkout', $pendingInvoice) }}" class="font-bold underline">resume your pending payment</a>
                        </p>
                    @endif
                </div>

            {{-- Results paid and accessible --}}
            @elseif($hasTransmittedResults && $hasPaid && $scores->count())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                        <h3 class="font-bold text-gray-700">{{ $selectedTerm }} — {{ $selectedSession }}</h3>
                        <a href="{{ route('reports.download', $student->id) }}" class="flex items-center text-xs text-blue-600 font-bold hover:text-blue-900">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Download Statement
                        </a>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-gray-100">
                            <tr class="text-xs uppercase text-gray-500">
                                <th class="px-6 py-3">Course</th>
                                <th class="px-6 py-3">Code</th>
                                <th class="px-6 py-3 text-center">Units</th>
                                <th class="px-6 py-3 text-center">CA</th>
                                <th class="px-6 py-3 text-center">Exam</th>
                                <th class="px-6 py-3 text-center">Total</th>
                                <th class="px-6 py-3 text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($scores as $score)
                            @php $total = $score->total ?? ($score->ca_score + $score->exam_score); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-bold text-gray-800">{{ $score->subject->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $score->subject->course_code ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-center">{{ $score->subject->course_unit ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-center text-gray-600">{{ $score->ca_score }}</td>
                                <td class="px-6 py-4 text-sm text-center text-gray-600">{{ $score->exam_score }}</td>
                                <td class="px-6 py-4 text-sm text-center font-black text-blue-600">{{ $total }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 text-xs font-black rounded-full
                                        {{ $score->grade == 'A' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $score->grade == 'B' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $score->grade == 'C' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                        {{ in_array($score->grade, ['D']) ? 'bg-orange-100 text-orange-700' : '' }}
                                        {{ in_array($score->grade, ['F','E']) ? 'bg-red-100 text-red-700' : '' }}">
                                        {{ $score->grade }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="px-6 py-3 font-bold text-gray-700">Total</td>
                                <td class="px-6 py-3 text-center font-bold">{{ $scores->sum(fn($s) => $s->subject->course_unit ?? 0) }}</td>
                                <td colspan="4"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            {{-- No results for this semester --}}
            @else
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-600">Results Not Ready</h3>
                    <p class="text-sm text-gray-500 mt-2">Results for {{ $selectedTerm }} {{ $selectedSession }} have not been transmitted yet. Check back later.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
