<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Bursar Portal <span class="text-brand">· Finance &amp; Collections</span>
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('fees.orders.index') }}" class="btn-brand px-4 py-2 rounded-lg font-bold shadow-sm">
                    + New Payment Order
                </a>
                <a href="{{ route('printables.index') }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-bold hover:bg-gray-900 transition shadow-sm">
                    Printables
                </a>
                @if(\Illuminate\Support\Facades\Route::has('payroll.index'))
                    <a href="{{ route('payroll.index') }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-bold hover:bg-gray-900 transition shadow-sm">
                        Payroll
                    </a>
                @endif
                <a href="{{ route('fees.settings') }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-bold hover:bg-gray-900 transition shadow-sm">
                    Fee Settings
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Headline collection figures — SETTLED money only (pending/unpaid
                 invoices are excluded so cancelled checkouts can't inflate totals). --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border-t-4 border-brand">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Collected</p>
                    <h3 class="text-3xl font-black text-gray-900">{{ money($totalCollected) }}</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border-t-4 border-emerald-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Payments Received</p>
                    <h3 class="text-3xl font-black text-emerald-600">{{ number_format($paymentsCount) }}</h3>
                </div>
            </div>

            {{-- Per-order collection progress --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                    <h3 class="font-bold text-gray-700">Payment Orders — collection progress</h3>
                    <a href="{{ route('fees.orders.index') }}" class="text-sm font-semibold text-brand hover:underline">Manage all →</a>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Order</th>
                            <th class="px-6 py-3 text-left">Target</th>
                            <th class="px-6 py-3 text-left">Collected</th>
                            <th class="px-6 py-3 text-left">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($orders as $o)
                            @php $pct = $o->invoices_count > 0 ? (int) round($o->paid_count / $o->invoices_count * 100) : 0; @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-semibold text-gray-800">
                                    <a href="{{ route('fees.orders.show', $o) }}" class="hover:text-brand">{{ $o->title }}</a>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $o->scope_label }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800">{{ money($o->collected ?? 0) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-28 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-brand" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $o->paid_count }}/{{ $o->invoices_count }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No payment orders yet. <a href="{{ route('fees.orders.index') }}" class="text-brand font-semibold hover:underline">Create one →</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Recent settled payments (full width; no pending/outstanding panel) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h3 class="font-bold text-gray-700">Recent Payments</h3>
                </div>
                <div class="p-6 space-y-4">
                    @forelse($recentPayments as $payment)
                        <div class="flex justify-between items-center border-b pb-3 last:border-0">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ optional($payment->student)->full_name ?? $payment->payer_email ?? 'Payment' }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->description }} · {{ optional($payment->paid_at)->format('d M, Y - h:i A') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-emerald-600">+ {{ money($payment->amount) }}</p>
                                <span class="text-[10px] bg-gray-100 px-2 py-0.5 rounded uppercase font-bold">{{ $payment->payment_method ?? 'paystack' }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">No payments recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
