<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🗂️ Complete Your Registration</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            @php
                $rs = $student->registration_status;
                $banner = match($rs) {
                    'pending_hod' => ['Submitted — awaiting your Head of Department\'s approval.', 'bg-blue-50 border-blue-200 text-blue-800'],
                    'registered'  => ['You are fully registered. 🎉', 'bg-green-50 border-green-200 text-green-800'],
                    'documents_rejected' => ['Your documents were returned for correction. Please re-upload and resubmit.', 'bg-red-50 border-red-200 text-red-800'],
                    default => ['Upload the required documents below, then submit for your HOD\'s review.', 'bg-amber-50 border-amber-200 text-amber-800'],
                };
            @endphp
            <div class="p-4 rounded-lg border text-sm {{ $banner[1] }}">
                <p class="font-semibold">{{ $banner[0] }}</p>
                <p class="text-xs mt-1 opacity-80">Reg. No: <span class="font-bold">{{ $student->registration_number }}</span> · {{ $student->program->name ?? '' }}</p>
            </div>

            <form method="POST" action="{{ route('registration.documents.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                @csrf
                @foreach($required as $key => $label)
                    @php $have = $existing->get($key); @endphp
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 border-b pb-3">
                        <label class="sm:w-1/2 text-sm font-semibold text-gray-700">
                            {{ $label }}
                            @if($have)<span class="ml-2 text-xs text-green-600">✓ uploaded</span>@endif
                        </label>
                        <input type="file" name="docs[{{ $key }}]" accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full text-sm text-gray-600">
                    </div>
                @endforeach

                <div class="flex items-center justify-between pt-2">
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="submit_for_review" value="1" class="rounded">
                        Submit for HOD review now
                    </label>
                    <button class="bg-emerald-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-emerald-700">
                        Save Documents
                    </button>
                </div>
                <p class="text-xs text-gray-400">Accepted: PDF/JPG/PNG, max 4MB each. Diploma certificate is only required for Direct Entry applicants.</p>
            </form>
        </div>
    </div>
</x-app-layout>
