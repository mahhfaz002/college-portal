<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Collect Fees: {{ $student->full_name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p class="mb-4 text-red-600 font-bold">Current Balance: ₦{{ number_format($student->fees_balance, 2) }}</p>

                <form action="{{ route('payments.store', $student->id) }}" method="POST">
                    @csrf

                    @if($bills->count())
                    <div class="mb-4">
                        <label class="block font-bold">Apply to Bill <span class="text-gray-400 font-normal text-sm">(optional)</span></label>
                        <select name="fee_bill_id" id="billSelect" class="w-full border-gray-300 rounded">
                            <option value="">General payment</option>
                            @foreach($bills as $bill)
                                <option value="{{ $bill->id }}" data-balance="{{ $bill->balance }}">
                                    {{ $bill->title }} — outstanding ₦{{ number_format($bill->balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="mb-4">
                        <label class="block font-bold">Amount Paid (₦)</label>
                        <input type="number" step="0.01" name="amount" id="amountInput" class="w-full border-gray-300 rounded" required>
                    </div>

                    <div class="mb-4">
                        <label class="block font-bold">Payment Method</label>
                        <select name="payment_method" class="w-full border-gray-300 rounded">
                            <option value="Cash">Cash</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="POS">POS</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block font-bold">Description/Remark</label>
                        <input type="text" name="description" placeholder="e.g. 2nd Term Part Payment" class="w-full border-gray-300 rounded">
                    </div>

                    <button type="submit" style="background-color: #059669; color: white; padding: 10px; width: 100%; border-radius: 5px; font-weight: bold;">
                        CONFIRM PAYMENT
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('billSelect')?.addEventListener('change', function () {
            const bal = this.selectedOptions[0]?.dataset.balance;
            if (bal) document.getElementById('amountInput').value = bal;
        });
    </script>
</x-app-layout>
