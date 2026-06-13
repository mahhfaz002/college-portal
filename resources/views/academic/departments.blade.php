<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏛️ Departments</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{ departments: @js($departments), filter: '', open: null,
                       shown() { return this.filter ? this.departments.filter(d => String(d.id)===String(this.filter)) : this.departments; } }">

            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <p class="text-sm text-gray-500 mb-4">View-only. Pick a department to see its courses of study and the courses created under each.</p>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Filter by department</label>
                <select x-model="filter" class="w-full md:w-80 border-gray-300 rounded-lg text-sm">
                    <option value="">All departments</option>
                    <template x-for="d in departments" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                </select>
            </div>

            <div class="space-y-4">
                <template x-for="d in shown()" :key="d.id">
                    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                        <button type="button" @click="open = (open===d.id ? null : d.id)"
                                class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50">
                            <div>
                                <span class="font-bold text-gray-800" x-text="d.name"></span>
                                <span class="ml-2 text-xs text-gray-400" x-text="(d.acronym||'') + (d.section ? ' · '+d.section : '')"></span>
                            </div>
                            <span class="text-xs text-gray-500"><span x-text="d.programs.length"></span> course(s) of study</span>
                        </button>

                        <div x-show="open===d.id" x-collapse class="border-t">
                            <template x-if="d.programs.length===0">
                                <p class="px-6 py-4 text-sm text-gray-400">No courses of study yet.</p>
                            </template>
                            <template x-for="p in d.programs" :key="p.id">
                                <div class="px-6 py-4 border-b last:border-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="font-semibold text-gray-700" x-text="p.name"></span>
                                        <span class="text-[10px] uppercase font-bold bg-gray-100 text-gray-500 px-2 py-0.5 rounded" x-text="p.type"></span>
                                        <span class="text-xs text-gray-400" x-text="p.courses.length + ' course(s)'"></span>
                                    </div>
                                    <table class="w-full text-sm" x-show="p.courses.length">
                                        <thead class="text-xs uppercase text-gray-400">
                                            <tr><th class="text-left py-1">Course</th><th class="text-left py-1">Code</th><th class="text-left py-1">Unit</th><th class="text-left py-1">Level</th></tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <template x-for="c in p.courses" :key="c.id">
                                                <tr>
                                                    <td class="py-1.5 text-gray-800" x-text="c.name"></td>
                                                    <td class="py-1.5 text-gray-500" x-text="c.course_code || '—'"></td>
                                                    <td class="py-1.5 text-gray-500" x-text="c.course_unit ?? '—'"></td>
                                                    <td class="py-1.5 text-gray-500" x-text="c.level ? 'L'+c.level : '—'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                    <p x-show="!p.courses.length" class="text-xs text-gray-400">No courses created yet.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
