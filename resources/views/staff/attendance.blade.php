<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🕒 Teacher Attendance — Classroom Activity</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <form method="GET" class="mb-6 flex items-end gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Date</label>
                    <input type="date" name="date" value="{{ $date }}" class="border-gray-300 rounded-md shadow-sm text-sm">
                </div>
                <button class="bg-gray-700 text-white px-4 py-2 rounded-md font-bold text-sm">View</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                                <th class="p-3 font-bold">Teacher</th>
                                <th class="p-3 font-bold">Status</th>
                                <th class="p-3 font-bold">Clock In</th>
                                <th class="p-3 font-bold">Classes Attended</th>
                                <th class="p-3 font-bold">Classes Missed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-800 text-sm">{{ $row['teacher']->name }}</td>
                                <td class="p-3">
                                    @if($row['is_active'])
                                        <span class="text-[10px] font-bold text-green-700 bg-green-100 px-2 py-1 rounded-full">● ACTIVE</span>
                                    @else
                                        <span class="text-[10px] font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded-full">○ NOT ACTIVE</span>
                                    @endif
                                </td>
                                <td class="p-3 text-sm text-gray-600">{{ $row['clock_in'] ? \Illuminate\Support\Carbon::parse($row['clock_in'])->format('h:i A') : '—' }}</td>
                                <td class="p-3 text-sm">
                                    @forelse($row['attended'] as $c)
                                        <span class="inline-block text-[11px] bg-green-50 text-green-700 px-2 py-0.5 rounded mr-1 mb-1">{{ $c }}</span>
                                    @empty
                                        <span class="text-gray-400 text-xs">none</span>
                                    @endforelse
                                </td>
                                <td class="p-3 text-sm">
                                    @forelse($row['missed'] as $c)
                                        <span class="inline-block text-[11px] bg-red-50 text-red-700 px-2 py-0.5 rounded mr-1 mb-1">{{ $c }}</span>
                                    @empty
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endforelse
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="p-8 text-center text-gray-400 italic">No teachers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
