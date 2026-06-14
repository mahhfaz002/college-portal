<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📝 Exams</h2>
            @can('manage_exams')
            <a href="{{ route('exams.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">+ Create Exam</a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))<div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                            <th class="p-3 font-bold">Exam</th>
                            <th class="p-3 font-bold">Course</th>
                            <th class="p-3 font-bold">Programmes</th>
                            <th class="p-3 font-bold">Questions</th>
                            <th class="p-3 font-bold">Submissions</th>
                            <th class="p-3 font-bold">Status</th>
                            <th class="p-3 font-bold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exams as $exam)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-bold">{{ $exam->title }}</td>
                            <td class="p-3">{{ $exam->subject->name ?? '—' }}</td>
                            <td class="p-3 text-xs">{{ implode(', ', $exam->class_arms) }}</td>
                            <td class="p-3">{{ $exam->questions_count }}</td>
                            <td class="p-3">{{ $exam->submissions_count }}</td>
                            <td class="p-3">
                                @php $b = ['draft'=>'bg-gray-100 text-gray-600','released'=>'bg-blue-100 text-blue-700','grading'=>'bg-yellow-100 text-yellow-700','published'=>'bg-green-100 text-green-700'][$exam->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                                <span class="text-[10px] font-bold uppercase px-2 py-1 rounded {{ $b }}">{{ $exam->status }}</span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('exams.show', $exam) }}" class="bg-gray-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-gray-700">Manage</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-400 italic">No exams created yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
