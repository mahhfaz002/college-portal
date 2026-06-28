<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $union->exists ? 'Edit' : 'Register' }} Union / Organization</h2>
            <a href="{{ route('affairs.unions.index') }}" class="text-sm text-gray-500 font-bold">← All Unions</a>
        </div>
    </x-slot>

    @php
        $initialRows = (is_iterable($leaders) && count($leaders))
            ? collect($leaders)->map(fn ($l) => [
                'name' => $l->name, 'department' => $l->department, 'course_of_study' => $l->course_of_study,
                'level' => $l->level, 'position' => $l->position,
                'tenure_start' => optional($l->tenure_start)->format('Y-m-d') ?: now()->format('Y-m-d'),
            ])->values()->all()
            : [['name' => '', 'department' => '', 'course_of_study' => '', 'level' => '', 'position' => '', 'tenure_start' => now()->format('Y-m-d')]];
    @endphp

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{ rows: {{ \Illuminate\Support\Js::from($initialRows) }},
                       addRow() { this.rows.push({ name:'', department:'', course_of_study:'', level:'', position:'', tenure_start: '{{ now()->format('Y-m-d') }}' }); },
                       removeRow(i) { this.rows.splice(i, 1); } }">

            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <form method="POST" action="{{ $union->exists ? route('affairs.unions.update', $union) : route('affairs.unions.store') }}" class="space-y-6">
                @csrf
                @if($union->exists) @method('PUT') @endif

                {{-- Union details --}}
                <div class="bg-white rounded-2xl shadow-sm border p-6 space-y-4">
                    <h3 class="font-bold text-gray-700">Union / Association Details</h3>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Name *</label>
                            <input name="name" required value="{{ old('name', $union->name) }}" class="w-full border-gray-300 rounded-lg" placeholder="e.g. Student Union Government">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Acronym</label>
                            <input name="acronym" value="{{ old('acronym', $union->acronym) }}" class="w-full border-gray-300 rounded-lg" placeholder="e.g. SUG">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Year Established</label>
                            <input type="number" name="year_established" min="1900" max="{{ date('Y') }}" value="{{ old('year_established', $union->year_established) }}" class="w-full border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Members (number)</label>
                            <input type="number" name="members_count" min="0" value="{{ old('members_count', $union->members_count ?? 0) }}" class="w-full border-gray-300 rounded-lg">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Constituents of the union / association</label>
                        <textarea name="constituents" rows="2" class="w-full border-gray-300 rounded-lg" placeholder="Who this body represents, e.g. all registered students, all Pharmacy Technician students, students of indigene…">{{ old('constituents', $union->constituents) }}</textarea>
                    </div>
                </div>

                {{-- Leadership --}}
                <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Current Leadership <span class="text-xs font-normal text-gray-400">(tenure runs one year from the start date)</span></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 text-left">Name</th>
                                    <th class="px-3 py-2 text-left">Department</th>
                                    <th class="px-3 py-2 text-left">Course of Study</th>
                                    <th class="px-3 py-2 text-left">Level</th>
                                    <th class="px-3 py-2 text-left">Position</th>
                                    <th class="px-3 py-2 text-left">Tenure Start</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, i) in rows" :key="i">
                                    <tr class="border-t">
                                        <td class="px-3 py-2"><input :name="`leaders[${i}][name]`" x-model="row.name" class="w-full border-gray-300 rounded text-sm" placeholder="Full name"></td>
                                        <td class="px-3 py-2"><input :name="`leaders[${i}][department]`" x-model="row.department" class="w-full border-gray-300 rounded text-sm"></td>
                                        <td class="px-3 py-2"><input :name="`leaders[${i}][course_of_study]`" x-model="row.course_of_study" class="w-full border-gray-300 rounded text-sm"></td>
                                        <td class="px-3 py-2"><input :name="`leaders[${i}][level]`" x-model="row.level" class="w-20 border-gray-300 rounded text-sm" placeholder="100"></td>
                                        <td class="px-3 py-2"><input :name="`leaders[${i}][position]`" x-model="row.position" class="w-full border-gray-300 rounded text-sm" placeholder="President"></td>
                                        <td class="px-3 py-2"><input type="date" :name="`leaders[${i}][tenure_start]`" x-model="row.tenure_start" class="border-gray-300 rounded text-sm"></td>
                                        <td class="px-3 py-2 text-right"><button type="button" @click="removeRow(i)" class="text-red-500 text-xs font-bold hover:underline">Remove</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 border-t bg-gray-50">
                        <button type="button" @click="addRow()" class="bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-800">+ Add Member</button>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('affairs.unions.index') }}" class="px-6 py-2.5 rounded-full border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50">Cancel</a>
                    <button class="bg-indigo-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-indigo-700">{{ $union->exists ? 'Save Changes' : 'Register Union' }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
