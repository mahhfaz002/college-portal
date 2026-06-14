<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payslip — {{ $user->name }} ({{ \Illuminate\Support\Carbon::parse($month.'-01')->format('F Y') }})</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())<div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            @if($slip->status === 'flagged')
            <div class="mb-4 p-4 bg-red-50 border border-red-300 text-red-800 rounded-lg text-sm">
                <strong>Flagged by Management:</strong> {{ $slip->flag_comment }}
            </div>
            @endif

            <form action="{{ route('payroll.store', $user) }}" method="POST" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-5" x-data="{ rows: {{ count($slip->deductions ?? []) ?: 1 }} }">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Basic Salary</label>
                        <input type="number" step="0.01" name="basic_salary" value="{{ old('basic_salary', $slip->basic_salary) }}" class="w-full border-gray-300 rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Allowances</label>
                        <input type="number" step="0.01" name="allowances" value="{{ old('allowances', $slip->allowances) }}" class="w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tax</label>
                        <input type="number" step="0.01" name="tax" value="{{ old('tax', $slip->tax) }}" class="w-full border-gray-300 rounded-md">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Deductions (nature + amount)</label>
                    <div class="space-y-2">
                        @php $deductions = old('deduction_nature') ? array_map(null, old('deduction_nature'), old('deduction_amount')) : ($slip->deductions ?? []); @endphp
                        @forelse($deductions as $d)
                            <div class="flex gap-2">
                                <input name="deduction_nature[]" value="{{ is_array($d) ? ($d['nature'] ?? $d[0] ?? '') : '' }}" placeholder="e.g. Pension, Loan" class="flex-1 border-gray-300 rounded-md text-sm">
                                <input name="deduction_amount[]" type="number" step="0.01" value="{{ is_array($d) ? ($d['amount'] ?? $d[1] ?? '') : '' }}" placeholder="Amount" class="w-32 border-gray-300 rounded-md text-sm">
                            </div>
                        @empty
                            <div class="flex gap-2">
                                <input name="deduction_nature[]" placeholder="e.g. Pension, Loan" class="flex-1 border-gray-300 rounded-md text-sm">
                                <input name="deduction_amount[]" type="number" step="0.01" placeholder="Amount" class="w-32 border-gray-300 rounded-md text-sm">
                            </div>
                        @endforelse
                        <div id="moreDeductions"></div>
                    </div>
                    <button type="button" onclick="addDeduction()" class="mt-2 text-xs font-bold text-indigo-600">+ Add deduction</button>
                </div>

                <p class="text-xs text-gray-400">Net salary = Basic + Allowances − Deductions − Tax (calculated on save).</p>

                <div class="flex justify-between items-center pt-3 border-t">
                    <a href="{{ route('payroll.index', ['month' => $month]) }}" class="text-gray-500 font-bold text-sm">← Cancel</a>
                    <button class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">Save Payslip</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addDeduction() {
            const wrap = document.getElementById('moreDeductions');
            const div = document.createElement('div');
            div.className = 'flex gap-2 mt-2';
            div.innerHTML = '<input name="deduction_nature[]" placeholder="e.g. Pension, Loan" class="flex-1 border-gray-300 rounded-md text-sm">' +
                            '<input name="deduction_amount[]" type="number" step="0.01" placeholder="Amount" class="w-32 border-gray-300 rounded-md text-sm">';
            wrap.appendChild(div);
        }
    </script>
</x-app-layout>
