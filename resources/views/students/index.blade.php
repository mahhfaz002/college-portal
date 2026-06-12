<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎓 {{ __('Students') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))<div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            {{-- Cascading filter: Section → Department → Course of Study → Level --}}
            <div class="bg-white rounded-2xl shadow-sm border p-5 mb-6"
                 x-data="{
                    section: '{{ request('section') }}',
                    department_id: '{{ request('department_id') }}',
                    program_id: '{{ request('program_id') }}',
                    depts: {{ Illuminate\Support\Js::from($departments->map(fn($d)=>['id'=>$d->id,'name'=>$d->name,'section'=>$d->section])) }},
                    progs: {{ Illuminate\Support\Js::from($programs->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'department_id'=>$p->department_id,'levels'=>$p->levels])) }},
                    get deptOptions(){ return this.section ? this.depts.filter(d=>d.section==this.section) : this.depts },
                    get progOptions(){ return this.department_id ? this.progs.filter(p=>p.department_id==this.department_id) : [] },
                    get levelCount(){ const p=this.progs.find(p=>p.id==this.program_id); return p ? Number(p.levels) : 0 },
                 }">
                <form action="{{ route('students.index') }}" method="GET" class="flex flex-wrap gap-2 items-center">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name / reg no…" class="border-gray-300 rounded-lg text-sm">

                    <select name="section" x-model="section" @change="department_id=''; program_id=''" class="border-gray-300 rounded-lg text-sm">
                        <option value="">All sections</option>
                        @foreach($sections as $s)<option value="{{ $s }}">{{ $sectionLabels[$s] }}</option>@endforeach
                    </select>

                    <select name="department_id" x-model="department_id" @change="program_id=''" class="border-gray-300 rounded-lg text-sm">
                        <option value="">All departments</option>
                        <template x-for="d in deptOptions" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                    </select>

                    <select name="program_id" x-model="program_id" class="border-gray-300 rounded-lg text-sm" :disabled="!department_id">
                        <option value="">All courses of study</option>
                        <template x-for="p in progOptions" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                    </select>

                    <select name="level" class="border-gray-300 rounded-lg text-sm">
                        <option value="">All levels</option>
                        <template x-for="n in levelCount" :key="n"><option :value="n*100" x-text="(n*100)" {{ request('level') ? '' : '' }}></option></template>
                    </select>

                    <button class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-bold">Filter</button>
                    @if(request()->hasAny(['search','section','department_id','program_id','level']))
                        <a href="{{ route('students.index') }}" class="text-sm text-red-600 underline">Clear</a>
                    @endif
                </form>
                @if(request('level'))<p class="text-xs text-gray-400 mt-2">Showing level {{ request('level') }}.</p>@endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">S/N</th>
                            <th class="px-4 py-3 text-left">Full Name</th>
                            <th class="px-4 py-3 text-left">Reg / Adm No</th>
                            <th class="px-4 py-3 text-left">Course of Study</th>
                            <th class="px-4 py-3 text-left">Level</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($students as $student)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-400">{{ $students->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-2 font-semibold">
                                    <a href="{{ route('students.show', $student->id) }}" class="text-blue-600 hover:underline">{{ $student->full_name }}</a>
                                </td>
                                <td class="px-4 py-2 text-gray-600">{{ $student->registration_number ?? $student->admission_number }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ $student->program->name ?? $student->class_arm }}</td>
                                <td class="px-4 py-2">{{ $student->level }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex justify-end items-center gap-2">
                                        <a href="{{ route('students.show', $student->id) }}" class="bg-gray-100 text-gray-700 px-3 py-1 rounded text-xs font-bold hover:bg-gray-200">View</a>
                                        @can('manage_fees')
                                            <a href="{{ route('payments.create', $student->id) }}" class="bg-emerald-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-emerald-700">Pay Fees</a>
                                        @endcan
                                        @can('edit_students')
                                            <a href="{{ route('students.edit', $student->id) }}" class="bg-indigo-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-indigo-700">Edit</a>
                                        @endcan
                                        @can('manage_students')
                                            <form action="{{ route('students.destroy', $student->id) }}" method="POST" onsubmit="return confirm('Delete this student?');" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="bg-red-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-red-700">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400 italic">No students match the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($students instanceof \Illuminate\Pagination\LengthAwarePaginator && $students->total() > 0)
                <div class="mt-4">
                    <p class="text-xs text-gray-500 mb-2">Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} students (50 per page).</p>
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
