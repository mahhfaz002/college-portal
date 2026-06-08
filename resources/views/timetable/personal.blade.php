<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗓️ {{ $title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if(!$plan || $entries->isEmpty())
                <div class="bg-white p-8 rounded-xl border text-center text-gray-400 italic">
                    No published timetable yet. Please check back after the school publishes it.
                </div>
            @else
                @php $map = $entries->keyBy(fn($e) => $e->day.'-'.$e->period_no); @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto p-4">
                    <table class="w-full text-left text-sm border">
                        <thead><tr class="bg-gray-50">
                            <th class="p-2 border">Period</th>
                            @foreach($params['days'] as $day)<th class="p-2 border">{{ $day }}</th>@endforeach
                        </tr></thead>
                        <tbody>
                            @foreach($rows as $row)
                            <tr>
                                <td class="p-2 border font-bold text-gray-500">{{ $row['no'] }}<br><span class="font-normal text-xs">{{ $row['start'] }}–{{ $row['end'] }}</span></td>
                                @foreach($params['days'] as $day)
                                    @php $e = $map->get($day.'-'.$row['no']); @endphp
                                    <td class="p-2 border align-top">
                                        @if($e)
                                            <span class="font-bold text-gray-800">{{ $e->subject->name ?? '' }}</span>
                                            @if($showClass)<br><span class="text-xs text-indigo-600">{{ $e->class_arm }}</span>@else<br><span class="text-xs text-gray-400">{{ $e->teacher->name ?? '' }}</span>@endif
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-3">This timetable recurs every week.</p>
            @endif
        </div>
    </div>
</x-app-layout>
