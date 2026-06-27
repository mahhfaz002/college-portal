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
                            'awaiting_documents' => ['Complete Your Application', 'bg-amber-100 text-amber-700'],
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
                            @if($applicant->application_status==='awaiting_documents') Upload documents &amp; submit application
                            @elseif($applicant->application_status==='submitted') Await admission decision
                            @elseif($applicant->application_status==='admitted') Accept &amp; pay acceptance fee
                            @else Complete application payment @endif
                        </p>
                    </div>
                </div>
            </div>

            {{-- Submit Application: upload documents --}}
            @if($applicant->application_status === 'awaiting_documents')
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-6">
                    <h3 class="font-bold text-indigo-900 text-lg mb-1">Complete Your Application</h3>
                    <p class="text-sm text-indigo-800 mb-4">
                        Your application fee has been paid. To complete your application, you need to upload the required documents
                        (JAMB result, SSCE certificate, and passport photograph) and submit for review.
                    </p>
                    <a href="{{ route('application.submit.show') }}" class="inline-block bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-700">
                        Submit Application &rarr;
                    </a>
                </div>
            @endif

            {{-- Admission offer: accept / reject --}}
            @if($applicant->application_status === 'admitted')
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-6">
                    <h3 class="font-bold text-amber-900 text-lg mb-1">🎉 Admission Offer</h3>
                    <p class="text-sm text-amber-800 mb-4">
                        You have been offered admission into
                        <span class="font-bold">{{ $applicant->admittedProgram->name ?? '' }}</span>.
                        Please accept to proceed to the acceptance fee, or reject to reapply for another program.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('admission.accept') }}">
                            @csrf
                            <button class="bg-emerald-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-emerald-700">
                                Accept &amp; Pay Acceptance Fee
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admission.reject.offer') }}"
                              onsubmit="return confirm('Reject this admission offer?')">
                            @csrf
                            <button class="bg-white border border-red-300 text-red-600 px-6 py-2.5 rounded-full font-bold hover:bg-red-50">
                                Reject Offer
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Acceptance paid: admission letter + acceptance form + registration --}}
            @if(in_array($applicant->application_status, ['accepted','registered']))
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                    <h3 class="font-bold text-emerald-900 text-lg mb-3">Admission Documents</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admission.letter') }}" target="_blank" class="bg-indigo-600 text-white px-5 py-2 rounded-full font-semibold hover:bg-indigo-700">📄 Admission Letter</a>
                        <a href="{{ route('admission.acceptance_form') }}" target="_blank" class="bg-white border px-5 py-2 rounded-full font-semibold hover:bg-gray-50">📝 Acceptance Form</a>
                        @if($applicant->application_status === 'registered')
                            <a href="{{ route('registration.documents') }}" class="bg-emerald-600 text-white px-5 py-2 rounded-full font-semibold hover:bg-emerald-700">Upload Registration Documents →</a>
                        @endif
                    </div>
                    @if($applicant->application_status === 'accepted')
                        <p class="text-sm text-emerald-800 mt-3">Pay your <span class="font-bold">registration fee</span> below to unlock your student dashboard.</p>
                    @endif
                </div>
            @endif

            {{-- Offer rejected / declined: reapply --}}
            @if(in_array($applicant->application_status, ['offer_rejected','rejected']))
                <div class="bg-white border rounded-xl p-6">
                    <h3 class="font-bold text-gray-800 text-lg mb-1">Reapply for Another Program</h3>
                    <p class="text-sm text-gray-500 mb-4">A new application fee applies for each reapplication.</p>
                    <form method="POST" action="{{ route('admission.reapply') }}" class="flex flex-wrap gap-3 items-center">
                        @csrf
                        <select name="program_id" required class="border-gray-300 rounded-lg">
                            <option value="">Choose a program…</option>
                            @foreach(\App\Models\Program::withoutGlobalScopes()->where('college_id',$applicant->college_id)->orderBy('name')->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} — app fee {{ money($p->application_fee) }}</option>
                            @endforeach
                        </select>
                        <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-700">Reapply &amp; Pay</button>
                    </form>
                </div>
            @endif

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
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                        {{ $inv->isPaid() ? 'bg-green-100 text-green-700' : ($inv->status === 'cancelled' ? 'bg-gray-100 text-gray-500' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ucfirst($inv->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    @if($inv->isPaid())
                                        <a href="{{ route('invoices.receipt', $inv) }}" target="_blank" class="text-indigo-600 hover:underline font-semibold">Print Receipt</a>
                                    @elseif($inv->status === 'cancelled')
                                        <form method="POST" action="{{ route('invoices.destroy', $inv) }}" class="inline" onsubmit="return confirm('Delete this cancelled invoice?')">
                                            @csrf @method('DELETE')
                                            <button class="text-red-500 text-xs font-bold hover:underline">Delete</button>
                                        </form>
                                    @else
                                        <a href="{{ route('payments.checkout', $inv) }}" class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-emerald-700">Pay Now</a>
                                        <form method="POST" action="{{ route('invoices.cancel', $inv) }}" class="inline ml-2" onsubmit="return confirm('Cancel this payment? You can delete it afterwards.')">
                                            @csrf
                                            <button class="text-gray-400 text-xs font-bold hover:text-red-500 hover:underline">Cancel</button>
                                        </form>
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
