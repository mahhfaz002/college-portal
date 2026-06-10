<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎓 Programs</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            @if($departments->isEmpty())
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">
                    Create a department first, then add programs under it.
                </div>
            @else
            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-gray-700 mb-4">Create Program</h3>
                <form method="POST" action="{{ route('programs.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <select name="department_id" required class="border-gray-300 rounded-lg">
                        <option value="">— Department —</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                    <input name="name" required placeholder="Program name (e.g. ND Science Lab Tech)" class="border-gray-300 rounded-lg md:col-span-2">
                    <input name="acronym" placeholder="Acronym (e.g. ND-SLT)" class="border-gray-300 rounded-lg">
                    <input name="level_system" placeholder="Level system (e.g. ND / HND)" class="border-gray-300 rounded-lg">
                    <input name="duration_years" type="number" min="1" max="7" value="2" placeholder="Duration (yrs)" class="border-gray-300 rounded-lg">
                    <input name="application_fee" type="number" step="0.01" placeholder="Application fee ₦" class="border-gray-300 rounded-lg">
                    <input name="acceptance_fee" type="number" step="0.01" placeholder="Acceptance fee ₦" class="border-gray-300 rounded-lg">
                    <input name="registration_fee" type="number" step="0.01" placeholder="Registration fee ₦" class="border-gray-300 rounded-lg">
                    <button class="bg-indigo-600 text-white rounded-lg font-bold px-4 py-2 hover:bg-indigo-700 md:col-span-3">+ Add Program</button>
                </form>
            </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b flex items-center gap-3">
                    <form method="GET" class="flex items-center gap-2">
                        <label class="text-sm text-gray-500">Filter by department:</label>
                        <select name="department_id" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm">
                            <option value="">All</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" @selected($selectedDept == $d->id)>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="text-left px-6 py-3">Program</th>
                            <th class="text-left px-6 py-3">Department</th>
                            <th class="text-left px-6 py-3">Application</th>
                            <th class="text-left px-6 py-3">Acceptance</th>
                            <th class="text-left px-6 py-3">Registration</th>
                            <th class="text-right px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($programs as $p)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $p->name }} <span class="text-xs text-gray-400">{{ $p->acronym }}</span></td>
                                <td class="px-6 py-3">{{ $p->department->name ?? '—' }}</td>
                                <td class="px-6 py-3">{{ money($p->application_fee) }}</td>
                                <td class="px-6 py-3">{{ money($p->acceptance_fee) }}</td>
                                <td class="px-6 py-3">{{ money($p->registration_fee) }}</td>
                                <td class="px-6 py-3 text-right">
                                    <form method="POST" action="{{ route('programs.destroy', $p) }}" onsubmit="return confirm('Delete this program?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-6 text-center text-gray-400">No programs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
