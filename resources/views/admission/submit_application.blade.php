<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Submit Your Application</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('error'))
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc ml-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- Section 1: Confirm Your Data --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h3 class="font-bold text-gray-700">Step 1: Confirm Your Information</h3>
                    <p class="text-xs text-gray-500">Review your details below. If anything is incorrect, contact the admissions office.</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs font-bold uppercase">Full Name</p>
                            <p class="font-semibold text-gray-800">{{ $applicant->full_name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs font-bold uppercase">Date of Birth</p>
                            <p class="font-semibold text-gray-800">{{ $applicant->date_of_birth?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs font-bold uppercase">Gender</p>
                            <p class="font-semibold text-gray-800">{{ ucfirst($applicant->gender ?? '—') }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs font-bold uppercase">Email</p>
                            <p class="font-semibold text-gray-800">{{ $applicant->email }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs font-bold uppercase">Phone</p>
                            <p class="font-semibold text-gray-800">{{ $applicant->phone ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs font-bold uppercase">1st Choice Programme</p>
                            <p class="font-semibold text-gray-800">{{ $applicant->firstChoice->name ?? '—' }}</p>
                        </div>
                        @if($applicant->secondChoice)
                        <div>
                            <p class="text-gray-400 text-xs font-bold uppercase">2nd Choice Programme</p>
                            <p class="font-semibold text-gray-800">{{ $applicant->secondChoice->name }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-gray-400 text-xs font-bold uppercase">Guardian</p>
                            <p class="font-semibold text-gray-800">{{ $applicant->guardian_name ?? $applicant->parent_name ?? '—' }}</p>
                        </div>
                    </div>

                    {{-- O-Level Results --}}
                    @if($applicant->olevel_results && count($applicant->olevel_results))
                    <div class="mt-6">
                        <p class="text-gray-400 text-xs font-bold uppercase mb-2">O-Level Results ({{ ucfirst($applicant->exam_type ?? 'WAEC') }} {{ $applicant->exam_year ?? '' }})</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach($applicant->olevel_results as $result)
                            <div class="p-2 bg-gray-50 rounded border text-xs">
                                <span class="font-bold">{{ $result['subject'] ?? '—' }}</span>:
                                <span class="text-gray-600">{{ $result['grade'] ?? '—' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Section 2: Upload Documents --}}
            <form action="{{ route('application.submit.store') }}" method="POST" enctype="multipart/form-data"
                  class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @csrf

                <div class="px-6 py-4 bg-indigo-50 border-b">
                    <h3 class="font-bold text-indigo-900">Step 2: Upload Required Documents</h3>
                    <p class="text-xs text-indigo-700">Upload the following documents in PDF, JPG, or PNG format (max 5MB each).</p>
                </div>

                <div class="p-6 space-y-6">
                    @foreach($required as $key => $label)
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            {{ $label }} <span class="text-red-500">*</span>
                        </label>
                        @if($existing->has($key))
                            <p class="text-xs text-green-600 mb-1">Previously uploaded: {{ $existing[$key]->original_name }}</p>
                        @endif
                        <input type="file" name="docs[{{ $key }}]" {{ $existing->has($key) ? '' : 'required' }}
                               accept=".pdf,.jpg,.jpeg,.png"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    @endforeach
                </div>

                <div class="px-6 py-4 border-t bg-gray-50">
                    <button type="submit"
                            class="w-full bg-emerald-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-emerald-700 shadow-lg transition">
                        Submit Application
                    </button>
                    <p class="text-xs text-gray-500 text-center mt-2">Once submitted, your application will be reviewed by the admission committee.</p>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
