<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">💼 HR / Payroll</h2>
            <form method="GET" class="flex items-end gap-2">
                <input type="month" name="month" value="{{ $month }}" class="border-gray-300 rounded-md text-sm">
                <button class="bg-gray-700 text-white px-3 py-2 rounded-md font-bold text-sm">Load</button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6"
             x-data="{ fSection: '', fDept: '' }">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            @if($counts['flagged'] > 0)
            <div class="p-4 bg-red-50 border border-red-300 text-red-800 rounded-lg text-sm font-bold">
                ⚠️ {{ $counts['flagged'] }} payslip(s) were flagged by the Principal — edit and resubmit them below.
            </div>
            @endif

            <div class="flex flex-wrap gap-3 items-center justify-between bg-white p-4 rounded-xl border">
                <div class="text-sm text-gray-600">
                    Month <strong>{{ \Illuminate\Support\Carbon::parse($month.'-01')->format('F Y') }}</strong> —
                    draft {{ $counts['draft'] }} · submitted {{ $counts['submitted'] }} · flagged {{ $counts['flagged'] }} · approved {{ $counts['approved'] }} · paid {{ $counts['paid'] }}
                </div>
                @if($counts['draft'] + $counts['flagged'] > 0)
                <form method="POST" action="{{ route('payroll.submit') }}" onsubmit="return confirm('Submit all draft/flagged payslips for this month to the Principal?')">
                    @csrf <input type="hidden" name="month" value="{{ $month }}">
                    <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Submit to Principal</button>
                </form>
                @endif
            </div>

            {{-- Filter by section / department (Others = staff with no department) --}}
            <div class="bg-white p-4 rounded-xl border flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Section</label>
                    <select x-model="fSection" @change="fDept=''" class="border-gray-300 rounded-md text-sm">
                        <option value="">All sections</option>
                        @foreach($sections as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 uppercase mb-1">Department</label>
                    <select x-model="fDept" class="border-gray-300 rounded-md text-sm">
                        <option value="">All departments</option>
                        @foreach($departments as $d)<option value="{{ $d->name }}" x-show="!fSection || fSection==='{{ $d->section }}'">{{ $d->name }}</option>@endforeach
                        <option value="Others" x-show="!fSection || fSection==='Others'">Others (no department)</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                            <th class="p-3">Staff</th><th class="p-3">Role</th><th class="p-3">Department</th><th class="p-3 text-right">Net Salary</th>
                            <th class="p-3">Status</th><th class="p-3">Principal's Note</th><th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $r)
                        @php $slip = $r['slip']; $status = $slip->status ?? 'none'; @endphp
                        <tr class="border-b hover:bg-gray-50"
                            x-show="(!fSection || fSection==='{{ $r['section'] }}') && (!fDept || fDept==='{{ $r['dept'] ?? 'Others' }}')">
                            <td class="p-3 font-bold">{{ $r['staff']->name }}</td>
                            <td class="p-3 text-gray-500 uppercase text-[11px]">{{ str_replace('_',' ',$r['staff']->role) }}</td>
                            <td class="p-3 text-gray-500 text-xs">{{ $r['dept'] ?? 'Others' }} <span class="text-gray-300">· {{ $r['section'] }}</span></td>
                            <td class="p-3 text-right font-bold">{{ $slip ? money($slip->net_salary) : '—' }}</td>
                            <td class="p-3">
                                @php $badge = ['none'=>'bg-gray-100 text-gray-500','draft'=>'bg-gray-100 text-gray-600','submitted'=>'bg-blue-100 text-blue-700','flagged'=>'bg-red-100 text-red-700','approved'=>'bg-green-100 text-green-700','paid'=>'bg-emerald-100 text-emerald-700'][$status]; @endphp
                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $badge }}">{{ $status === 'none' ? 'not set' : $status }}</span>
                            </td>
                            <td class="p-3 text-xs text-red-600">{{ $slip->flag_comment ?? '' }}</td>
                            <td class="p-3 text-right">
                                @if(in_array($status, ['none','draft','flagged']))
                                    <a href="{{ route('payroll.edit', [$r['staff'], 'month' => $month]) }}" class="bg-gray-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-gray-700">{{ $status==='none' ? 'Create' : 'Edit' }}</a>
                                @elseif($status === 'approved')
                                    <form method="POST" action="{{ route('payroll.pay', $slip) }}" class="inline" onsubmit="return confirm('Initiate salary payment to {{ $r['staff']->name }}?')">@csrf
                                        <button class="bg-emerald-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-emerald-700">Pay</button>
                                    </form>
                                @elseif($status === 'paid')
                                    <a href="{{ route('payroll.slip', $slip) }}" class="text-xs bg-emerald-600 text-white px-3 py-1.5 rounded font-bold hover:bg-emerald-700">✓ Paid · Payslip</a>
                                @else
                                    <span class="text-xs text-gray-400 italic">awaiting principal</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
