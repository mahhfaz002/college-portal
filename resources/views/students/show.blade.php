<x-app-layout>
    <style>
    @media print {
        /* Hide everything except the statement area */
        nav, .py-12 > div > div:first-child, .mt-6, button, header {
            display: none !important;
        }
        .py-12 {
            padding: 0 !important;
        }
        .shadow, .rounded-lg {
            box-shadow: none !important;
            border: none !important;
        }
        body {
            background-color: white !important;
        }
    }
    </style>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Statement of Account: {{ $student->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow sm:rounded-lg mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-600">Admission No: <strong>{{ $student->admission_number }}</strong></p>
                        <p class="text-gray-600">Class: <strong>{{ $student->class_arm }}</strong></p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-red-600">Outstanding Balance</p>
                        <p class="text-3xl font-black text-red-600">₦{{ number_format($student->fees_balance, 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-bold text-lg mb-4 text-gray-700 border-b pb-2">Payment History</h3>
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-left text-sm uppercase text-gray-600">
                            <th class="p-3 border-b">Date</th>
                            <th class="p-3 border-b">Method</th>
                            <th class="p-3 border-b">Description</th>
                            <th class="p-3 border-b text-right">Amount (₦)</th>
                            <th class="p-3 border-b text-center">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3">{{ \Illuminate\Support\Carbon::parse($payment['date'])->format('d M, Y') }}</td>
                                <td class="p-3 uppercase text-xs font-bold text-gray-500">{{ $payment['method'] }}</td>
                                <td class="p-3 text-gray-500 italic text-sm">{{ $payment['description'] }}</td>
                                <td class="p-3 text-right font-bold text-green-600">₦{{ number_format($payment['amount'], 2) }}</td>
                                <td class="p-3 text-center">
                                    <a href="{{ $payment['receipt'] }}" target="_blank"
                                       style="background-color: #f3f4f6; border: 1px solid #d1d5db; padding: 4px 12px; border-radius: 4px; text-decoration: none; font-size: 11px; color: black; font-weight: bold;">
                                        View Receipt
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-gray-400">No payment records found for this student.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($payments->isNotEmpty())
                    <tfoot>
                        <tr class="bg-gray-50">
                            <td colspan="3" class="p-3 font-bold text-gray-700">Total Paid</td>
                            <td class="p-3 text-right font-black text-green-700">₦{{ number_format($totalPaid, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>

                <div class="mt-8 flex justify-between items-center border-t pt-6">
                    <a href="{{ route('students.index') }}" class="text-blue-600 font-bold underline">← Back to Student List</a>

                    {{-- Oversight roles (proprietor/provost) cannot view academic reports. --}}
                    @unless(auth()->user()->hasRole('proprietor', 'provost'))
                    <a href="{{ route('students.report', $student->id) }}"
                       style="background-color: #4338ca; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; transition: background 0.2s;">
                        📜 View End-of-Term Report
                    </a>
                    @endunless
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
