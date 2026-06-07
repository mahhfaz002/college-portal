<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📨 Result Queries</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            @forelse($queries as $q)
            <div class="bg-white p-5 rounded-xl shadow-sm border {{ $q->status==='open' ? 'border-yellow-300' : 'border-gray-200' }}">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-bold text-gray-800">{{ $q->student->full_name ?? '—' }}
                            <span class="text-xs text-gray-400">on {{ $q->score?->subject?->name ?? 'a result' }}</span>
                        </p>
                        <p class="text-sm text-gray-600 mt-1">“{{ $q->message }}”</p>
                    </div>
                    <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $q->status==='open' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700' }}">{{ $q->status }}</span>
                </div>

                @if($q->status === 'open')
                    @can('manage_exams')
                    <form action="{{ route('exams.queries.resolve', $q) }}" method="POST" class="mt-3 border-t pt-3 space-y-2">
                        @csrf
                        @if($q->score)
                        <div class="flex gap-2 items-end">
                            <div><label class="block text-[10px] font-bold text-gray-500 uppercase">CA</label><input type="number" name="ca_score" value="{{ $q->score->ca_score }}" class="w-20 border-gray-300 rounded text-sm"></div>
                            <div><label class="block text-[10px] font-bold text-gray-500 uppercase">Exam</label><input type="number" name="exam_score" value="{{ $q->score->exam_score }}" class="w-20 border-gray-300 rounded text-sm"></div>
                            <span class="text-xs text-gray-400">Leave unchanged if no amendment needed.</span>
                        </div>
                        @endif
                        <textarea name="resolution" rows="2" placeholder="Resolution note to record…" class="w-full border-gray-300 rounded-md text-sm" required></textarea>
                        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Resolve</button>
                    </form>
                    @endcan
                @else
                    <p class="text-sm text-green-700 mt-2 border-t pt-2"><strong>Resolution:</strong> {{ $q->resolution }}</p>
                @endif
            </div>
            @empty
            <div class="bg-white p-8 rounded-xl border text-center text-gray-400 italic">No queries.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
