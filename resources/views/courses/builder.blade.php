<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📘 Create Courses</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white rounded-2xl shadow-sm border p-6"
                 x-data="courseBuilder(@js($programs))">

                {{-- Cascading selectors --}}
                <div class="grid md:grid-cols-4 gap-3 mb-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Programme</label>
                        <select x-model="type" @change="reset()" class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">— Type —</option>
                            <option value="UG">UG</option><option value="DIP">DIP</option><option value="CERT">CERT</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                        <select x-model="deptId" @change="programId=''; level=''; courses=[]" class="w-full border-gray-300 rounded-lg text-sm" :disabled="!type">
                            <option value="">— Department —</option>
                            <template x-for="d in departments()" :key="d.id"><option :value="d.id" x-text="d.dept_name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study</label>
                        <select x-model="programId" @change="level=''; courses=[]" class="w-full border-gray-300 rounded-lg text-sm" :disabled="!deptId">
                            <option value="">— Course —</option>
                            <template x-for="p in coursesOfStudy()" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                        <select x-model="level" @change="loadExisting()" class="w-full border-gray-300 rounded-lg text-sm" :disabled="!programId">
                            <option value="">— Level —</option>
                            <template x-for="l in levelOptions()" :key="l"><option :value="l" x-text="l"></option></template>
                        </select>
                    </div>
                </div>

                {{-- Course rows --}}
                <template x-if="programId && level">
                    <form method="POST" action="{{ route('courses.builder.save') }}" @submit="syncHidden()">
                        @csrf
                        <input type="hidden" name="program_id" :value="programId">
                        <input type="hidden" name="level" :value="level">

                        <div class="overflow-x-auto border rounded-xl">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr><th class="px-3 py-2 text-left">Course Title</th><th class="px-3 py-2 text-left">Course Code</th><th class="px-3 py-2 text-left">Unit</th><th class="px-3 py-2"></th></tr>
                                </thead>
                                <tbody>
                                    <template x-for="(c, i) in courses" :key="i">
                                        <tr class="border-t">
                                            <td class="px-3 py-2"><input x-model="c.name" :name="`courses[${i}][name]`" required class="w-full border-gray-300 rounded text-sm py-1" placeholder="e.g. Human Anatomy"></td>
                                            <td class="px-3 py-2"><input x-model="c.course_code" :name="`courses[${i}][course_code]`" required class="w-full border-gray-300 rounded text-sm py-1 uppercase" placeholder="e.g. ANA 101" style="text-transform:uppercase"></td>
                                            <td class="px-3 py-2"><input type="number" min="1" max="12" x-model="c.course_unit" :name="`courses[${i}][course_unit]`" required class="w-20 border-gray-300 rounded text-sm py-1"></td>
                                            <td class="px-3 py-2 text-right"><button type="button" @click="courses.splice(i,1)" class="text-red-500 text-xs font-bold hover:underline">Delete</button></td>
                                        </tr>
                                    </template>
                                    <tr x-show="courses.length===0"><td colspan="4" class="px-3 py-4 text-center text-gray-400 text-sm">No courses yet — add one below.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between mt-4">
                            <button type="button" @click="courses.push({name:'',course_code:'',course_unit:3})" class="bg-gray-100 border px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-200">+ Add Course</button>
                            <button type="submit" class="bg-emerald-600 text-white px-8 py-2 rounded-lg font-bold hover:bg-emerald-700" x-show="courses.length>0">Submit Courses</button>
                        </div>
                    </form>
                </template>
                <p x-show="!(programId && level)" class="text-sm text-gray-400">Select programme type, department, course of study and level to begin.</p>
            </div>
        </div>
    </div>

    <script>
        function courseBuilder(programs) {
            return {
                all: programs, type: '', deptId: '', programId: '', level: '', courses: [],
                departments() {
                    const seen = {}; const out = [];
                    this.all.filter(p => !this.type || p.type === this.type).forEach(p => {
                        if (!seen[p.dept_id]) { seen[p.dept_id] = 1; out.push({id: p.dept_id, dept_name: p.dept_name}); }
                    });
                    return out;
                },
                coursesOfStudy() {
                    return this.all.filter(p => (!this.type || p.type === this.type) && String(p.dept_id) === String(this.deptId));
                },
                levelOptions() {
                    const p = this.all.find(x => String(x.id) === String(this.programId));
                    const n = p ? p.levels : 0; const out = [];
                    for (let i = 1; i <= n; i++) out.push(String(i * 100));
                    return out;
                },
                reset() { this.deptId=''; this.programId=''; this.level=''; this.courses=[]; },
                async loadExisting() {
                    this.courses = [];
                    if (!this.programId || !this.level) return;
                    const url = `{{ route('courses.builder.list') }}?program_id=${this.programId}&level=${encodeURIComponent(this.level)}`;
                    const res = await fetch(url, {headers: {'Accept': 'application/json'}});
                    const data = await res.json();
                    this.courses = (data.courses || []).map(c => ({name: c.name, course_code: c.course_code, course_unit: c.course_unit}));
                },
                syncHidden() { /* names are bound via :name, nothing extra needed */ }
            }
        }
    </script>
</x-app-layout>
