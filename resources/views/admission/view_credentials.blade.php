<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Applicant Credentials — {{ $applicant->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <a href="{{ route('admissions.review') }}" class="inline-flex items-center text-sm text-indigo-600 font-bold hover:underline">
                &larr; Back to Applications
            </a>

            {{-- Personal Information --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h3 class="font-bold text-gray-700">Personal Information</h3>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase">Full Name</p>
                        <p class="font-semibold text-gray-800">{{ $applicant->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase">Date of Birth</p>
                        <p class="font-semibold text-gray-800">{{ $applicant->date_of_birth?->format('M d, Y') ?? '—' }} ({{ $applicant->age() ? $applicant->age().' years' : '—' }})</p>
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
                        <p class="text-gray-400 text-xs font-bold uppercase">Address</p>
                        <p class="font-semibold text-gray-800">{{ $applicant->address ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase">1st Choice</p>
                        <p class="font-semibold text-gray-800">{{ $applicant->firstChoice->name ?? '—' }} ({{ optional(optional($applicant->firstChoice)->department)->name ?? '' }})</p>
                    </div>
                    @if($applicant->secondChoice)
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase">2nd Choice</p>
                        <p class="font-semibold text-gray-800">{{ $applicant->secondChoice->name }}</p>
                    </div>
                    @endif
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase">Guardian</p>
                        <p class="font-semibold text-gray-800">{{ $applicant->guardian_name ?? $applicant->parent_name ?? '—' }} ({{ $applicant->guardian_relationship ?? '—' }})</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs font-bold uppercase">Guardian Phone</p>
                        <p class="font-semibold text-gray-800">{{ $applicant->guardian_phone ?? $applicant->parent_phone ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- O-Level Results --}}
            @if($applicant->olevel_results && count($applicant->olevel_results))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h3 class="font-bold text-gray-700">O-Level Results ({{ ucfirst($applicant->exam_type ?? 'WAEC') }} {{ $applicant->exam_year ?? '' }})</h3>
                </div>
                <div class="p-6">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="text-left px-4 py-2">Subject</th>
                                <th class="text-center px-4 py-2">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($applicant->olevel_results as $result)
                            <tr>
                                <td class="px-4 py-2 font-semibold text-gray-800">{{ $result['subject'] ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                        {{ in_array($result['grade'] ?? '', ['A1','B2','B3','C4','C5','C6']) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $result['grade'] ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Uploaded Documents --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-indigo-50 border-b">
                    <h3 class="font-bold text-indigo-900">Uploaded Documents</h3>
                </div>
                <div class="p-6 space-y-4">
                    @php
                        $docLabels = [
                            'jamb_result' => 'JAMB Result',
                            'ssce'        => 'SSCE Certificate',
                            'passport'    => 'Passport Photograph',
                        ];
                    @endphp
                    @foreach($docLabels as $key => $label)
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <p class="font-bold text-gray-800 text-sm">{{ $label }}</p>
                            @if($documents->has($key))
                                <p class="text-xs text-gray-500">{{ $documents[$key]->original_name }}</p>
                            @else
                                <p class="text-xs text-red-500">Not uploaded</p>
                            @endif
                        </div>
                        @if($documents->has($key))
                            <a href="{{ route('documents.show', $documents[$key]) }}" target="_blank"
                               class="bg-indigo-600 text-white px-4 py-2 rounded-full text-xs font-bold hover:bg-indigo-700">
                                View Document
                            </a>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Application Status --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase">Application Status</p>
                        <p class="font-bold text-lg text-gray-800">{{ ucfirst(str_replace('_', ' ', $applicant->application_status)) }}</p>
                        @if($applicant->documents_submitted_at)
                            <p class="text-xs text-gray-500">Documents submitted: {{ $applicant->documents_submitted_at->format('M d, Y h:i A') }}</p>
                        @endif
                    </div>
                    @if($applicant->admittedProgram)
                        <span class="px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-700">
                            Admitted to {{ $applicant->admittedProgram->name }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
