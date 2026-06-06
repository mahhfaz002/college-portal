<x-app-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto bg-white p-8 shadow-lg border border-gray-200" id="receipt">

            <div class="text-center border-b-2 border-black pb-4 mb-6">
                <div class="flex justify-center items-center gap-4">
                    <div class="w-20 h-20 bg-gray-200 flex items-center justify-center border">
                        <span class="text-xs text-gray-500 italic">LOGO</span>
                    </div>
                    <div class="text-left">
                        <h1 class="text-2xl font-black uppercase">{{ $school['name'] ?? 'School Portal' }}</h1>
                        <p class="text-sm italic">{{ $school['tagline'] ?? '' }}</p>
                        <p class="text-xs">{{ setting('school_address') }}</p>
                        <p class="text-xs">{{ setting('school_phone') }} | {{ setting('school_email') }}</p>
                    </div>
                </div>
            </div>

            <h2 class="text-center font-bold text-xl underline mb-6">OFFICIAL PAYMENT RECEIPT</h2>

            <div class="grid grid-cols-2 gap-4 mb-8">
                <div>
                    <p class="text-sm"><strong>STUDENT NAME:</strong> {{ $payment->student->full_name }}</p>
                    <p class="text-sm"><strong>ADMISSION NO:</strong> {{ $payment->student->admission_number }}</p>
                    <p class="text-sm"><strong>CLASS:</strong> {{ $payment->student->class_arm }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm"><strong>RECEIPT NO:</strong> #{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</p>
                    <p class="text-sm"><strong>DATE OF PAYMENT:</strong> {{ $payment->created_at->format('d/m/Y') }}</p>
                    <p class="text-sm text-gray-500 italic">Printed on: {{ now()->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <table class="w-full border-collapse border border-black mb-8">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-black p-2 text-left">Description</th>
                        <th class="border border-black p-2 text-right">Amount (₦)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-black p-4 h-32 align-top">
                            {{ $payment->description ?? 'School Fees Payment' }}
                            <p class="text-xs mt-2 italic text-gray-600">Method: {{ $payment->payment_method }}</p>
                        </td>
                        <td class="border border-black p-4 text-right font-bold text-xl">
                            {{ number_format($payment->amount, 2) }}
                        </td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="border border-black p-2 text-right font-bold uppercase">Total Paid:</td>
                        <td class="border border-black p-2 text-right font-black text-xl text-green-700">₦{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mb-8 p-3 bg-red-50 border border-red-200">
                <p class="text-sm font-bold text-red-700">REMAINING BALANCE: ₦{{ number_format($payment->student->fees_balance, 2) }}</p>
            </div>

            <div class="flex justify-between items-end mt-12">
                <div class="text-center w-40">
                    <div class="border-b border-black w-full"></div>
                    <p class="text-xs mt-1">Bursar's Signature</p>
                </div>
                <div class="text-center italic text-xs text-gray-400">
                    Thank you for your payment. Keep this receipt safe.
                </div>
                <div class="text-center w-40">
                    <div class="border-b border-black w-full"></div>
                    <p class="text-xs mt-1">Student/Parent Sign</p>
                </div>
            </div>

            <div class="mt-10 text-center no-print">
                <button onclick="window.print()" style="background-color: #1e293b; color: white; padding: 10px 30px; border-radius: 5px; font-weight: bold; cursor: pointer;">
                    🖨️ PRINT RECEIPT
                </button>
                <a href="{{ route('students.show', $payment->student->id) }}" class="block mt-4 text-blue-600 underline">Back to History</a>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #receipt, #receipt * { visibility: visible; }
            #receipt { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
            .no-print { display: none !important; }
            nav, header { display: none !important; }
        }
    </style>
</x-app-layout>
