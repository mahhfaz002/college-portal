<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📚 Courses</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="courseList(@js($programs), @js($departments), @js($subjects))">

            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <p class="text-sm text-gray-500 mb-4">All courses created in <span class="font-semibold">Create Courses</span>. Use the filters to narrow by section, department, course of study and level.</p>
                <div class="grid md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Programme</label>
                        <select x-model="type" @change="deptId='';programId='';level=''" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All types</option>
                            <option value="UG">UG</option><option value="DIP">DIP</option><option value="CERT">CERT</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                        <select x-model="deptId" @change="programId='';level=''" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All departments</option>
                            <template x-for="d in departmentOptions()" :key="d.id"><option :value="d.id" x-text="d.dept_name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study</label>
                        <select x-model="programId" @change="level=''" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All courses of study</option>
                            <template x-for="p in coursesOfStudy()" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                        <select x-model="level" class="w-full border-gray-300 rounded-lg text-sm" :disabled="!programId">
                            <option value="">All levels</option>
                            <template x-for="l in levelOptions()" :key="l"><option :value="l" x-text="'L'+l"></option></template>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">
                    Courses (<span x-text="filtered().length"></span>)
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Course Title</th>
                            <th class="px-6 py-3 text-left">Code</th>
                            <th class="px-6 py-3 text-left">Unit</th>
                            <th class="px-6 py-3 text-left">Course of Study</th>
                            <th class="px-6 py-3 text-left">Level</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template x-for="c in filtered()" :key="c.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 font-semibold text-gray-800" x-text="c.name"></td>
                                <td class="px-6 py-3" x-text="c.course_code || '—'"></td>
                                <td class="px-6 py-3" x-text="c.course_unit ?? '—'"></td>
                                <td class="px-6 py-3 text-gray-500" x-text="programName(c.program_id)"></td>
                                <td class="px-6 py-3" x-text="c.level ? 'L'+c.level : '—'"></td>
                            </tr>
                        </template>
                        <tr x-show="filtered().length===0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">No courses match.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function courseList(programs, departments, subjects) {
            return {
                programs, departments, subjects,
                type: '', deptId: '', programId: '', level: '',
                departmentOptions() {
                    if (!this.type) return this.departments.map(d => ({id: d.id, dept_name: d.name}));
                    const seen = {}, out = [];
                    this.programs.filter(p => p.type === this.type).forEach(p => {
                        if (!seen[p.dept_id]) { seen[p.dept_id] = 1; out.push({id: p.dept_id, dept_name: p.dept_name}); }
                    });
                    return out;
                },
                coursesOfStudy() {
                    return this.programs.filter(p =>
                        (!this.type || p.type === this.type) &&
                        (!this.deptId || String(p.dept_id) === String(this.deptId)));
                },
                levelOptions() {
                    const p = this.programs.find(x => String(x.id) === String(this.programId));
                    const out = []; for (let i = 1; i <= (p ? p.levels : 0); i++) out.push(String(i * 100));
                    return out;
                },
                programName(id) { const p = this.programs.find(x => String(x.id) === String(id)); return p ? p.name : '—'; },
                filtered() {
                    return this.subjects.filter(s =>
                        (!this.deptId || String(s.dept_id) === String(this.deptId)) &&
                        (!this.programId || String(s.program_id) === String(this.programId)) &&
                        (!this.level || String(s.level) === String(this.level)) &&
                        (!this.type || this.programs.some(p => String(p.id) === String(s.program_id) && p.type === this.type)));
                },
            }
        }
    </script>
</x-app-layout>
