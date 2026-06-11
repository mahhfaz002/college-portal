<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏛️ Registrar</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([['Students',$stats['students'],'indigo'],['Paid Applications',$stats['applications'],'emerald'],['Awaiting Decision',$stats['queue'],'amber'],['Staff',$stats['staff'],'blue']] as [$l,$v,$c])
                    <div class="bg-white rounded-xl border p-5 border-t-4 border-{{ $c }}-500">
                        <p class="text-xs uppercase text-gray-400 font-bold">{{ $l }}</p>
                        <p class="text-3xl font-black text-gray-800">{{ $v }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="{{ route('students.index') }}" class="bg-white rounded-2xl shadow-sm border p-6 hover:shadow-md transition">
                    <div class="text-3xl mb-2">👨‍🎓</div><h3 class="font-bold text-gray-800">View Students</h3>
                    <p class="text-sm text-gray-500 mt-1">Read-only, filter by department &amp; course of study.</p>
                </a>
                <a href="{{ route('admission.admin') }}" class="bg-white rounded-2xl shadow-sm border p-6 hover:shadow-md transition">
                    <div class="text-3xl mb-2">📋</div><h3 class="font-bold text-gray-800">Applications</h3>
                    <p class="text-sm text-gray-500 mt-1">All submitted applications.</p>
                </a>
                <a href="{{ route('admissions.review') }}" class="bg-white rounded-2xl shadow-sm border p-6 hover:shadow-md transition">
                    <div class="text-3xl mb-2">🎟️</div><h3 class="font-bold text-gray-800">Approval Queue</h3>
                    <p class="text-sm text-gray-500 mt-1">Approve or reject applicants.</p>
                </a>
                <a href="{{ route('staff.index') }}" class="bg-white rounded-2xl shadow-sm border p-6 hover:shadow-md transition">
                    <div class="text-3xl mb-2">🧑‍💼</div><h3 class="font-bold text-gray-800">Manage Staff</h3>
                    <p class="text-sm text-gray-500 mt-1">Create, assign department &amp; role, edit, delete.</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
