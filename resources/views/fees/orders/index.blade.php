<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">💳 Payment Orders</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            {{-- Create order (bursar only; oversight roles see this read-only) --}}
            @can('manage_fees')
            <div class="bg-white rounded-2xl shadow-sm border p-6"
                 x-data="feeScope(@js($sections), @js($programs))">
                <h3 class="font-bold text-gray-800 mb-4">Create a Payment Order</h3>
                <form method="POST" action="{{ route('fees.orders.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid md:grid-cols-3 gap-4">
                        <input name="title" required placeholder="Fee title * (e.g. 2026 Tuition)" class="border-gray-300 rounded-lg md:col-span-2" value="{{ old('title') }}">
                        <input name="amount" type="number" step="0.01" min="1" required placeholder="Amount (₦) *" class="border-gray-300 rounded-lg" value="{{ old('amount') }}">
                    </div>
                    <input name="description" placeholder="Description (shown on the receipt)" class="border-gray-300 rounded-lg w-full" value="{{ old('description') }}">

                    {{-- Mode toggle --}}
                    <input type="hidden" name="mode" :value="mode">
                    <div class="flex gap-2">
                        <button type="button" @click="mode='filter'" :class="mode==='filter' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-4 py-1.5 rounded-full text-sm font-bold">By filter</button>
                        <button type="button" @click="mode='students'" :class="mode==='students' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-4 py-1.5 rounded-full text-sm font-bold">Selected students</button>
                    </div>

                    {{-- Filter cascade — every level is optional; stop at any depth. --}}
                    <div x-show="mode==='filter'">
                        <p class="text-xs text-gray-500 mb-2">Pick as deep as you like and stop — e.g. choose only a department and <strong>every student across all its courses of study and levels</strong> is billed. Leave all blank to bill the whole college.</p>
                        <div class="grid md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Section</label>
                                <select name="section" x-model="section" @change="deptId='';programId='';level=''" class="w-full border-gray-300 rounded-lg text-sm">
                                    <option value="">All sections</option>
                                    @foreach($sections as $s)<option value="{{ $s }}">{{ \App\Support\Sections::label($s) }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                                <select name="department_id" x-model="deptId" @change="programId='';level=''" class="w-full border-gray-300 rounded-lg text-sm">
                                    <option value="">All departments</option>
                                    @foreach($departments as $d)<option value="{{ $d->id }}" x-show="!section || '{{ $d->section }}'===section">{{ $d->name }}</option>@endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study</label>
                                <select name="program_id" x-model="programId" @change="level=''" class="w-full border-gray-300 rounded-lg text-sm">
                                    <option value="">All courses of study</option>
                                    <template x-for="p in coursesOfStudy()" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level <span x-show="programId" class="text-red-500">*</span></label>
                                <select name="level" x-model="level" class="w-full border-gray-300 rounded-lg text-sm" :disabled="!programId" :required="!!programId">
                                    <option value="" x-text="programId ? '— Select level —' : 'All levels'"></option>
                                    <template x-for="l in levelOptions()" :key="l"><option :value="l" x-text="'L'+l"></option></template>
                                </select>
                                <p x-show="programId" class="text-[11px] text-gray-400 mt-1">Required — keeps this order to one level of the programme.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Hand-picked students --}}
                    <div x-show="mode==='students'" class="border rounded-lg p-3 max-h-56 overflow-y-auto">
                        <p class="text-xs text-gray-500 mb-2">Tick the students to bill (use the directory filter below to narrow the list).</p>
                        @forelse($students as $s)
                            <label class="flex items-center gap-2 py-1 text-sm">
                                <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" class="rounded">
                                {{ $s->full_name }} <span class="text-xs text-gray-400">{{ $s->registration_number ?? $s->admission_number }} · {{ $s->program->name ?? '' }} · L{{ $s->level }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-400">No students yet.</p>
                        @endforelse
                    </div>

                    <button class="bg-emerald-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-emerald-700">Create Payment Order</button>
                </form>
            </div>
            @endcan

            {{-- Student directory filter --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-800">Student Directory ({{ $students->count() }})</h3>
                    <form method="GET" class="flex flex-wrap gap-2 text-sm">
                        <select name="department_id" class="border-gray-300 rounded-lg text-sm py-1">
                            <option value="">All departments</option>
                            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id')==$d->id)>{{ $d->name }}</option>@endforeach
                        </select>
                        <select name="program_id" class="border-gray-300 rounded-lg text-sm py-1">
                            <option value="">All programs</option>
                            @foreach($programs as $p)<option value="{{ $p['id'] }}" @selected(request('program_id')==$p['id'])>{{ $p['name'] }}</option>@endforeach
                        </select>
                        <select name="level" class="border-gray-300 rounded-lg text-sm py-1">
                            <option value="">All levels</option>
                            @foreach($levels as $l)<option value="{{ $l }}" @selected(request('level')==$l)>{{ $l }}</option>@endforeach
                        </select>
                        <button class="bg-gray-800 text-white px-4 py-1 rounded-lg">Filter</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr><th class="px-3 py-2 text-left">Name</th><th class="px-3 py-2 text-left">Reg/Adm No</th><th class="px-3 py-2 text-left">Program</th><th class="px-3 py-2 text-left">Level</th></tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($students as $s)
                                <tr><td class="px-3 py-2 font-semibold">{{ $s->full_name }}</td><td class="px-3 py-2">{{ $s->registration_number ?? $s->admission_number }}</td><td class="px-3 py-2">{{ $s->program->name ?? '—' }}</td><td class="px-3 py-2">{{ $s->level }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">No students match.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Existing orders --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Payment Orders</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2 text-left">Title</th><th class="px-4 py-2 text-left">Target</th><th class="px-4 py-2 text-left">Amount</th><th class="px-4 py-2 text-left">Paid</th><th class="px-4 py-2"></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($orders as $o)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $o->title }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $o->scope_label }}</td>
                                <td class="px-4 py-3">{{ money($o->amount) }}</td>
                                <td class="px-4 py-3">{{ $o->paid_count }}/{{ $o->invoices_count }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('fees.orders.show', $o) }}" class="text-indigo-600 font-semibold hover:underline">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No payment orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function feeScope(sections, programs) {
            return {
                sections, programs,
                mode: 'filter', section: '', deptId: '', programId: '', level: '',
                coursesOfStudy() {
                    return this.programs.filter(p =>
                        (!this.section || p.section === this.section) &&
                        (!this.deptId || String(p.dept_id) === String(this.deptId)));
                },
                levelOptions() {
                    const p = this.programs.find(x => String(x.id) === String(this.programId));
                    const out = []; for (let i = 1; i <= (p ? p.levels : 0); i++) out.push(String(i * 100));
                    return out;
                },
            }
        }
    </script>
</x-app-layout>
