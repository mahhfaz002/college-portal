<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daily Attendance') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <form action="{{ route('attendance.index') }}" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Select Programme</label>
                        <select name="class" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500" required>
                            <option value="">-- Choose Programme --</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ $selectedClass == $class ? 'selected' : '' }}>{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700">Date</label>
                        <input type="date" name="date" value="{{ $date }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500">
                    </div>

                    <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-md font-bold hover:bg-gray-700 transition">
                        Load Student List
                    </button>
                </form>
            </div>

           @if($selectedClass)
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <div class="flex justify-between items-center mb-2">
            <h3 class="font-black text-lg text-blue-800 uppercase">
                Attendance for {{ $selectedClass }} ({{ \Carbon\Carbon::parse($date)->format('D, d M Y') }})
            </h3>
        </div>

        <div class="flex gap-4 mb-6 text-xs font-bold uppercase tracking-wider">
            <span class="flex items-center text-green-600">
                <span class="h-2 w-2 bg-green-600 rounded-full mr-1"></span> Present
            </span>
            <span class="flex items-center text-red-600">
                <span class="h-2 w-2 bg-red-600 rounded-full mr-1"></span> Absent
            </span>
            <span class="flex items-center text-orange-500">
                <span class="h-2 w-2 bg-orange-500 rounded-full mr-1"></span> Late
            </span>
        </div>

        <form action="{{ route('attendance.store') }}" method="POST">
                    <form action="{{ route('attendance.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="date" value="{{ $date }}">

                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100 text-left">
                                    <th class="p-4 border-b">Student Name</th>
                                    <th class="p-4 border-b text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    @php
                                        // Check if attendance was already marked for today
                                        $currentStatus = $student->attendances->first()->status ?? 'present';
                                    @endphp
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="p-4 font-medium">{{ $student->full_name }}</td>
                                        <td class="p-4">
                                            <div class="flex justify-center gap-6">
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" name="status[{{ $student->id }}]" value="present"
                                                        class="text-green-600 focus:ring-green-500" {{ $currentStatus == 'present' ? 'checked' : '' }}>
                                                    <span class="ml-2 text-sm text-green-700 font-bold">Present</span>
                                                </label>

                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" name="status[{{ $student->id }}]" value="absent"
                                                        class="text-red-600 focus:ring-red-500" {{ $currentStatus == 'absent' ? 'checked' : '' }}>
                                                    <span class="ml-2 text-sm text-red-700 font-bold">Absent</span>
                                                </label>

                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="radio" name="status[{{ $student->id }}]" value="late"
                                                        class="text-orange-500 focus:ring-orange-400" {{ $currentStatus == 'late' ? 'checked' : '' }}>
                                                    <span class="ml-2 text-sm text-orange-600 font-bold">Late</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="p-8 text-center text-gray-500">No students found in this class.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if(count($students) > 0)
                            <div class="mt-8 flex justify-end">
                                <button type="submit" class="bg-blue-600 text-white px-10 py-3 rounded-lg font-black uppercase tracking-widest shadow-lg hover:bg-blue-700 transition">
                                    💾 Save Attendance
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            @else
                <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded shadow">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700 font-bold">
                                Please select a class and date above to begin marking attendance.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
