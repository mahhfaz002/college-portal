<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📝 My Exams</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            @forelse($exams as $exam)
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex justify-between items-center">
                <div>
                    <p class="font-bold text-gray-800">{{ $exam->title }}</p>
                    <p class="text-sm text-gray-500">{{ $exam->subject->name ?? '' }} · {{ $exam->duration_minutes }} mins · {{ $exam->questions()->count() }} questions</p>
                </div>
                @if($submittedIds->contains($exam->id))
                    <span class="text-xs font-bold text-green-700 bg-green-100 px-3 py-1.5 rounded">✓ Submitted</span>
                @else
                    <a href="{{ route('myexams.take', $exam) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Start →</a>
                @endif
            </div>
            @empty
            <div class="bg-white p-8 rounded-xl border text-center text-gray-400 italic">No exams available for you right now.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
