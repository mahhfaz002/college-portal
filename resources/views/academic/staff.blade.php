<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">👩‍🏫 Teaching Staff</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="staffAssign(@js($programs), @js($subjects))">

            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <p class="text-sm text-gray-500 mb-4">Lecturers and resource persons (and HODs who teach). Filter by department; a lecturer may hold courses across several departments, courses of study and levels.</p>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Filter by department</label>
                <select x-model="dept" class="w-full md:w-80 border-gray-300 rounded-lg text-sm">
                    <option value="">All departments</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </div>

            <div class="space-y-4">
                @forelse($lecturers as $l)
                    <div class="bg-white rounded-2xl shadow-sm border p-5" x-show="!dept || dept==='{{ $l->department_id }}'">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-bold text-gray-800">{{ $l->name }}
                                    @if($l->role !== 'lecturer')<span class="ml-1 text-[10px] uppercase font-bold bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">{{ $l->role }}</span>@endif
                                </p>
                                <p class="text-xs text-gray-400">{{ optional($l->departmentModel)->name ?? 'No department' }} · {{ $l->email }}</p>
                            </div>
                            <button type="button"
                                    @click="open({{ $l->id }}, @js($l->name))"
                                    class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold hover:bg-indigo-700 whitespace-nowrap">+ Assign Course</button>
                        </div>

                        <div class="mt-3">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-1">Assigned courses ({{ $l->subjects->count() }})</p>
                            @forelse($l->subjects as $s)
                                <span class="inline-flex items-center gap-1 bg-gray-100 rounded-full px-2 py-0.5 text-xs mr-1 mb-1">
                                    {{ $s->name }}{{ $s->course_code ? " ({$s->course_code})" : '' }}
                                    <span class="text-gray-400">· {{ optional($s->program)->name }}{{ $s->level ? " L{$s->level}" : '' }}</span>
                                    <form method="POST" action="{{ route('course-assignments.destroy') }}" class="inline" onsubmit="return confirm('Remove this assignment?')">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="subject_id" value="{{ $s->id }}">
                                        <input type="hidden" name="user_id" value="{{ $l->id }}">
                                        <button class="text-red-500 font-bold">&times;</button>
                                    </form>
                                </span>
                            @empty
                                <span class="text-sm text-gray-400">No courses assigned yet.</span>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-2xl shadow-sm border p-8 text-center text-gray-400">No teaching staff found.</div>
                @endforelse
            </div>

            {{-- Assign modal --}}
            <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal=false">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800">Assign courses to <span x-text="lecturerName"></span></h3>
                        <button @click="modal=false" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <select x-model="deptId" @change="programId='';level='';selected=[]" class="border-gray-300 rounded-lg text-sm">
                            <option value="">— Department —</option>
                            @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                        </select>
                        <select x-model="programId" @change="level='';selected=[]" class="border-gray-300 rounded-lg text-sm">
                            <option value="">— Course of study —</option>
                            <template x-for="p in coursesOfStudy()" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                        </select>
                        <select x-model="level" @change="selected=[]" class="border-gray-300 rounded-lg text-sm col-span-2" :disabled="!programId">
                            <option value="">— Level —</option>
                            <template x-for="l in levelOptions()" :key="l"><option :value="l" x-text="'L'+l"></option></template>
                        </select>
                    </div>

                    <form method="POST" action="{{ route('course-assignments.batch') }}" @submit="if(selected.length===0){alert('Tick at least one course.');$event.preventDefault();}">
                        @csrf
                        <input type="hidden" name="user_id" :value="lecturerId">
                        <template x-for="sid in selected" :key="sid"><input type="hidden" name="subject_ids[]" :value="sid"></template>

                        <div class="border rounded-xl divide-y max-h-56 overflow-y-auto" x-show="coursesInScope().length">
                            <template x-for="c in coursesInScope()" :key="c.id">
                                <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50">
                                    <input type="checkbox" :value="c.id" x-model.number="selected" class="rounded">
                                    <span class="font-medium text-gray-800" x-text="c.name"></span>
                                    <span class="text-xs text-gray-400" x-text="c.course_code||''"></span>
                                </label>
                            </template>
                        </div>
                        <p x-show="!coursesInScope().length" class="text-sm text-gray-400 py-3">Pick course of study and level to list courses.</p>

                        <div class="flex justify-end gap-2 mt-4">
                            <button type="button" @click="modal=false" class="px-4 py-2 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-100">Cancel</button>
                            <button class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700" x-show="coursesInScope().length">Assign <span x-text="selected.length"></span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function staffAssign(programs, subjects) {
            return {
                programs, subjects, dept: '',
                modal: false, lecturerId: '', lecturerName: '',
                deptId: '', programId: '', level: '', selected: [],
                open(id, name) { this.lecturerId = id; this.lecturerName = name; this.deptId=''; this.programId=''; this.level=''; this.selected=[]; this.modal = true; },
                coursesOfStudy() { return this.programs.filter(p => !this.deptId || String(p.dept_id)===String(this.deptId)); },
                levelOptions() {
                    const p = this.programs.find(x => String(x.id)===String(this.programId));
                    const out = []; for (let i=1;i<=(p?p.levels:0);i++) out.push(String(i*100));
                    return out;
                },
                coursesInScope() {
                    if (!this.programId || !this.level) return [];
                    return this.subjects.filter(s => String(s.program_id)===String(this.programId) && String(s.level)===String(this.level));
                },
            }
        }
    </script>
</x-app-layout>
