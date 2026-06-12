<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🖥️ MIS Console: <span class="text-purple-600">Management Information Systems</span>
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('structure.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition text-sm">
                    🏛️ Academic Structure
                </a>
                <a href="{{ route('support.index') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-purple-700 transition text-sm">
                    🛠️ Support Tickets ({{ $openTickets }})
                </a>
                <a href="{{ route('exams.index') }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-bold hover:bg-gray-900 transition text-sm">
                    📝 Exam Support
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total System Users</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $totalUsers }}</h3>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Sessions</p>
                    <h3 class="text-3xl font-black text-purple-600">{{ $activeSessions }}</h3>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Open Tickets</p>
                    <h3 class="text-3xl font-black text-red-600">{{ $openTickets }}</h3>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $releasedExams }} exam(s) live now</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Password Resets</h3>
                    @if(session('success'))<div class="mb-3 p-3 bg-green-100 border border-green-300 text-green-800 rounded text-xs">{{ session('success') }}</div>@endif
                    <div class="max-h-72 overflow-y-auto divide-y">
                        @foreach($allUsers as $u)
                        <div class="flex justify-between items-center py-2">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $u->name }}</p>
                                <p class="text-[11px] text-gray-400 uppercase">{{ str_replace('_',' ',$u->role) }}</p>
                            </div>
                            <form action="{{ route('support.reset-password', $u) }}" method="POST" onsubmit="return confirm('Reset password for {{ $u->name }}?')">
                                @csrf
                                <button class="text-xs bg-gray-700 text-white px-3 py-1.5 rounded font-bold hover:bg-gray-800">Reset</button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h3 class="font-bold text-gray-700">Recent System Activity</h3>
                    </div>
                    <div class="p-6 space-y-4 text-sm text-gray-600">
                        @forelse($latestSystemLogs as $log)
                        <div class="border-b pb-2 last:border-0">
                            <span class="font-bold text-purple-700">[{{ $log->created_at->format('H:i:s') }}]</span>
                            {{ $log->description }}
                        </div>
                        @empty
                        <p class="text-gray-400 italic">No activity logs recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
