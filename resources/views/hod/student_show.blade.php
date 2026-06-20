<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎓 {{ $student->full_name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('hod.students') }}" class="text-sm text-indigo-600 hover:underline">← Department students</a>

            <div class="bg-white rounded-2xl shadow-sm border p-6 flex flex-col sm:flex-row gap-6">
                @if($student->photo)
                    <img src="{{ $student->photo }}" alt="passport" class="w-28 h-28 rounded-xl object-cover border">
                @endif
                <div class="grid sm:grid-cols-2 gap-x-8 gap-y-2 text-sm flex-1">
                    <div><span class="text-gray-400">Reg No:</span> <span class="font-semibold">{{ $student->registration_number ?? $student->admission_number }}</span></div>
                    <div><span class="text-gray-400">Email:</span> <span class="font-semibold">{{ $student->email }}</span></div>
                    <div><span class="text-gray-400">Course of Study:</span> <span class="font-semibold">{{ $student->program->name ?? '—' }}</span></div>
                    <div><span class="text-gray-400">Level:</span> <span class="font-semibold">{{ $student->level }}</span></div>
                    <div><span class="text-gray-400">Phone:</span> <span class="font-semibold">{{ $student->parent_phone }}</span></div>
                    <div><span class="text-gray-400">Registration:</span> <span class="font-semibold">{{ ucfirst(str_replace('_',' ',$student->registration_status ?? '—')) }}</span></div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Uploaded Documents</div>
                <div class="p-6 flex flex-wrap gap-2">
                    @forelse($documents as $d)
                        <a href="{{ route('documents.show', $d) }}" target="_blank" class="text-xs bg-indigo-50 text-indigo-700 px-3 py-2 rounded-lg hover:bg-indigo-100 font-semibold">
                            📎 {{ $d->label ?? $d->type }}
                        </a>
                    @empty
                        <p class="text-sm text-gray-400">No documents uploaded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
