<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎓 Applicant Dashboard</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif

            @if(!$applicant)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">
                    No application record is linked to this account.
                </div>
            @else
            {{-- Admission status --}}
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">{{ $applicant->full_name }}</h3>
                        <p class="text-sm text-gray-500">
                            1st choice: <span class="font-semibold">{{ $applicant->firstChoice->name ?? '—' }}</span>
                            @if($applicant->secondChoice) · 2nd choice: {{ $applicant->secondChoice->name }} @endif
                        </p>
                    </div>
                    @php
                        $status = $applicant->application_status;
                        $badge = match($status) {
                            'submitted' => ['Under Review', 'bg-blue-100 text-blue-700'],
                            'admitted'  => ['Admitted', 'bg-green-100 text-green-700'],
                            'rejected'  => ['Not Successful', 'bg-red-100 text-red-700'],
                            default     => ['Awaiting Payment', 'bg-amber-100 text-amber-700'],
                        };
                    @endphp
                    <span class="px-3 py-1 rounded-full text-sm font-bold {{ $badge[1] }}">{{ $badge[0] }}</span>
                </div>

                <div class="mt-5 grid sm:grid-cols-3 gap-4 text-sm">
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="text-gray-400 uppercase text-xs font-bold">Application Fee</p>
                        <p class="font-semibold {{ $applicant->payment_status==='paid' ? 'text-green-600':'text-amber-600' }}">
                            {{ ucfirst($applicant->payment_status) }}
                        </p>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="text-gray-400 uppercase text-xs font-bold">Admission Decision</p>
                        <p class="font-semibold text-gray-700">
                            @if($applicant->application_status==='admitted')
                                {{ $applicant->admittedProgram->name ?? 'Admitted' }}
                            @elseif($applicant->application_status==='rejected')
                                Not offered
                            @else
                                Pending
                            @endif
                        </p>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50">
                        <p class="text-gray-400 uppercase text-xs font-bold">Next Step</p>
                        <p class="font-semibold text-gray-700">
                            @if($applicant->application_status==='submitted') Await admission decision
                            @elseif($applicant->application_status==='admitted') Accept &amp; pay acceptance fee <span class="text-xs text-gray-400">(Phase 3)</span>
                            @else Complete application payment @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Fees / invoices --}}
            <div id="fees" class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b font-bold text-gray-700">Fees &amp; Payments</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                        <tr>
                            <th class="text-left px-6 py-3">Description</th>
                            <th class="text-left px-6 py-3">Amount</th>
                            <th class="text-left px-6 py-3">Status</th>
                            <th class="text-right px-6 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($invoices as $inv)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $inv->description }}</td>
                                <td class="px-6 py-3">{{ money($inv->amount) }}</td>
                                <td class="px-6 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $inv->isPaid() ? 'bg-green-100 text-green-700':'bg-amber-100 text-amber-700' }}">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @if($inv->isPaid())
                                        <a href="{{ route('invoices.receipt', $inv) }}" target="_blank" class="text-indigo-600 hover:underline font-semibold">Print Receipt</a>
                                    @else
                                        <a href="{{ route('payments.initialize', $inv) }}" class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-emerald-700">Pay Now</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No invoices.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
