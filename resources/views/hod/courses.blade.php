<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📚 Department Courses @if($department) — {{ $department->name }} @endif
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="hodCourses(@js($programs), @js($subjects), @js($semesters))">

            @if(!$department)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">You have not been assigned to a department yet.</div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <p class="text-sm text-gray-500 mb-4">View-only. Filter your department's courses by course of study, level and semester.</p>
                <div class="grid md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study</label>
                        <select x-model="programId" @change="level=''" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All courses of study</option>
                            <template x-for="p in programs" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                        <select x-model="level" class="w-full border-gray-300 rounded-lg text-sm" :disabled="!programId">
                            <option value="">All levels</option>
                            <template x-for="l in levelOptions()" :key="l"><option :value="l" x-text="'L'+l"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Semester</label>
                        <select x-model="semester" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">All semesters</option>
                            <template x-for="s in semesters" :key="s"><option :value="s" x-text="s"></option></template>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Courses (<span x-text="filtered().length"></span>)</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Course Title</th>
                            <th class="px-6 py-3 text-left">Code</th>
                            <th class="px-6 py-3 text-left">Unit</th>
                            <th class="px-6 py-3 text-left">Course of Study</th>
                            <th class="px-6 py-3 text-left">Level</th>
                            <th class="px-6 py-3 text-left">Semester</th>
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
                                <td class="px-6 py-3 text-gray-500" x-text="c.semester || '—'"></td>
                            </tr>
                        </template>
                        <tr x-show="filtered().length===0"><td colspan="6" class="px-6 py-8 text-center text-gray-400">No courses match.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function hodCourses(programs, subjects, semesters) {
            return {
                programs, subjects, semesters,
                programId: '', level: '', semester: '',
                levelOptions() { const p = this.programs.find(x => String(x.id)===String(this.programId)); const out=[]; for(let i=1;i<=(p?p.levels:0);i++) out.push(String(i*100)); return out; },
                programName(id) { const p = this.programs.find(x => String(x.id)===String(id)); return p ? p.name : '—'; },
                filtered() {
                    return this.subjects.filter(s =>
                        (!this.programId || String(s.program_id)===String(this.programId)) &&
                        (!this.level || String(s.level)===String(this.level)) &&
                        (!this.semester || s.semester===this.semester));
                },
            }
        }
    </script>
</x-app-layout>
