<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">💰 Payroll Approvals</h2>
            <form method="GET" class="flex items-end gap-2">
                <input type="month" name="month" value="{{ $month }}" class="border-gray-300 rounded-md text-sm">
                <button class="bg-gray-700 text-white px-3 py-2 rounded-md font-bold text-sm">Load</button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            <p class="text-sm text-gray-500">You can approve a payslip or flag it back to the Bursar with a note. You cannot change the amounts — only the Bursar edits figures.</p>

            @forelse($slips as $slip)
            <div class="bg-white p-5 rounded-xl shadow-sm border {{ $slip->status==='flagged' ? 'border-red-300' : 'border-gray-200' }}">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-bold text-gray-800">{{ $slip->staff->name ?? '—' }} <span class="text-xs text-gray-400 uppercase">{{ str_replace('_',' ', $slip->staff->role ?? '') }}</span></p>
                        <p class="text-sm text-gray-600 mt-1">
                            Basic {{ money($slip->basic_salary) }} · Allow {{ money($slip->allowances) }} · Tax {{ money($slip->tax) }}
                            · Deductions {{ money($slip->totalDeductions()) }}
                        </p>
                        @if($slip->deductions)
                            <p class="text-xs text-gray-400">{{ collect($slip->deductions)->map(fn($d)=>$d['nature'].': '.money($d['amount']))->implode(', ') }}</p>
                        @endif
                        <p class="text-lg font-black text-gray-900 mt-1">Net: {{ money($slip->net_salary) }}</p>
                    </div>
                    @php $badge = ['submitted'=>'bg-blue-100 text-blue-700','flagged'=>'bg-red-100 text-red-700','approved'=>'bg-green-100 text-green-700','paid'=>'bg-emerald-100 text-emerald-700'][$slip->status] ?? 'bg-gray-100'; @endphp
                    <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $badge }}">{{ $slip->status }}</span>
                </div>

                @if($slip->flag_comment && $slip->status==='flagged')
                    <p class="mt-2 text-sm text-red-700 bg-red-50 border border-red-200 rounded p-2"><strong>Your note:</strong> {{ $slip->flag_comment }}</p>
                @endif

                @if(in_array($slip->status, ['submitted','flagged']))
                <div class="mt-3 border-t pt-3 flex flex-col sm:flex-row gap-2 sm:items-end">
                    <form method="POST" action="{{ route('payroll.approve', $slip) }}">@csrf
                        <button class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 text-sm">✓ Approve</button>
                    </form>
                    <form method="POST" action="{{ route('payroll.flag', $slip) }}" class="flex-1 flex gap-2">
                        @csrf
                        <input name="flag_comment" placeholder="Reason / what to correct…" class="flex-1 border-gray-300 rounded-md text-sm" required>
                        <button class="bg-red-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-red-700 text-sm">Flag</button>
                    </form>
                </div>
                @endif
            </div>
            @empty
            <div class="bg-white p-8 rounded-xl border text-center text-gray-400 italic">No payslips submitted for this month.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
