<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">💳 {{ $order->title }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('fees.orders.index') }}" class="text-sm text-indigo-600 hover:underline">← All payment orders</a>

            <div class="grid sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Amount</p><p class="font-bold text-gray-800">{{ money($order->amount) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Target</p><p class="font-semibold text-gray-700 text-sm">{{ $order->scope_label }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Students</p><p class="font-bold text-gray-800">{{ $order->students_count }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Collected</p><p class="font-bold text-emerald-600">{{ money($order->collected()) }}</p></div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Students</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2 text-left">Student</th><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2 text-left">Paid At</th><th class="px-4 py-2 text-right">Receipt</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($invoices as $inv)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $inv->student->full_name ?? $inv->payer_email }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $inv->isPaid() ? 'bg-green-100 text-green-700':'bg-amber-100 text-amber-700' }}">{{ ucfirst($inv->status) }}</span></td>
                                <td class="px-4 py-3 text-gray-500">{{ optional($inv->paid_at)->format('d M Y, g:ia') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if($inv->isPaid())
                                        <a href="{{ route('invoices.receipt', $inv) }}" target="_blank" class="text-indigo-600 font-semibold hover:underline">Print</a>
                                    @else <span class="text-gray-300">—</span> @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No invoices.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
