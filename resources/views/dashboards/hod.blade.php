<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🧑‍🏫 {{ ucwords(str_replace('_',' ', auth()->user()->role)) }} Dashboard
            @if($department) — {{ $department->name }} @endif
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(!$department)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">
                    You have not been assigned to a department yet. Ask the Registrar to assign you one.
                </div>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Students</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $studentCount }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-amber-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Pending Approvals</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $pendingApprovals }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Courses</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $courses }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Lecturers</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $lecturers }}</h3>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm">
                <h3 class="font-bold text-gray-700 mb-2">Student Registrations Awaiting Your Approval</h3>
                <p class="text-sm text-gray-500">
                    When a student completes registration and uploads their documents, their file appears here for your
                    review and final approval. <span class="font-semibold">(Approval workflow goes live in Phase 3.)</span>
                </p>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($programs as $p)
                        <div class="border rounded-lg p-4">
                            <p class="font-semibold text-gray-800">{{ $p->name }}</p>
                            <p class="text-xs text-gray-400">{{ $p->acronym }} · {{ $p->level_system }}</p>
                        </div>
                    @empty
                        <p class="text-gray-400">No programs in this department yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
