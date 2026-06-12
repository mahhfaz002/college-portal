<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏛️ Academic Structure</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            {{-- Builder --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6"
                 x-data="{ courses: [{name:'',acronym:'',levels:2,application_fee:'',acceptance_fee:'',registration_fee:''}] }">
                <h3 class="font-bold text-gray-800 mb-4">Create a Department</h3>
                <form method="POST" action="{{ route('structure.store') }}" class="space-y-5">
                    @csrf
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Section *</label>
                            <select name="section" required class="w-full border-gray-300 rounded-lg">
                                <option value="">— Select —</option>
                                @foreach($sections as $s)<option value="{{ $s }}">{{ $sectionLabels[$s] }}</option>@endforeach
                            </select>
                        </div>
                        <input name="department_name" required placeholder="Department name *" class="border-gray-300 rounded-lg">
                        <input name="department_acronym" placeholder="Dept. acronym" class="border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Courses of Study</label>
                        <template x-for="(c, i) in courses" :key="i">
                            <div class="grid md:grid-cols-12 gap-2 mb-2 items-center">
                                <input x-model="c.name" :name="`courses[${i}][name]`" required placeholder="Course of study name *" class="md:col-span-4 border-gray-300 rounded-lg text-sm">
                                <input x-model="c.acronym" :name="`courses[${i}][acronym]`" placeholder="Acronym" class="md:col-span-1 border-gray-300 rounded-lg text-sm">
                                <input x-model="c.levels" :name="`courses[${i}][levels]`" type="number" min="1" max="8" required placeholder="Levels" title="Number of levels" class="md:col-span-1 border-gray-300 rounded-lg text-sm">
                                <input x-model="c.application_fee" :name="`courses[${i}][application_fee]`" type="number" step="100" placeholder="App. fee" class="md:col-span-2 border-gray-300 rounded-lg text-sm">
                                <input x-model="c.acceptance_fee" :name="`courses[${i}][acceptance_fee]`" type="number" step="100" placeholder="Accept. fee" class="md:col-span-2 border-gray-300 rounded-lg text-sm">
                                <input x-model="c.registration_fee" :name="`courses[${i}][registration_fee]`" type="number" step="100" placeholder="Reg. fee" class="md:col-span-1 border-gray-300 rounded-lg text-sm">
                                <button type="button" @click="courses.splice(i,1)" x-show="courses.length>1" class="md:col-span-1 text-red-500 text-xs font-bold">Remove</button>
                            </div>
                        </template>
                        <button type="button" @click="courses.push({name:'',acronym:'',levels:2,application_fee:'',acceptance_fee:'',registration_fee:''})"
                                class="text-indigo-600 text-sm font-bold hover:underline">+ Add course of study</button>
                    </div>

                    <button class="bg-emerald-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-emerald-700">Create Department</button>
                </form>
            </div>

            {{-- Existing structure --}}
            @forelse($departmentsBySection as $section => $departments)
                <div>
                    <h3 class="text-sm font-bold text-gray-500 uppercase mb-2">{{ $sectionLabels[$section] ?? ($section ?: 'Unsectioned') }}</h3>
                    <div class="space-y-4">
                        @foreach($departments as $dept)
                            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden" x-data="{ open:false }">
                                <div class="px-6 py-4 flex items-center justify-between bg-gray-50 border-b">
                                    <div>
                                        <span class="font-bold text-gray-800">{{ $dept->name }}</span>
                                        <span class="text-xs text-gray-400">{{ $dept->acronym }} · {{ $dept->programs->count() }} course(s)</span>
                                    </div>
                                    <form method="POST" action="{{ route('structure.departments.destroy', $dept) }}" onsubmit="return confirm('Delete department and all its courses?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 text-xs font-bold hover:underline">Delete dept</button>
                                    </form>
                                </div>
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                                        <tr><th class="px-6 py-2 text-left">Course of Study</th><th class="px-4 py-2">Acr.</th><th class="px-4 py-2">Levels</th><th class="px-4 py-2 text-right">Actions</th></tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @forelse($dept->programs as $p)
                                            <tr x-data="{ edit:false }">
                                                <td class="px-6 py-2 font-semibold text-gray-700">
                                                    <span x-show="!edit">{{ $p->name }}</span>
                                                    <form x-show="edit" method="POST" action="{{ route('structure.courses.update', $p) }}" class="flex flex-wrap gap-1 items-center">
                                                        @csrf @method('PUT')
                                                        <input name="name" value="{{ $p->name }}" required class="border-gray-300 rounded text-xs py-1">
                                                        <input name="acronym" value="{{ $p->acronym }}" placeholder="Acr" class="border-gray-300 rounded text-xs py-1 w-16">
                                                        <input name="levels" type="number" min="1" max="8" value="{{ $p->levels }}" class="border-gray-300 rounded text-xs py-1 w-14">
                                                        <input name="application_fee" type="number" value="{{ (int)$p->application_fee }}" class="border-gray-300 rounded text-xs py-1 w-20" title="App fee">
                                                        <input name="acceptance_fee" type="number" value="{{ (int)$p->acceptance_fee }}" class="border-gray-300 rounded text-xs py-1 w-20" title="Accept fee">
                                                        <input name="registration_fee" type="number" value="{{ (int)$p->registration_fee }}" class="border-gray-300 rounded text-xs py-1 w-20" title="Reg fee">
                                                        <button class="bg-emerald-600 text-white px-2 py-1 rounded text-xs font-bold">Save</button>
                                                    </form>
                                                </td>
                                                <td class="px-4 py-2 text-center text-gray-500">{{ $p->acronym }}</td>
                                                <td class="px-4 py-2 text-center">{{ $p->levels }}</td>
                                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                                    <button type="button" @click="edit=!edit" class="text-indigo-600 text-xs font-bold hover:underline">Edit</button>
                                                    <form method="POST" action="{{ route('structure.courses.destroy', $p) }}" class="inline" onsubmit="return confirm('Delete this course of study?')">
                                                        @csrf @method('DELETE')
                                                        <button class="text-red-500 text-xs font-bold hover:underline ml-2">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="px-6 py-3 text-center text-gray-400">No courses of study.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                {{-- Add course to this dept --}}
                                <form method="POST" action="{{ route('structure.courses.add', $dept) }}" class="px-6 py-3 border-t flex flex-wrap gap-2 items-center bg-gray-50">
                                    @csrf
                                    <input name="name" required placeholder="New course of study" class="border-gray-300 rounded text-xs py-1">
                                    <input name="acronym" placeholder="Acr" class="border-gray-300 rounded text-xs py-1 w-16">
                                    <input name="levels" type="number" min="1" max="8" value="2" class="border-gray-300 rounded text-xs py-1 w-14" title="Levels">
                                    <button class="text-indigo-600 text-xs font-bold hover:underline">+ Add course</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-center text-gray-400">No departments yet. Create one above.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
