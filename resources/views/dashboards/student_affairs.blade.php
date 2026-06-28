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
                <form method="POST" action="{{ route('affairs.cases.store') }}" class="p-6 space-y-4" x-data="studentPicker({ multi: true })">
                    @csrf
                    {{-- Student search + multi-select --}}
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Student(s)</label>
                        <div class="relative mt-1">
                            <input type="text" x-model="q" @input.debounce.300ms="search()" placeholder="Type a student's name or reg number…"
                                   class="w-full rounded-lg border-gray-300" autocomplete="off">
                            <div x-show="results.length" x-cloak class="absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-64 overflow-y-auto">
                                <template x-for="r in results" :key="r.id">
                                    <button type="button" @click="add(r)" class="w-full text-left px-3 py-2 hover:bg-indigo-50 text-sm border-b last:border-0">
                                        <span class="font-semibold" x-text="r.name"></span>
                                        <span class="text-gray-400 text-xs" x-text="' · ' + r.reg + (r.department ? ' · ' + r.department : '')"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Search and click to add. You can add more than one student to the same case.</p>

                        {{-- Selected students as chips (with hidden inputs) --}}
                        <div class="flex flex-wrap gap-2 mt-2">
                            <template x-for="s in selected" :key="s.id">
                                <span class="inline-flex items-center gap-2 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-full pl-3 pr-1 py-1 text-xs">
                                    <span><span class="font-bold" x-text="s.name"></span> · <span x-text="s.reg"></span><span x-show="s.department" x-text="' · ' + s.department"></span></span>
                                    <input type="hidden" name="student_ids[]" :value="s.id">
                                    <button type="button" @click="remove(s.id)" class="w-5 h-5 rounded-full bg-indigo-200 text-indigo-800 hover:bg-indigo-300">&times;</button>
                                </span>
                            </template>
                            <span x-show="!selected.length" class="text-xs text-gray-400 italic">No student selected yet.</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                        <label class="text-xs font-bold text-gray-500 uppercase">Description of the case</label>
                        <textarea name="description" required rows="3" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Recommendation</label>
                        <textarea name="recommendation" rows="2" class="mt-1 w-full rounded-lg border-gray-300"></textarea>
                    </div>
                    <button type="submit" :disabled="!selected.length" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-700 disabled:opacity-40">Log Case</button>
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
                <form method="POST" action="{{ route('affairs.register.store') }}" class="p-6 space-y-4" x-data="studentPicker({ multi: false })">
                    @csrf
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Search student</label>
                        <div class="relative mt-1">
                            <input type="text" x-model="q" @input.debounce.300ms="search()" placeholder="Type a student's name or reg number…"
                                   class="w-full max-w-md rounded-lg border-gray-300" autocomplete="off">
                            <div x-show="results.length" x-cloak class="absolute z-20 mt-1 w-full max-w-md bg-white border rounded-lg shadow-lg max-h-64 overflow-y-auto">
                                <template x-for="r in results" :key="r.id">
                                    <button type="button" @click="add(r)" class="w-full text-left px-3 py-2 hover:bg-emerald-50 text-sm border-b last:border-0">
                                        <span class="font-semibold" x-text="r.name"></span>
                                        <span class="text-gray-400 text-xs" x-text="' · ' + r.reg + (r.department ? ' · ' + r.department : '')"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Auto-filled details of the selected student --}}
                    <template x-if="selected.length">
                        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-sm grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <input type="hidden" name="student_id" :value="selected[0].id">
                            <div><span class="text-gray-400 text-xs uppercase font-bold block">Full Name</span><span class="font-semibold" x-text="selected[0].name"></span></div>
                            <div><span class="text-gray-400 text-xs uppercase font-bold block">Department</span><span x-text="selected[0].department || '—'"></span></div>
                            <div><span class="text-gray-400 text-xs uppercase font-bold block">Course of Study</span><span x-text="selected[0].program || '—'"></span></div>
                            <div><span class="text-gray-400 text-xs uppercase font-bold block">Level</span><span x-text="selected[0].level ? ('L' + selected[0].level) : '—'"></span></div>
                        </div>
                    </template>

                    @php $items = ['dob_cert'=>'DOB Certificate','admission_letter'=>'Admission Letter','oath_form'=>'Signed Oath Form','olevel_certs'=>'O-Level Certificates','indigene_letter'=>'Indigene Letter']; @endphp
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($items as $k => $l)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="checklist[{{ $k }}]" value="1" class="rounded border-gray-300 text-indigo-600"> {{ $l }}</label>
                        @endforeach
                    </div>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border-gray-300" placeholder="Notes..."></textarea>
                    <button type="submit" :disabled="!selected.length" class="bg-emerald-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-emerald-700 disabled:opacity-40">Register</button>
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

    <script>
        function studentPicker(opts = {}) {
            return {
                q: '', results: [], selected: [], multi: opts.multi || false, loading: false,
                async search() {
                    if (this.q.trim().length < 2) { this.results = []; return; }
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('affairs.students.search') }}?q=' + encodeURIComponent(this.q),
                            { headers: { 'Accept': 'application/json' } });
                        this.results = res.ok ? await res.json() : [];
                    } catch (e) { this.results = []; }
                    this.loading = false;
                },
                add(r) {
                    if (!this.multi) this.selected = [];
                    if (!this.selected.find(s => s.id === r.id)) this.selected.push(r);
                    this.q = ''; this.results = [];
                },
                remove(id) { this.selected = this.selected.filter(s => s.id !== id); },
            };
        }
    </script>
</x-app-layout>
