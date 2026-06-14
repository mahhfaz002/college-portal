<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📄 Approved Question Papers</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-3 rounded-lg text-sm">
                HOD-approved question sets. <strong>Print</strong> the full paper (objectives + theory) or download a
                <strong>CSV of objectives only</strong> to upload into the offline exam portal.
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Approved papers</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Course</th>
                            <th class="px-6 py-3 text-left">Course of Study · Level</th>
                            <th class="px-6 py-3 text-left">Cycle</th>
                            <th class="px-6 py-3 text-left">Questions</th>
                            <th class="px-6 py-3 text-right">Export</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($exams as $ex)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ optional($ex->subject)->name }} <span class="text-xs text-gray-400">{{ optional($ex->subject)->course_code }}</span></td>
                                <td class="px-6 py-3 text-gray-500">{{ optional(optional($ex->subject)->program)->name ?? '—' }} · {{ optional($ex->subject)->level ? 'L'.$ex->subject->level : '—' }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ optional($ex->examCycle)->title ?? '—' }}</td>
                                <td class="px-6 py-3 text-gray-600 text-xs">{{ $ex->objective_count }} obj · {{ $ex->theory_count }} theory</td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('exams.papers.print', $ex) }}" target="_blank" class="bg-gray-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-800">Print</a>
                                    <a href="{{ route('exams.papers.csv', $ex) }}" class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-emerald-700">CSV (objectives)</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No approved papers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
