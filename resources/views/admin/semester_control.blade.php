<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Semester Control</h2></x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            {{-- Current status --}}
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Current Semester</p>
                        <p class="text-lg font-black text-gray-800">{{ $currentTerm }} — {{ $currentSession }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-bold {{ $status === 'active' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ ucfirst($status) }}
                    </span>
                </div>
                @if($status === 'break' && $nextSemesterStart)
                    <div class="mt-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
                        <p class="text-sm text-amber-800">On break. Next semester starts <strong>{{ \Illuminate\Support\Carbon::parse($nextSemesterStart)->format('M d, Y') }}</strong>.</p>
                        @if($nextSessionStart)
                            <p class="text-sm text-amber-700">Next session begins <strong>{{ \Illuminate\Support\Carbon::parse($nextSessionStart)->format('M d, Y') }}</strong>.</p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- End the semester --}}
            @if($status === 'active')
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">End Semester &amp; Set Break</div>
                <form method="POST" action="{{ route('semester.end') }}" class="p-6 space-y-4" onsubmit="return confirm('End the current semester and start the break countdown?')">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Break Starts</label>
                            <input type="date" name="break_start" required class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Next Semester Starts</label>
                            <input type="date" name="next_semester_start" required class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Next Session Starts (if session end)</label>
                            <input type="date" name="next_session_start" class="w-full rounded-lg border-gray-300">
                            <p class="text-xs text-gray-500 mt-1">Only set this if the session is also ending.</p>
                        </div>
                    </div>
                    <button type="submit" class="bg-red-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-red-700">End Semester</button>
                </form>
            </div>
            @else
            {{-- Transition to new semester --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Begin New Semester</div>
                <form method="POST" action="{{ route('semester.transition') }}" class="p-6 space-y-4" onsubmit="return confirm('Transition to the new semester? This clears course allocations for fresh assignment.')">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">New Semester</label>
                            <select name="new_term" required class="w-full rounded-lg border-gray-300">
                                <option value="First Semester">First Semester</option>
                                <option value="Second Semester">Second Semester</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Session (e.g. 2026/2027)</label>
                            <input type="text" name="new_session" value="{{ $currentSession }}" required class="w-full rounded-lg border-gray-300">
                        </div>
                    </div>
                    <button type="submit" class="bg-emerald-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-emerald-700">Activate New Semester</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
