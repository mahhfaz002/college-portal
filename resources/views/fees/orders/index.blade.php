<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">💳 Payment Orders</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            {{-- Create order --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6" x-data="{ scope: 'all' }">
                <h3 class="font-bold text-gray-800 mb-4">Create a Payment Order</h3>
                <form method="POST" action="{{ route('fees.orders.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid md:grid-cols-3 gap-4">
                        <input name="title" required placeholder="Fee title * (e.g. 2026 Tuition)" class="border-gray-300 rounded-lg md:col-span-2" value="{{ old('title') }}">
                        <input name="amount" type="number" step="0.01" min="1" required placeholder="Amount (₦) *" class="border-gray-300 rounded-lg" value="{{ old('amount') }}">
                    </div>
                    <input name="description" placeholder="Description (shown on the receipt)" class="border-gray-300 rounded-lg w-full" value="{{ old('description') }}">

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Assign to</label>
                            <select name="scope_type" x-model="scope" class="w-full border-gray-300 rounded-lg">
                                <option value="all">All students</option>
                                <option value="department">A department</option>
                                <option value="program">A program</option>
                                <option value="level">A level</option>
                                <option value="students">Selected students</option>
                            </select>
                        </div>

                        <div x-show="scope==='department'">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                            <select name="department_id" class="w-full border-gray-300 rounded-lg">
                                @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                            </select>
                        </div>
                        <div x-show="scope==='program'">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Program</label>
                            <select name="program_id" class="w-full border-gray-300 rounded-lg">
                                @foreach($programs as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->department->acronym ?? '' }})</option>@endforeach
                            </select>
                        </div>
                        <div x-show="scope==='level'">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                            <select name="level" class="w-full border-gray-300 rounded-lg">
                                @forelse($levels as $l)<option value="{{ $l }}">{{ $l }}</option>@empty<option value="100">100</option>@endforelse
                            </select>
                        </div>
                    </div>

                    <div x-show="scope==='students'" class="border rounded-lg p-3 max-h-56 overflow-y-auto">
                        <p class="text-xs text-gray-500 mb-2">Tick the students to bill (use the filter below to narrow the list).</p>
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
                            @foreach($programs as $p)<option value="{{ $p->id }}" @selected(request('program_id')==$p->id)>{{ $p->name }}</option>@endforeach
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
</x-app-layout>
