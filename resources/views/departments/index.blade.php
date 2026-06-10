<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏛️ Departments</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-gray-700 mb-4">Create Department</h3>
                <form method="POST" action="{{ route('departments.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @csrf
                    <input name="name" required placeholder="Department name" class="border-gray-300 rounded-lg md:col-span-2">
                    <input name="acronym" placeholder="Acronym (e.g. SLT)" class="border-gray-300 rounded-lg">
                    <button class="bg-indigo-600 text-white rounded-lg font-bold px-4 py-2 hover:bg-indigo-700">+ Add</button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="text-left px-6 py-3">Department</th>
                            <th class="text-left px-6 py-3">Acronym</th>
                            <th class="text-left px-6 py-3">Programs</th>
                            <th class="text-right px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($departments as $dept)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $dept->name }}</td>
                                <td class="px-6 py-3">{{ $dept->acronym }}</td>
                                <td class="px-6 py-3">{{ $dept->programs_count }}</td>
                                <td class="px-6 py-3 text-right">
                                    <form method="POST" action="{{ route('departments.destroy', $dept) }}" onsubmit="return confirm('Delete this department and its programs?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No departments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('programs.index') }}" class="inline-block text-indigo-600 font-semibold hover:underline">Manage programs →</a>
        </div>
    </div>
</x-app-layout>
