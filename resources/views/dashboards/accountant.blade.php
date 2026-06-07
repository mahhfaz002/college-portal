<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                💰 Accountant Portal: <span class="text-green-600">Finance & Bursary</span>
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('fees.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-sm">
                    🧾 Fee Billing
                </a>
                <a href="{{ route('students.index') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 transition shadow-sm">
                    ➕ Record New Payment
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border-t-4 border-green-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Collected Revenue</p>
                    <h3 class="text-4xl font-black text-gray-900">₦{{ number_format($totalRevenue, 2) }}</h3>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border-t-4 border-red-500">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Outstanding Debt</p>
                    <h3 class="text-4xl font-black text-red-600">₦{{ number_format($totalDebt, 2) }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                        <h3 class="font-bold text-gray-700 italic underline">Top Debtors (Immediate Attention)</h3>
                    </div>
                    <table class="w-full text-left">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500">Student</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500">Class</th>
                                <th class="px-6 py-3 text-xs font-bold text-gray-500">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($debtors as $debtor)
                            <tr class="hover:bg-red-50 transition">
                                <td class="px-6 py-4 text-sm font-bold">{{ $debtor->full_name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $debtor->class_arm }}</td>
                                <td class="px-6 py-4 text-sm font-black text-red-600">₦{{ number_format($debtor->fees_balance, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b">
                        <h3 class="font-bold text-gray-700">Recent Payment Log</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @foreach($recentPayments as $payment)
                        <div class="flex justify-between items-center border-b pb-3 last:border-0">
                            <div>
                                <p class="text-sm font-bold text-gray-800">{{ $payment->student->full_name }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->created_at->format('d M, Y - h:i A') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-green-600">+ ₦{{ number_format($payment->amount, 2) }}</p>
                                <span class="text-[10px] bg-gray-100 px-2 py-0.5 rounded uppercase font-bold">{{ $payment->payment_method }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
