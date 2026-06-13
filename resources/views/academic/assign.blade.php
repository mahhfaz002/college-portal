<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎯 Assign Courses</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="assignCourses(@js($programs), @js($departments), @js($subjects), @js($lecturers), @js($assignments))">

            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            {{-- Cascade filter --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="grid md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Programme</label>
                        <select x-model="type" @change="deptId='';programId='';level='';selected=[]" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All types</option><option value="UG">UG</option><option value="DIP">DIP</option><option value="CERT">CERT</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                        <select x-model="deptId" @change="programId='';level='';selected=[];lecturerId=''" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All departments</option>
                            <template x-for="d in departmentOptions()" :key="d.id"><option :value="d.id" x-text="d.dept_name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study</label>
                        <select x-model="programId" @change="level='';selected=[]" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All courses of study</option>
                            <template x-for="p in coursesOfStudy()" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                        <select x-model="level" @change="selected=[]" class="w-full border-gray-300 rounded-lg text-sm" :disabled="!programId">
                            <option value="">All levels</option>
                            <template x-for="l in levelOptions()" :key="l"><option :value="l" x-text="'L'+l"></option></template>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Assign panel --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <h3 class="font-bold text-gray-800 mb-4">Assign courses to a lecturer</h3>

                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Lecturer / Resource person</label>
                <select x-model="lecturerId" class="w-full md:w-96 border-gray-300 rounded-lg text-sm mb-4">
                    <option value="">— Select staff —</option>
                    <template x-for="l in lecturerOptions()" :key="l.id">
                        <option :value="l.id" x-text="l.name + (l.role!=='lecturer' ? ' ('+l.role.toUpperCase()+')' : '')"></option>
                    </template>
                </select>

                <form method="POST" action="{{ route('course-assignments.batch') }}" @submit="if(!lecturerId||selected.length===0){alert('Pick a lecturer and at least one course.');$event.preventDefault();}">
                    @csrf
                    <input type="hidden" name="user_id" :value="lecturerId">
                    <template x-for="sid in selected" :key="sid"><input type="hidden" name="subject_ids[]" :value="sid"></template>

                    <p class="text-xs font-bold text-gray-500 uppercase mb-2">Courses
                        <span x-show="programId && level" class="text-gray-400 font-normal">(tick to assign)</span></p>

                    <div class="border rounded-xl divide-y max-h-72 overflow-y-auto" x-show="coursesInScope().length">
                        <template x-for="c in coursesInScope()" :key="c.id">
                            <label class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50">
                                <input type="checkbox" :value="c.id" x-model.number="selected" class="rounded">
                                <span class="font-medium text-gray-800" x-text="c.name"></span>
                                <span class="text-xs text-gray-400" x-text="(c.course_code||'') + (c.level?' · L'+c.level:'')"></span>
                                <span class="ml-auto text-[11px] text-gray-400" x-text="assignedNames(c.id)"></span>
                            </label>
                        </template>
                    </div>
                    <p x-show="!coursesInScope().length" class="text-sm text-gray-400">Select a course of study and level to list its courses.</p>

                    <button class="mt-4 bg-indigo-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-indigo-700"
                            x-show="coursesInScope().length">Assign <span x-text="selected.length"></span> course(s)</button>
                </form>
            </div>

            {{-- Already-assigned, editable --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Current assignments <span class="text-gray-400 font-normal text-sm">(in the filtered scope)</span></div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                        <th class="px-6 py-3 text-left">Course</th><th class="px-6 py-3 text-left">Assigned lecturer(s)</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        <template x-for="c in assignedInScope()" :key="c.id">
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800" x-text="c.name + (c.course_code?' ('+c.course_code+')':'')"></td>
                                <td class="px-6 py-3">
                                    <template x-for="uid in (assignments[c.id]||[])" :key="uid">
                                        <span class="inline-flex items-center gap-1 bg-gray-100 rounded-full px-2 py-0.5 text-xs mr-1 mb-1">
                                            <span x-text="lecturerName(uid)"></span>
                                            <form method="POST" action="{{ route('course-assignments.destroy') }}" class="inline" onsubmit="return confirm('Remove this assignment?')">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="subject_id" :value="c.id">
                                                <input type="hidden" name="user_id" :value="uid">
                                                <button class="text-red-500 font-bold">&times;</button>
                                            </form>
                                        </span>
                                    </template>
                                    <span x-show="!(assignments[c.id]||[]).length" class="text-gray-400">Unassigned</span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="assignedInScope().length===0"><td colspan="2" class="px-6 py-8 text-center text-gray-400">No courses in this scope.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function assignCourses(programs, departments, subjects, lecturers, assignments) {
            return {
                programs, departments, subjects, lecturers, assignments,
                type: '', deptId: '', programId: '', level: '', lecturerId: '', selected: [],
                departmentOptions() {
                    if (!this.type) return this.departments.map(d => ({id: d.id, dept_name: d.name}));
                    const seen = {}, out = [];
                    this.programs.filter(p => p.type === this.type).forEach(p => {
                        if (!seen[p.dept_id]) { seen[p.dept_id] = 1; out.push({id: p.dept_id, dept_name: p.dept_name}); }
                    });
                    return out;
                },
                coursesOfStudy() {
                    return this.programs.filter(p => (!this.type || p.type === this.type) && (!this.deptId || String(p.dept_id)===String(this.deptId)));
                },
                levelOptions() {
                    const p = this.programs.find(x => String(x.id) === String(this.programId));
                    const out = []; for (let i = 1; i <= (p ? p.levels : 0); i++) out.push(String(i * 100));
                    return out;
                },
                lecturerOptions() {
                    return this.lecturers.filter(l => !this.deptId || String(l.dept_id) === String(this.deptId));
                },
                coursesInScope() {
                    if (!this.programId || !this.level) return [];
                    return this.subjects.filter(s => String(s.program_id)===String(this.programId) && String(s.level)===String(this.level));
                },
                assignedInScope() {
                    return this.subjects.filter(s =>
                        (!this.deptId || String(s.dept_id)===String(this.deptId)) &&
                        (!this.programId || String(s.program_id)===String(this.programId)) &&
                        (!this.level || String(s.level)===String(this.level)));
                },
                lecturerName(id) { const l = this.lecturers.find(x => String(x.id)===String(id)); return l ? l.name : ('#'+id); },
                assignedNames(sid) { const ids = this.assignments[sid]||[]; return ids.length ? '→ '+ids.map(i=>this.lecturerName(i)).join(', ') : ''; },
            }
        }
    </script>
</x-app-layout>
