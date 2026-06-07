<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Exam</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form action="{{ route('exams.store') }}" method="POST" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Subject</label>
                    <select name="subject_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        <option value="">Select subject…</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Exam Title</label>
                    <input type="text" name="title" placeholder="e.g. First Term Mathematics Exam" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" value="60" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Eligible Classes</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 max-h-40 overflow-y-auto border rounded-md p-2">
                        @forelse($classes as $class)
                            <label class="flex items-center gap-2 text-sm py-1">
                                <input type="checkbox" name="class_arms[]" value="{{ $class }}" class="rounded"> {{ $class }}
                            </label>
                        @empty
                            <p class="text-xs text-gray-400">No classes.</p>
                        @endforelse
                    </div>
                </div>
                <div class="flex justify-between items-center pt-3 border-t">
                    <a href="{{ route('exams.index') }}" class="text-gray-500 font-bold text-sm">← Cancel</a>
                    <button class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">Create Exam</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
