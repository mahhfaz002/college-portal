<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Fees &amp; Payments</h2>
            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold border">
                {{ $student->registration_number ?? $student->admission_number ?? 'N/A' }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            {{-- Fees balance --}}
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                <p class="text-xs font-bold text-gray-400 uppercase">Fees Balance (Expected to Pay)</p>
                <h3 class="text-3xl font-black text-red-600">₦{{ number_format($student->fees_balance, 2) }}</h3>
            </div>

            {{-- Invoices / payment orders --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b font-bold text-gray-700 flex items-center justify-between">
                    <span>Payment Orders &amp; Invoices</span>
                    @php $due = $invoices->where('status','pending')->count(); @endphp
                    @if($due)<span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-bold">{{ $due }} due</span>@endif
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="text-left px-6 py-2">Description</th><th class="text-left px-6 py-2">Amount</th><th class="text-left px-6 py-2">Status</th><th class="text-right px-6 py-2">Action</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($invoices as $inv)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $inv->description }}</td>
                                <td class="px-6 py-3">{{ money($inv->amount) }}</td>
                                <td class="px-6 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $inv->isPaid() ? 'bg-green-100 text-green-700' : ($inv->status === 'cancelled' ? 'bg-gray-100 text-gray-500' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($inv->status) }}</span></td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    @if($inv->isPaid())
                                        <a href="{{ route('invoices.receipt', $inv) }}" target="_blank" class="text-indigo-600 font-semibold hover:underline">Print Receipt</a>
                                    @elseif($inv->status !== 'cancelled')
                                        <a href="{{ route('payments.checkout', $inv) }}" class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-emerald-700">Pay Now</a>
                                    @else
                                        <span class="text-xs text-gray-400">Cancelled</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No payment orders assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Payment history — every settled payment (online + offline) with a receipt --}}
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 border-b font-bold text-gray-700">Payment History</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="text-left px-6 py-2">Date</th><th class="text-left px-6 py-2">Description</th><th class="text-left px-6 py-2">Method</th><th class="text-right px-6 py-2">Amount</th><th class="text-right px-6 py-2">Receipt</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($history as $h)
                            <tr>
                                <td class="px-6 py-3 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($h['date'])->format('d M, Y') }}</td>
                                <td class="px-6 py-3 text-gray-700">{{ $h['description'] }}</td>
                                <td class="px-6 py-3 uppercase text-xs font-bold text-gray-500">{{ $h['method'] }}</td>
                                <td class="px-6 py-3 text-right font-bold text-green-600">₦{{ number_format($h['amount'], 2) }}</td>
                                <td class="px-6 py-3 text-right"><a href="{{ $h['receipt_url'] }}" target="_blank" class="text-indigo-600 font-semibold hover:underline">Receipt</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
