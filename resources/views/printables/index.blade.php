<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🖨️ Printables</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8"
             x-data="printables(@js($students), @js($staff), @js($programs), @js($departments))">

            {{-- ================= STUDENT RECEIPTS ================= --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Student Receipts</div>
                <div class="p-6 space-y-4">
                    <div class="grid md:grid-cols-5 gap-3">
                        <select x-model="s.section" @change="s.dept='';s.prog='';s.level=''" class="border-gray-300 rounded-lg text-sm">
                            <option value="">All sections</option>
                            @foreach($sections as $sec)<option value="{{ $sec }}">{{ $sec }}</option>@endforeach
                        </select>
                        <select x-model="s.dept" @change="s.prog='';s.level=''" class="border-gray-300 rounded-lg text-sm">
                            <option value="">All departments</option>
                            @foreach($departments as $d)<option value="{{ $d->id }}" x-show="!s.section || s.section==='{{ $d->section }}'">{{ $d->name }}</option>@endforeach
                        </select>
                        <select x-model="s.prog" @change="s.level=''" class="border-gray-300 rounded-lg text-sm">
                            <option value="">All courses of study</option>
                            <template x-for="p in studentCourses()" :key="p.id"><option :value="p.id" x-text="p.name"></option></template>
                        </select>
                        <select x-model="s.level" class="border-gray-300 rounded-lg text-sm" :disabled="!s.prog">
                            <option value="">All levels</option>
                            <template x-for="l in studentLevels()" :key="l"><option :value="l" x-text="'L'+l"></option></template>
                        </select>
                        <input x-model="s.q" type="search" placeholder="Search name or reg no…" class="border-gray-300 rounded-lg text-sm">
                    </div>

                    <div class="border rounded-xl divide-y max-h-96 overflow-y-auto">
                        <template x-for="st in filteredStudents()" :key="st.id">
                            <div class="flex items-center justify-between px-4 py-2.5 text-sm hover:bg-gray-50">
                                <div>
                                    <span class="font-semibold text-gray-800" x-text="st.name"></span>
                                    <span class="text-xs text-gray-400" x-text="' · '+(st.reg||'—')"></span>
                                </div>
                                <button @click="viewStudent(st)" class="bg-indigo-600 text-white px-3 py-1 rounded-lg text-xs font-bold hover:bg-indigo-700">View</button>
                            </div>
                        </template>
                        <p x-show="filteredStudents().length===0" class="px-4 py-6 text-center text-gray-400 text-sm">No students match.</p>
                    </div>
                    <p class="text-xs text-gray-400"><span x-text="filteredStudents().length"></span> student(s)</p>
                </div>
            </div>

            {{-- ================= PAYSLIPS ================= --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Payslips</div>
                <div class="p-6 space-y-4">
                    <div class="grid md:grid-cols-3 gap-3">
                        <select x-model="f.section" @change="f.dept=''" class="border-gray-300 rounded-lg text-sm">
                            <option value="">All sections</option>
                            @foreach($sections as $sec)<option value="{{ $sec }}">{{ $sec }}</option>@endforeach
                        </select>
                        <select x-model="f.dept" class="border-gray-300 rounded-lg text-sm">
                            <option value="">All departments</option>
                            @foreach($departments as $d)<option value="{{ $d->id }}" x-show="!f.section || f.section==='{{ $d->section }}'">{{ $d->name }}</option>@endforeach
                            <option value="others" x-show="!f.section || f.section==='Others'">Others (no department)</option>
                        </select>
                        <input x-model="f.q" type="search" placeholder="Search name or staff ID…" class="border-gray-300 rounded-lg text-sm">
                    </div>

                    <div class="border rounded-xl divide-y max-h-96 overflow-y-auto">
                        <template x-for="m in filteredStaff()" :key="m.id">
                            <div class="flex items-center justify-between px-4 py-2.5 text-sm hover:bg-gray-50">
                                <div>
                                    <span class="font-semibold text-gray-800" x-text="m.name"></span>
                                    <span class="text-xs text-gray-400" x-text="' · '+(m.staff_id||m.role)+' · '+(m.dept||'Others')"></span>
                                </div>
                                <button @click="viewStaff(m)" class="bg-indigo-600 text-white px-3 py-1 rounded-lg text-xs font-bold hover:bg-indigo-700">View</button>
                            </div>
                        </template>
                        <p x-show="filteredStaff().length===0" class="px-4 py-6 text-center text-gray-400 text-sm">No staff match.</p>
                    </div>
                    <p class="text-xs text-gray-400"><span x-text="filteredStaff().length"></span> staff</p>
                </div>
            </div>

            {{-- ================= MODAL ================= --}}
            <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" @click.self="modal=false">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
                    <div class="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white">
                        <h3 class="font-bold text-gray-800" x-text="modalTitle"></h3>
                        <button @click="modal=false" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
                    </div>
                    <div class="p-6 space-y-5">
                        <p x-show="loading" class="text-sm text-gray-400">Loading…</p>

                        {{-- Student view --}}
                        <template x-if="!loading && kind==='student'">
                            <div class="space-y-5">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase mb-2">Receipts (most recent first)</p>
                                    <div class="divide-y border rounded-lg" x-show="data.receipts && data.receipts.length">
                                        <template x-for="(r,i) in data.receipts" :key="i">
                                            <a :href="r.receipt_url" target="_blank" class="flex items-center justify-between px-3 py-2 text-sm hover:bg-indigo-50">
                                                <span><span class="font-semibold text-gray-800" x-text="r.description"></span><br><span class="text-xs text-gray-400" x-text="r.purpose+' · '+r.date+' · '+r.reference"></span></span>
                                                <span class="text-right"><span class="font-bold text-emerald-600" x-text="r.amount"></span><br><span class="text-xs text-indigo-600 font-semibold">Print / Download</span></span>
                                            </a>
                                        </template>
                                    </div>
                                    <p x-show="!data.receipts || !data.receipts.length" class="text-sm text-gray-400">No paid receipts.</p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase mb-2">Outstanding (unpaid) invoices</p>
                                    <div class="divide-y border rounded-lg" x-show="data.unpaid && data.unpaid.length">
                                        <template x-for="(u,i) in data.unpaid" :key="i">
                                            <div class="flex items-center justify-between px-3 py-2 text-sm">
                                                <span><span class="font-semibold text-gray-800" x-text="u.description"></span><br><span class="text-xs text-gray-400" x-text="u.purpose+' · '+u.date"></span></span>
                                                <span class="font-bold text-amber-600" x-text="u.amount"></span>
                                            </div>
                                        </template>
                                    </div>
                                    <p x-show="!data.unpaid || !data.unpaid.length" class="text-sm text-gray-400">No outstanding invoices. 🎉</p>
                                </div>
                            </div>
                        </template>

                        {{-- Staff view --}}
                        <template x-if="!loading && kind==='staff'">
                            <div>
                                <p class="text-xs font-bold text-gray-500 uppercase mb-2">Payslips (most recent first)</p>
                                <div class="divide-y border rounded-lg" x-show="data.payslips && data.payslips.length">
                                    <template x-for="(p,i) in data.payslips" :key="i">
                                        <div class="flex items-center justify-between px-3 py-2 text-sm">
                                            <span><span class="font-semibold text-gray-800" x-text="p.month"></span><br><span class="text-xs text-gray-400" x-text="'Net '+p.net+' · '+p.status"></span></span>
                                            <span class="text-right" x-show="p.slip_url">
                                                <a :href="p.slip_url" target="_blank" class="text-xs text-indigo-600 font-semibold">Print</a>
                                                <a :href="p.pdf_url" class="text-xs text-emerald-600 font-semibold ml-2">PDF</a>
                                            </span>
                                            <span x-show="!p.slip_url" class="text-xs text-gray-400 italic" x-text="p.status"></span>
                                        </div>
                                    </template>
                                </div>
                                <p x-show="!data.payslips || !data.payslips.length" class="text-sm text-gray-400">No payslips yet.</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printables(students, staff, programs, departments) {
            return {
                students, staff, programs, departments,
                s: { section:'', dept:'', prog:'', level:'', q:'' },
                f: { section:'', dept:'', q:'' },
                modal: false, modalTitle: '', kind: '', loading: false, data: {},
                studentCourses() { return this.programs.filter(p => (!this.s.section || p.section===this.s.section) && (!this.s.dept || String(p.dept_id)===String(this.s.dept))); },
                studentLevels() { const p = this.programs.find(x => String(x.id)===String(this.s.prog)); const out=[]; for(let i=1;i<=(p?p.levels:0);i++) out.push(String(i*100)); return out; },
                filteredStudents() {
                    const q = this.s.q.trim().toLowerCase();
                    return this.students.filter(st =>
                        (!this.s.section || st.section===this.s.section) &&
                        (!this.s.dept || String(st.dept_id)===String(this.s.dept)) &&
                        (!this.s.prog || String(st.program_id)===String(this.s.prog)) &&
                        (!this.s.level || String(st.level)===String(this.s.level)) &&
                        (!q || st.name.toLowerCase().includes(q) || (st.reg||'').toLowerCase().includes(q)));
                },
                filteredStaff() {
                    const q = this.f.q.trim().toLowerCase();
                    return this.staff.filter(m =>
                        (!this.f.section || m.section===this.f.section) &&
                        (!this.f.dept || (this.f.dept==='others' ? !m.dept_id : String(m.dept_id)===String(this.f.dept))) &&
                        (!q || m.name.toLowerCase().includes(q) || (m.staff_id||'').toLowerCase().includes(q)));
                },
                async viewStudent(st) {
                    this.kind='student'; this.modalTitle = st.name+' — '+(st.reg||''); this.modal=true; this.loading=true; this.data={};
                    const r = await fetch(`{{ url('printables/student') }}/${st.id}/receipts`, {headers:{'Accept':'application/json'}});
                    this.data = await r.json(); this.loading=false;
                },
                async viewStaff(m) {
                    this.kind='staff'; this.modalTitle = m.name+(m.staff_id?' — '+m.staff_id:''); this.modal=true; this.loading=true; this.data={};
                    const r = await fetch(`{{ url('printables/staff') }}/${m.id}/payslips`, {headers:{'Accept':'application/json'}});
                    this.data = await r.json(); this.loading=false;
                },
            }
        }
    </script>
</x-app-layout>
