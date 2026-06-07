<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗂️ Registrar / Admin Dashboard</h2>
            <a href="{{ route('admission.admin') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Review Admissions</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="{{ route('admission.admin') }}" class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-yellow-500 hover:shadow-md transition">
                    <p class="text-xs font-bold text-gray-400 uppercase">Pending Applications</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $pending }}</h3>
                </a>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Admitted</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $admitted }}</h3>
                </div>
                <a href="{{ route('students.index') }}" class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500 hover:shadow-md transition">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Students</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $totalStudents }}</h3>
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">Recent Applications</h3>
                    <a href="{{ route('admission.admin') }}" class="text-xs font-bold text-indigo-600">Review all →</a>
                </div>
                <table class="w-full text-left text-sm">
                    <tbody>
                        @forelse($recentApplicants as $a)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-bold">{{ $a->full_name }}</td>
                            <td class="p-3 text-gray-500">{{ $a->desired_class }}</td>
                            <td class="p-3">
                                @php $b = ['pending'=>'bg-yellow-100 text-yellow-700','admitted'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'][$a->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $b }}">{{ $a->status }}</span>
                            </td>
                            <td class="p-3 text-right text-xs font-mono text-green-700">{{ $a->admission_number }}</td>
                        </tr>
                        @empty
                        <tr><td class="p-6 text-center text-gray-400 italic">No applications yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="{{ route('admission.admin') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-indigo-50 text-center font-bold text-indigo-700">Admissions</a>
                    <a href="{{ route('students.index') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-indigo-50 text-center font-bold text-indigo-700">Students</a>
                    <a href="{{ route('students.create') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-indigo-50 text-center font-bold text-indigo-700">Admit Manually</a>
                    <a href="{{ route('students.promotion') }}" class="p-4 bg-gray-50 rounded-lg hover:bg-indigo-50 text-center font-bold text-indigo-700">Promote Class</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
