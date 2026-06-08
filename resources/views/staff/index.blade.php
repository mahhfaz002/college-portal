<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">👥 Staff Directory</h2>
            @can('manage_staff')
            <a href="{{ route('staff.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-sm text-sm">
                + Register Staff
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-medium">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm font-medium">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                                <th class="p-3 font-bold">Staff</th>
                                <th class="p-3 font-bold">Staff ID</th>
                                <th class="p-3 font-bold">Role</th>
                                <th class="p-3 font-bold">Classes</th>
                                <th class="p-3 font-bold">Subjects</th>
                                <th class="p-3 font-bold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $member)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        @if($member->passport)
                                            <img src="{{ $member->passport }}" class="w-9 h-9 rounded-full object-cover border" alt="">
                                        @else
                                            <div class="w-9 h-9 bg-indigo-100 rounded-full flex items-center justify-center font-bold text-indigo-700 text-sm">{{ strtoupper(substr($member->name,0,1)) }}</div>
                                        @endif
                                        <div>
                                            <p class="font-bold text-gray-800 text-sm">{{ $member->name }}</p>
                                            <p class="text-xs text-gray-400">{{ $member->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3 text-xs font-mono text-gray-600">{{ $member->staff_id ?? '—' }}</td>
                                <td class="p-3"><span class="uppercase text-[10px] font-bold px-2 py-1 bg-gray-200 rounded">{{ str_replace('_',' ',$member->role) }}</span></td>
                                <td class="p-3 text-sm text-gray-600">{{ $member->classes_count }}</td>
                                <td class="p-3 text-sm text-gray-600">{{ $member->subjects_count }}</td>
                                <td class="p-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('manage_staff')
                                        @if($member->role === 'teacher')
                                        <details class="relative text-left">
                                            <summary class="cursor-pointer bg-green-600 text-white text-xs px-3 py-1.5 rounded font-bold list-none">Assign</summary>
                                            <div class="absolute right-0 z-10 mt-1 w-64 bg-white border rounded-lg shadow-lg p-3">
                                                <form action="{{ route('staff.assignments', $member) }}" method="POST">
                                                    @csrf
                                                    <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Classes</p>
                                                    <div class="grid grid-cols-2 gap-1 max-h-28 overflow-y-auto mb-2">
                                                        @foreach($allClasses as $c)
                                                            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="class_ids[]" value="{{ $c->id }}" {{ $member->classes->contains($c->id) ? 'checked' : '' }}> {{ $c->name }}</label>
                                                        @endforeach
                                                    </div>
                                                    <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Subjects</p>
                                                    <div class="grid grid-cols-2 gap-1 max-h-28 overflow-y-auto mb-2">
                                                        @foreach($allSubjects as $s)
                                                            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="subject_ids[]" value="{{ $s->id }}" {{ $member->subjects->contains($s->id) ? 'checked' : '' }}> {{ $s->name }}</label>
                                                        @endforeach
                                                    </div>
                                                    <button class="w-full bg-indigo-600 text-white text-xs py-1.5 rounded font-bold">Save</button>
                                                </form>
                                            </div>
                                        </details>
                                        @endif
                                        @endcan
                                        <a href="{{ route('staff.show', $member) }}" class="bg-gray-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-gray-700">View</a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="p-8 text-center text-gray-400 italic">No staff yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
