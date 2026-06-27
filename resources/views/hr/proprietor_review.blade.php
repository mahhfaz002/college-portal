<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payroll Final Approval — Proprietor</h2>
            <form method="GET" class="flex items-end gap-2">
                <input type="month" name="month" value="{{ $month }}" class="border-gray-300 rounded-md text-sm">
                <button class="bg-gray-700 text-white px-3 py-2 rounded-md font-bold text-sm">Load</button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="bg-white rounded-xl shadow-sm border overflow-x-auto">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">
                    Payslips Awaiting Final Approval — {{ \Illuminate\Support\Carbon::parse($month.'-01')->format('F Y') }} ({{ $slips->count() }})
                </div>
                <table class="w-full text-left text-sm">
                    <thead><tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                        <th class="p-3">Staff</th><th class="p-3 text-right">Net Salary</th><th class="p-3">Status</th><th class="p-3 text-right">Action</th>
                    </tr></thead>
                    <tbody>
                        @forelse($slips as $slip)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-bold">{{ $slip->staff->name ?? '—' }}</td>
                            <td class="p-3 text-right font-bold">{{ money($slip->net_salary) }}</td>
                            <td class="p-3">
                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded
                                    {{ $slip->status === 'approved' ? 'bg-green-100 text-green-700' : ($slip->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-purple-100 text-purple-700') }}">
                                    {{ str_replace('_', ' ', $slip->status) }}
                                </span>
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('payroll.view', $slip) }}" class="text-indigo-600 text-xs font-bold hover:underline mr-1">View</a>
                                @if($slip->status === 'proprietor_review')
                                    <form method="POST" action="{{ route('payroll.proprietor.approve', $slip) }}" class="inline" onsubmit="return confirm('Give final approval? This locks the payslip permanently.')">@csrf
                                        <button class="bg-green-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-green-700">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('payroll.proprietor.query', $slip) }}" class="inline ml-1">@csrf
                                        <input name="comment" placeholder="Query reason" required class="border-gray-300 rounded text-xs py-1 w-28">
                                        <button class="text-red-600 text-xs font-bold hover:underline">Query</button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400 italic">{{ str_replace('_', ' ', $slip->status) }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-400">No payslips awaiting approval.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
