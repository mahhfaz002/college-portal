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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="{ fSection: '', fDept: '', q: '' }">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-medium">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm font-medium">{{ session('error') }}</div>
            @endif

            {{-- Filter by section / department (Others = staff with no department) --}}
            <div class="bg-white p-4 rounded-xl border flex flex-wrap gap-3 items-end mb-4">
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Section</label>
                    <select x-model="fSection" @change="fDept=''" class="border-gray-300 rounded-md text-sm">
                        <option value="">All sections</option>
                        @foreach($sections as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Department</label>
                    <select x-model="fDept" class="border-gray-300 rounded-md text-sm">
                        <option value="">All departments</option>
                        @foreach($departments as $d)<option value="{{ $d->name }}" x-show="!fSection || fSection==='{{ $d->section }}'">{{ $d->name }}</option>@endforeach
                        <option value="Others" x-show="!fSection || fSection==='Others'">Others (no department)</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Search</label>
                    <input x-model="q" type="search" placeholder="Name, email, staff ID…" class="w-full border-gray-300 rounded-md text-sm">
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                                <th class="p-3 font-bold">Staff</th>
                                <th class="p-3 font-bold">Staff ID</th>
                                <th class="p-3 font-bold">Role</th>
                                <th class="p-3 font-bold">Department</th>
                                <th class="p-3 font-bold">Contact</th>
                                <th class="p-3 font-bold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $member)
                            @php $dept = optional($member->departmentModel)->name; $sec = optional($member->departmentModel)->section ?: 'Others'; @endphp
                            <tr class="border-b hover:bg-gray-50"
                                x-show="(!fSection || fSection==='{{ $sec }}') && (!fDept || fDept==='{{ $dept ?? 'Others' }}') && (!q || '{{ strtolower($member->name) }}'.includes(q.toLowerCase()) || '{{ strtolower($member->email) }}'.includes(q.toLowerCase()) || '{{ strtolower($member->staff_id ?? '') }}'.includes(q.toLowerCase()))">
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
                                <td class="p-3 text-sm text-gray-600">{{ $dept ?? 'Others' }} <span class="text-gray-300 text-xs">· {{ $sec }}</span></td>
                                <td class="p-3 text-sm text-gray-600">{{ $member->phone ?? '—' }}</td>
                                <td class="p-3 text-right">
                                    <a href="{{ route('staff.show', $member) }}" class="bg-gray-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-gray-700">View</a>
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
