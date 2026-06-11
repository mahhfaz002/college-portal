<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📝 My Exam Courses</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Courses assigned this semester</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2 text-left">Course</th><th class="px-4 py-2 text-left">Dept · Level</th><th class="px-4 py-2 text-left">Questions</th><th class="px-4 py-2 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($exams as $e)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-800">
                                    {{ $e->subject->name ?? $e->title }}
                                    <span class="text-xs text-gray-400">{{ $e->subject->course_code ?? '' }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-500">
                                    {{ $e->subject->department->acronym ?? '—' }} · L{{ $e->subject->level ?? '—' }}
                                    <span class="text-xs text-gray-400">{{ $e->subject->program->name ?? '' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($e->questions_count > 0)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">Filled ({{ $e->questions_count }})</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Empty</span>
                                    @endif
                                    @if($e->isLocked())<span class="ml-1 text-xs text-gray-400">· submitted</span>@endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-3">
                                    @if($e->isLocked())
                                        <span class="text-xs text-gray-400">Locked</span>
                                    @else
                                        <a href="{{ route('exams.questions', $e) }}" class="text-indigo-600 font-semibold hover:underline">Set Questions</a>
                                        @if($e->questions_count > 0)
                                            <form action="{{ route('exams.submit', $e) }}" method="POST" class="inline" onsubmit="return confirm('Submit to the Exam Officer? You will not be able to edit afterwards.')">
                                                @csrf<button class="text-emerald-700 font-bold hover:underline">Submit</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No exams have been created for your courses yet. The Exam Officer sets up exams each semester.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
