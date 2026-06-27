<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Affairs Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Open Cases</p>
                    <p class="text-2xl font-black">{{ $stats['open'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-amber-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Pending Review</p>
                    <p class="text-2xl font-black">{{ $stats['pending_registrar'] + $stats['pending_provost'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-green-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Resolved</p>
                    <p class="text-2xl font-black">{{ $stats['resolved'] }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-indigo-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Register</p>
                    <p class="text-2xl font-black">{{ $registerCount }}</p>
                </div>
            </div>

            <div class="flex gap-2 border-b">
                @foreach(['cases' => 'Cases', 'register' => 'Register', 'students' => 'Students'] as $key => $label)
                    <a href="?tab={{ $key }}" class="px-4 py-2 font-bold text-sm {{ $tab === $key ? 'border-b-2 border-indigo-600 text-indigo-700' : 'text-gray-500 hover:text-gray-700' }}">{{ $label }}</a>
                @endforeach
            </div>

            @if($tab === 'cases')
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Log a Case</div>
                <form method="POST" action="{{ route('affairs.cases.store') }}" class="p-6 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Student Name</label>
                            <input type="text" name="student_name" placeholder="Student name" class="mt-1 w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Category</label>
                            <select name="category" required class="mt-1 w-full rounded-lg border-gray-300">
                                <option value="disciplinary">Disciplinary</option>
                                <option value="welfare">Welfare</option>
                                <option value="complaint">Complaint</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Description</label>
                        <textarea name="description" required rows="3" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Recommendation</label>
                            <textarea name="recommendation" rows="2" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 uppercase">Penalty Type</label>
                            <input type="text" name="penalty_type" class="mt-1 w-full rounded-lg border-gray-300" placeholder="e.g. Warning, Suspension">
                        </div>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-700">Log Case</button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Cases ({{ $cases->count() }})</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="text-left px-4 py-3">Student</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Status</th><th class="text-right px-4 py-3">Action</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($cases as $case)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $case->student_name ?? '—' }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $case->category === 'disciplinary' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">{{ ucfirst($case->category) }}</span></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $case->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($case->status === 'open')
                                    <form method="POST" action="{{ route('affairs.cases.submit', $case) }}" class="inline">@csrf <button class="text-indigo-600 text-xs font-bold hover:underline">Forward to Registrar</button></form>
                                @endif
                                @if(in_array($case->status, ['resolved', 'forwarded_to_student']))
                                    <form method="POST" action="{{ route('affairs.cases.notify', $case) }}" class="inline ml-2">@csrf <button class="text-emerald-600 text-xs font-bold hover:underline">{{ $case->student_notified_at ? 'Notified' : 'Notify Student' }}</button></form>
                                @endif
                                <form method="POST" action="{{ route('affairs.cases.destroy', $case) }}" class="inline ml-2" onsubmit="return confirm('Delete?')">@csrf @method('DELETE') <button class="text-red-500 text-xs font-bold hover:underline">Delete</button></form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No cases.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            @if($tab === 'register')
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Register Student</div>
                <form method="POST" action="{{ route('affairs.register.store') }}" class="p-6 space-y-4">
                    @csrf
                    <input type="text" name="registration_number" required placeholder="Registration number" class="w-full max-w-md rounded-lg border-gray-300">
                    @php $items = ['dob_cert'=>'DOB Certificate','admission_letter'=>'Admission Letter','oath_form'=>'Signed Oath Form','olevel_certs'=>'O-Level Certificates','indigene_letter'=>'Indigene Letter']; @endphp
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($items as $k => $l)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="checklist[{{ $k }}]" value="1" class="rounded border-gray-300 text-indigo-600"> {{ $l }}</label>
                        @endforeach
                    </div>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300" placeholder="Notes..."></textarea>
                    <button type="submit" class="bg-emerald-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-emerald-700">Register</button>
                </form>
            </div>
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Register ({{ $registerEntries->count() }})</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="text-left px-4 py-3">Student</th><th class="px-4 py-3">Programme</th><th class="px-4 py-3">Level</th><th class="px-4 py-3">Date</th><th class="text-right px-4 py-3">Action</th></tr></thead>
                    <tbody class="divide-y">
                        @forelse($registerEntries as $e)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $e->student->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $e->student->program->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $e->student->level ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $e->registered_at?->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-right"><form method="POST" action="{{ route('affairs.register.destroy', $e) }}" class="inline" onsubmit="return confirm('Remove?')">@csrf @method('DELETE') <button class="text-red-500 text-xs font-bold hover:underline">Remove</button></form></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No entries.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            @if($tab === 'students')
            <form method="GET" class="bg-white p-4 rounded-xl shadow-sm border flex flex-wrap gap-3 items-end">
                <input type="hidden" name="tab" value="students">
                <div><label class="text-xs font-bold text-gray-500">Department</label><select name="department_id" onchange="this.form.submit()" class="mt-1 rounded-lg border-gray-300"><option value="">All</option>@foreach($departments as $d)<option value="{{ $d->id }}" {{ request('department_id')==$d->id?'selected':'' }}>{{ $d->name }}</option>@endforeach</select></div>
                <div><label class="text-xs font-bold text-gray-500">Programme</label><select name="program_id" onchange="this.form.submit()" class="mt-1 rounded-lg border-gray-300"><option value="">All</option>@foreach($programs as $p)<option value="{{ $p->id }}" {{ request('program_id')==$p->id?'selected':'' }}>{{ $p->name }}</option>@endforeach</select></div>
            </form>
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="text-left px-4 py-3">Name</th><th class="px-4 py-3">Reg. No.</th><th class="px-4 py-3">Programme</th><th class="text-center px-4 py-3">SA Register</th></tr></thead>
                    <tbody class="divide-y">
                        @forelse($students as $s)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $s->full_name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $s->registration_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $s->program->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $s->sa_registered ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $s->sa_registered ? 'Registered' : 'Pending' }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No students found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
