<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Monthly Attendance Summary</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase">Programme</label>
                        <select name="class" class="mt-1 border-gray-300 rounded-md shadow-sm">
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ $selectedClass == $class ? 'selected' : '' }}>{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase">Month</label>
                        <select name="month" class="mt-1 border-gray-300 rounded-md shadow-sm">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">Generate Report</button>
                </form>
            </div>

            @if($selectedClass)
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4 font-bold text-gray-700">Student Name</th>
                            <th class="p-4 font-bold text-gray-700 text-center">Days Present</th>
                            <th class="p-4 font-bold text-gray-700 text-center">School Days</th>
                            <th class="p-4 font-bold text-gray-700 text-center">Attendance %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData as $data)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-4 font-medium">{{ $data['name'] }}</td>
                            <td class="p-4 text-center">{{ $data['present'] }}</td>
                            <td class="p-4 text-center">{{ $data['total'] }}</td>
                            <td class="p-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $data['percentage'] < 70 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $data['percentage'] }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
