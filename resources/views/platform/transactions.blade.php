<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">💳 Transactions — {{ $college->name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            <a href="{{ route('platform.colleges.show', $college) }}" class="text-sm text-indigo-600 hover:underline">← {{ $college->name }}</a>

            {{-- Reconciliation summary --}}
            <div class="grid sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Paid Transactions</p><p class="text-2xl font-black text-gray-800">{{ number_format($summary['count']) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Gross Collected</p><p class="text-2xl font-black text-gray-800">{{ money($summary['gross']) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Platform Commission</p><p class="text-2xl font-black text-emerald-600">{{ money($summary['commission']) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Institution Share</p><p class="text-2xl font-black text-indigo-600">{{ money($summary['share']) }}</p></div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700 flex justify-between items-center">
                    <span>Transactions</span>
                    <span class="text-xs font-normal text-gray-500">{{ $summary['settled'] }} settled</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-2 text-left">Reference</th>
                                <th class="px-4 py-2 text-left">Payer</th>
                                <th class="px-4 py-2 text-left">Purpose</th>
                                <th class="px-4 py-2 text-right">Amount</th>
                                <th class="px-4 py-2 text-right">Commission</th>
                                <th class="px-4 py-2 text-right">Share</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Settlement</th>
                                <th class="px-4 py-2 text-left">Paid</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($invoices as $inv)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $inv->reference }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ optional($inv->student)->full_name ?? $inv->payer_email ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ ucwords(str_replace('_',' ', $inv->purpose)) }}</td>
                                    <td class="px-4 py-2 text-right font-bold">{{ money($inv->amount) }}</td>
                                    <td class="px-4 py-2 text-right text-emerald-600">{{ $inv->platform_commission !== null ? money($inv->platform_commission) : '—' }}</td>
                                    <td class="px-4 py-2 text-right text-indigo-600">{{ $inv->institution_share !== null ? money($inv->institution_share) : '—' }}</td>
                                    <td class="px-4 py-2">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $inv->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ ucfirst($inv->status) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-xs">
                                        @php $ss = $inv->settlement_status; @endphp
                                        <span class="{{ $ss === 'settled' ? 'text-green-600' : ($ss === 'failed' ? 'text-red-600' : 'text-gray-400') }} font-semibold">{{ $ss ? ucfirst($ss) : '—' }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-500">{{ optional($inv->paid_at)->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No transactions for this college yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $invoices->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
