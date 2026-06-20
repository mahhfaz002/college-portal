<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔁 Change of Course — Academic Review</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">Paid applications awaiting your recommendation</h3></div>
                @forelse($requests as $r)
                    <div class="p-6 border-b">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm">
                                <b>{{ optional($r->student)->full_name }}</b>
                                <span class="text-xs text-gray-400 font-mono">{{ optional($r->student)->registration_number ?: optional($r->student)->admission_number }}</span>
                            </div>
                            <div class="text-sm">{{ optional($r->currentProgram)->name ?? '—' }} <span class="text-gray-400">→</span> <b class="text-indigo-700">{{ optional($r->requestedProgram)->name }}</b></div>
                        </div>
                        <p class="text-sm text-gray-600 bg-gray-50 rounded-lg p-3">{{ $r->reason }}</p>
                        <form method="POST" action="{{ route('change-of-course.recommend', $r) }}" class="mt-3 flex flex-col sm:flex-row gap-2 sm:items-end">
                            @csrf
                            <input name="note" placeholder="Note (optional)" class="flex-1 border-gray-300 rounded-lg text-sm">
                            <button name="decision" value="recommend" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-700">Recommend</button>
                            <button name="decision" value="against" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-orange-700">Recommend Against</button>
                        </form>
                    </div>
                @empty
                    <p class="p-8 text-center text-gray-400 italic">No applications awaiting review.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
