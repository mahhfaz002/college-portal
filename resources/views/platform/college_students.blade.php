<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎓 Registered Students — {{ $college->name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <a href="{{ route('platform.colleges.show', $college) }}" class="text-sm text-indigo-600 hover:underline">← Back to {{ $college->name }}</a>

            {{-- Upload registered students (CSV) — college pre-selected --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <h3 class="font-bold text-gray-800 mb-1">Upload registered students (CSV)</h3>
                <p class="text-sm text-gray-500 mb-4">
                    The CSV must have a header row with these columns (any order):
                    <code class="bg-gray-100 px-1 rounded">Full Name</code>,
                    <code class="bg-gray-100 px-1 rounded">Registration Number</code>,
                    <code class="bg-gray-100 px-1 rounded">Department</code>,
                    <code class="bg-gray-100 px-1 rounded">Level</code>.
                    Re-uploading updates existing rows (matched by registration number). Students then create their own
                    account by entering their registration number.
                </p>
                <form method="POST" action="{{ route('platform.admitted-records.upload') }}" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end">
                    @csrf
                    <input type="hidden" name="college_id" value="{{ $college->id }}">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">CSV file</label>
                        <input type="file" name="csv" accept=".csv,text/csv" required class="block text-sm">
                    </div>
                    <button class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-emerald-700 text-sm">Import</button>
                </form>
            </div>

            {{-- Uploaded students list + search + inline edit --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-3 bg-gray-50 border-b flex flex-wrap items-center justify-between gap-3">
                    <h3 class="font-bold text-gray-700">Uploaded students <span class="text-gray-400 font-normal">({{ $records->total() }})</span></h3>
                    <form method="GET" action="{{ route('platform.colleges.students', $college) }}" class="flex gap-2">
                        <input name="q" value="{{ $term }}" placeholder="Search name, reg no. or department…" class="border-gray-300 rounded-lg text-sm w-64">
                        <button class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold hover:bg-indigo-700">Search</button>
                        @if($term !== '')<a href="{{ route('platform.colleges.students', $college) }}" class="px-3 py-1.5 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-100">Clear</a>@endif
                    </form>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                        <tr>
                            <th class="px-6 py-2 text-left">Full Name</th>
                            <th class="px-4 py-2 text-left">Registration No.</th>
                            <th class="px-4 py-2 text-left">Department</th>
                            <th class="px-4 py-2 text-left">Level</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" x-data="{ editId: null }">
                        @forelse($records as $r)
                            @php $status = $r->status(); @endphp
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $r->full_name }}</td>
                                <td class="px-4 py-3 font-mono text-gray-600">{{ $r->registration_number }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $r->department ?: '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $r->level ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($status === 'registered')
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-green-100 text-green-700">Registered</span>
                                    @else
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Pending</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" @click="editId = (editId === {{ $r->id }} ? null : {{ $r->id }})" class="text-indigo-600 text-xs font-bold hover:underline" x-text="editId === {{ $r->id }} ? 'Cancel' : 'Edit'">Edit</button>
                                </td>
                            </tr>
                            <tr x-show="editId === {{ $r->id }}" style="display:none" class="bg-indigo-50/40">
                                <td colspan="6" class="px-6 py-4">
                                    <form method="POST" action="{{ route('platform.colleges.students.update', [$college, $r]) }}" class="flex flex-wrap gap-3 items-end">
                                        @csrf @method('PUT')
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Full Name</label>
                                            <input name="full_name" value="{{ $r->full_name }}" required class="border-gray-300 rounded-lg text-sm w-56">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Registration No.</label>
                                            <input name="registration_number" value="{{ $r->registration_number }}" required class="border-gray-300 rounded-lg text-sm w-48 font-mono">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Department</label>
                                            <input name="department" value="{{ $r->department }}" class="border-gray-300 rounded-lg text-sm w-44">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Level</label>
                                            <input name="level" value="{{ $r->level }}" class="border-gray-300 rounded-lg text-sm w-24">
                                        </div>
                                        <button class="bg-emerald-600 text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-6 text-center text-gray-400">
                                {{ $term !== '' ? 'No students match your search.' : 'No students uploaded yet — import a CSV above.' }}
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>

                @if($records->hasPages())
                    <div class="p-4 border-t">{{ $records->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
