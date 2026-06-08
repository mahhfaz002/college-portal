<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏫 Classes</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Add a Class</h3>
                <form action="{{ route('classes.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Class Name</label>
                        <input name="name" placeholder="e.g. JSS1B" class="w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level <span class="text-gray-300">(opt)</span></label>
                        <input name="level" placeholder="JSS / SSS" class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm h-10">Add Class</button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">All Classes</h3></div>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                            <th class="p-3">Class</th><th class="p-3">Level</th><th class="p-3">Students</th>
                            <th class="p-3">Teachers</th><th class="p-3">Status</th><th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $c)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-bold">{{ $c->name }}</td>
                            <td class="p-3 text-gray-500">{{ $c->level ?? '—' }}</td>
                            <td class="p-3">{{ $c->student_count }}</td>
                            <td class="p-3">{{ $c->teachers_count }}</td>
                            <td class="p-3">
                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $c->active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500' }}">{{ $c->active ? 'active' : 'inactive' }}</span>
                            </td>
                            <td class="p-3 text-right">
                                <form action="{{ route('classes.toggle', $c) }}" method="POST" class="inline">@csrf
                                    <button class="text-xs font-bold {{ $c->active ? 'text-red-600' : 'text-green-700' }} hover:underline">{{ $c->active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-400 italic">No classes yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
