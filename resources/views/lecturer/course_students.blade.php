<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🎓 {{ $subject->name }}
                <span class="text-sm font-normal text-gray-500">
                    {{ $subject->course_code }} · {{ optional($subject->program)->name }}{{ $subject->level ? ' · L'.$subject->level : '' }}
                </span>
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm font-bold text-indigo-600 hover:underline">← My Courses</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">
                    Registered Students ({{ $students->count() }})
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-2 text-left">#</th>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Reg No</th>
                            <th class="px-4 py-2 text-left">Level</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($students as $i => $s)
                            <tr>
                                <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-2 font-semibold text-gray-800">{{ $s->full_name }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $s->registration_number ?? $s->admission_number }}</td>
                                <td class="px-4 py-2">{{ $s->level }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No students are registered for this course yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
