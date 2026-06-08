<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗓️ Timetable</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            @if($canManage)
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Generate Weekly Timetable (AI)</h3>
                <p class="text-sm text-gray-500 mb-4">Uses your classes, subjects and teacher assignments. The AI proposes a layout and the system guarantees no teacher is double-booked. Mon–Fri, recurring weekly.</p>
                <form action="{{ route('timetable.generate') }}" method="POST" class="grid grid-cols-2 sm:grid-cols-5 gap-3 items-end">
                    @csrf
                    <div><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Periods/day</label><input type="number" name="periods" value="{{ $params['periods'] }}" class="w-full border-gray-300 rounded-md text-sm"></div>
                    <div><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Minutes</label><input type="number" name="period_minutes" value="{{ $params['period_minutes'] }}" class="w-full border-gray-300 rounded-md text-sm"></div>
                    <div><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Start</label><input type="time" name="start_time" value="{{ $params['start_time'] }}" class="w-full border-gray-300 rounded-md text-sm"></div>
                    <div><label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Break after</label><input type="number" name="break_after" value="{{ $params['break_after'] }}" class="w-full border-gray-300 rounded-md text-sm"></div>
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm h-10">⚡ Generate</button>
                </form>
            </div>
            @endif

            @if($draft)
                @php $byClass = $draft->entries->groupBy('class_arm'); @endphp
                <div class="bg-white p-6 rounded-xl shadow-sm border border-amber-300">
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h3 class="font-bold text-amber-800">Draft Timetable
                            <span class="ml-1 text-[10px] uppercase font-bold px-2 py-0.5 rounded {{ $draft->engine==='ai' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600' }}">{{ $draft->engine === 'ai' ? 'AI-generated' : 'auto-generated' }}</span>
                        </h3>
                        @if($canManage)
                        <div class="flex gap-2">
                            <form action="{{ route('timetable.approve', $draft) }}" method="POST" onsubmit="return confirm('Approve and publish this timetable to all teachers and students?')">@csrf
                                <button class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 text-sm">✓ Approve &amp; Publish</button>
                            </form>
                            <form action="{{ route('timetable.destroy', $draft) }}" method="POST" onsubmit="return confirm('Discard this draft?')">@csrf @method('DELETE')
                                <button class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-bold hover:bg-gray-300 text-sm">Discard</button>
                            </form>
                        </div>
                        @endif
                    </div>

                    @forelse($byClass as $class => $entries)
                        @php $map = $entries->keyBy(fn($e) => $e->day.'-'.$e->period_no); @endphp
                        <div class="mb-6">
                            <h4 class="font-bold text-gray-700 mb-2">{{ $class }}</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border">
                                    <thead><tr class="bg-gray-50">
                                        <th class="p-2 border">Period</th>
                                        @foreach($params['days'] as $day)<th class="p-2 border">{{ \Illuminate\Support\Str::substr($day,0,3) }}</th>@endforeach
                                    </tr></thead>
                                    <tbody>
                                        @foreach($rows as $row)
                                        <tr>
                                            <td class="p-2 border font-bold text-gray-500">{{ $row['no'] }}<br><span class="font-normal">{{ $row['start'] }}</span></td>
                                            @foreach($params['days'] as $day)
                                                @php $e = $map->get($day.'-'.$row['no']); @endphp
                                                <td class="p-2 border">
                                                    @if($e)<span class="font-bold text-gray-800">{{ $e->subject->name ?? '' }}</span><br><span class="text-gray-400">{{ $e->teacher->name ?? '' }}</span>@else<span class="text-gray-300">—</span>@endif
                                                </td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No entries — assign teachers to classes &amp; subjects first, then regenerate.</p>
                    @endforelse
                </div>
            @endif

            @if($approved)
                <div class="bg-white p-4 rounded-xl shadow-sm border border-green-300 text-sm text-green-800">
                    ✅ A timetable is currently <strong>published</strong> (approved {{ $approved->approved_at?->diffForHumans() }}). Teachers and students see it on their dashboards. Generating &amp; approving a new one replaces it.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
