<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payslip (Read-only) — {{ $payslip->staff->name ?? '' }}</h2>
            <a href="{{ $back }}" class="text-sm text-gray-500 font-bold">← Back to Review</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @php
                $college = \App\Models\College::withoutGlobalScopes()->find($payslip->college_id);
                $cur = setting('currency_symbol','₦');
            @endphp

            <div class="mb-4 p-3 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-xs font-bold">
                View only — these figures were entered by the Bursar. Use the Query or Approve actions on the review page.
            </div>

            <div class="bg-white p-8 shadow-lg border">
                <div class="text-center border-b-2 border-black pb-4 mb-6">
                    <h1 class="text-2xl font-black uppercase">{{ $college->name ?? setting('school_name','College') }}</h1>
                    <p class="text-xs">{{ $college->address ?? '' }}</p>
                </div>
                <h2 class="text-center font-bold text-lg underline mb-6">STAFF PAYSLIP — {{ \Illuminate\Support\Carbon::parse($payslip->month.'-01')->format('F Y') }}</h2>

                <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                    <div>
                        <p><strong>Staff:</strong> {{ $payslip->staff->name }}</p>
                        <p><strong>Role:</strong> {{ ucwords(str_replace('_',' ', $payslip->staff->role ?? '')) }}</p>
                        <p><strong>Staff ID:</strong> {{ $payslip->staff->staff_id ?? '—' }}</p>
                    </div>
                    <div class="text-right">
                        <p><strong>Status:</strong> {{ strtoupper(str_replace('_',' ', $payslip->status)) }}</p>
                    </div>
                </div>

                <table class="w-full border-collapse border border-black text-sm mb-4">
                    <tr class="bg-gray-100"><th class="border border-black p-2 text-left">Earnings</th><th class="border border-black p-2 text-right">Amount</th></tr>
                    <tr><td class="border border-black p-2">Basic Salary</td><td class="border border-black p-2 text-right">{{ $cur }}{{ number_format($payslip->basic_salary,2) }}</td></tr>
                    <tr><td class="border border-black p-2">Allowances</td><td class="border border-black p-2 text-right">{{ $cur }}{{ number_format($payslip->allowances,2) }}</td></tr>
                    <tr class="bg-gray-50"><td class="border border-black p-2 font-bold">Gross</td><td class="border border-black p-2 text-right font-bold">{{ $cur }}{{ number_format($payslip->gross(),2) }}</td></tr>
                </table>

                <table class="w-full border-collapse border border-black text-sm mb-4">
                    <tr class="bg-gray-100"><th class="border border-black p-2 text-left">Deductions</th><th class="border border-black p-2 text-right">Amount</th></tr>
                    @forelse($payslip->deductions ?? [] as $d)
                        <tr><td class="border border-black p-2">{{ $d['nature'] ?? '' }}</td><td class="border border-black p-2 text-right">{{ $cur }}{{ number_format($d['amount'] ?? 0,2) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="border border-black p-2 text-center text-gray-400">No itemised deductions</td></tr>
                    @endforelse
                    <tr><td class="border border-black p-2">Tax ({{ rtrim(rtrim(number_format($payslip->tax,2),'0'),'.') }}%)</td><td class="border border-black p-2 text-right">{{ $cur }}{{ number_format($payslip->taxAmount(),2) }}</td></tr>
                    <tr><td class="border border-black p-2">Contributory Savings ({{ rtrim(rtrim(number_format($payslip->contributory_savings,2),'0'),'.') }}%)</td><td class="border border-black p-2 text-right">{{ $cur }}{{ number_format($payslip->savingsAmount(),2) }}</td></tr>
                    <tr class="bg-gray-50"><td class="border border-black p-2 font-bold">Total Deductions</td><td class="border border-black p-2 text-right font-bold">{{ $cur }}{{ number_format($payslip->totalDeductions() + $payslip->taxAmount() + $payslip->savingsAmount(),2) }}</td></tr>
                </table>

                <div class="flex justify-end">
                    <div class="text-right">
                        <p class="text-xs text-gray-500 uppercase">Net Salary</p>
                        <p class="text-2xl font-black text-green-700">{{ $cur }}{{ number_format($payslip->net_salary,2) }}</p>
                    </div>
                </div>

                @if($payslip->provost_comment || $payslip->proprietor_comment)
                <div class="mt-6 pt-4 border-t space-y-2 text-sm">
                    @if($payslip->provost_comment)<p><strong class="text-amber-700">Provost query:</strong> {{ $payslip->provost_comment }}</p>@endif
                    @if($payslip->proprietor_comment)<p><strong class="text-red-700">Proprietor query:</strong> {{ $payslip->proprietor_comment }}</p>@endif
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
