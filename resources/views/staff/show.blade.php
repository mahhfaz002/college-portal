<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Staff Profile</h2>
            <a href="{{ route('staff.index') }}" class="text-sm text-gray-500 font-bold">← Back to Directory</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-medium">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm font-medium">{{ session('error') }}</div>
            @endif

            <!-- Profile card -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex flex-col sm:flex-row gap-6">
                    @if($staff->passport)
                        <img src="{{ $staff->passport }}" class="w-28 h-28 rounded-lg object-cover border" alt="">
                    @else
                        <div class="w-28 h-28 rounded-lg bg-indigo-100 flex items-center justify-center text-4xl font-black text-indigo-600">{{ strtoupper(substr($staff->name,0,1)) }}</div>
                    @endif
                    <div class="flex-1">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-black text-gray-800">{{ $staff->name }}</h3>
                                <span class="uppercase text-[10px] font-bold px-2 py-1 bg-gray-200 rounded">{{ str_replace('_',' ',$staff->role) }}</span>
                                <span class="uppercase text-[10px] font-bold px-2 py-1 rounded {{ $staff->status==='active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">{{ $staff->status }}</span>
                            </div>
                            <div class="flex gap-2">
                                @can('manage_staff')
                                <a href="{{ route('staff.edit', $staff) }}" class="bg-gray-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-gray-700">Edit</a>
                                @endcan
                                <a href="{{ route('staff.id-card', $staff) }}" class="bg-indigo-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-indigo-700">ID Card</a>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mt-4 text-sm">
                            <p><span class="text-gray-400">Staff ID:</span> <span class="font-mono font-bold">{{ $staff->staff_id ?? '—' }}</span></p>
                            <p><span class="text-gray-400">Email:</span> {{ $staff->email }}</p>
                            <p><span class="text-gray-400">Phone:</span> {{ $staff->phone ?? '—' }}</p>
                            <p><span class="text-gray-400">Department:</span> {{ $staff->department ?? '—' }}</p>
                            <p><span class="text-gray-400">Employed:</span> {{ $staff->employed_year ?? '—' }}</p>
                            <p><span class="text-gray-400">Next of Kin:</span> {{ $staff->next_of_kin_name ?? '—' }} {{ $staff->next_of_kin_phone ? '('.$staff->next_of_kin_phone.')' : '' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignments -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Class & Subject Assignments</h3>

                @can('manage_staff')
                <form action="{{ route('staff.assignments', $staff) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Classes</label>
                            <div class="grid grid-cols-2 gap-1 max-h-44 overflow-y-auto border rounded-md p-2">
                                @foreach($allClasses as $c)
                                    <label class="flex items-center gap-2 text-sm py-1">
                                        <input type="checkbox" name="class_ids[]" value="{{ $c->id }}" class="rounded" {{ $staff->classes->contains($c->id) ? 'checked' : '' }}> {{ $c->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Subjects</label>
                            <div class="grid grid-cols-2 gap-1 max-h-44 overflow-y-auto border rounded-md p-2">
                                @foreach($allSubjects as $s)
                                    <label class="flex items-center gap-2 text-sm py-1">
                                        <input type="checkbox" name="subject_ids[]" value="{{ $s->id }}" class="rounded" {{ $staff->subjects->contains($s->id) ? 'checked' : '' }}> {{ $s->name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="text-right mt-4">
                        <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-green-700 text-sm">Save Assignments</button>
                    </div>
                </form>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase mb-2">Classes</p>
                            {{ $staff->classes->pluck('name')->implode(', ') ?: '—' }}
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase mb-2">Subjects</p>
                            {{ $staff->subjects->pluck('name')->implode(', ') ?: '—' }}
                        </div>
                    </div>
                @endcan
            </div>

            @can('manage_staff')
            <div class="bg-white p-6 rounded-xl shadow-sm border border-red-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-red-700">Danger Zone</h3>
                        <p class="text-xs text-gray-500">Permanently remove this staff account.</p>
                    </div>
                    <form action="{{ route('staff.destroy', $staff) }}" method="POST" onsubmit="return confirm('Delete {{ $staff->name }}? This cannot be undone.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-red-700 text-sm">Delete Staff</button>
                    </form>
                </div>
            </div>
            @endcan
        </div>
    </div>
</x-app-layout>
