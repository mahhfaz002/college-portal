<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎓 Department Students — {{ $department->name ?? '' }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filter by course of study + level (department-scoped) --}}
            <form method="GET" class="bg-white rounded-2xl shadow-sm border p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study</label>
                    <select name="program_id" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm">
                        <option value="">All courses of study</option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}" {{ (string) request('program_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                    <select name="level" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm">
                        <option value="">All levels</option>
                        @foreach($levels as $lv)
                            <option value="{{ $lv }}" {{ (string) request('level') === (string) $lv ? 'selected' : '' }}>L{{ $lv }}</option>
                        @endforeach
                    </select>
                </div>
                @if(request('program_id') || request('level'))
                    <a href="{{ route('hod.students') }}" class="text-sm text-gray-500 font-semibold pb-2">Clear filters</a>
                @endif
            </form>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Students ({{ $students->total() }})</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2 text-left">Name</th><th class="px-4 py-2 text-left">Reg No</th><th class="px-4 py-2 text-left">Course of Study</th><th class="px-4 py-2 text-left">Level</th><th class="px-4 py-2 text-left">Registration</th><th class="px-4 py-2 text-right"></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($students as $s)
                            @php $b = match($s->registration_status){'registered'=>['Registered','bg-green-100 text-green-700'],'pending_hod'=>['Pending','bg-blue-100 text-blue-700'],'documents_rejected'=>['Returned','bg-red-100 text-red-700'],default=>[$s->registration_status ?: '—','bg-gray-100 text-gray-600']}; @endphp
                            <tr>
                                <td class="px-4 py-2 font-semibold text-gray-800">{{ $s->full_name }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $s->registration_number ?? $s->admission_number }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $s->program->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $s->level }}</td>
                                <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $b[1] }}">{{ $b[0] }}</span></td>
                                <td class="px-4 py-2 text-right"><a href="{{ route('hod.students.show', $s) }}" class="text-indigo-600 font-semibold hover:underline">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No students in your department yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $students->links() }}</div>
        </div>
    </div>
</x-app-layout>
