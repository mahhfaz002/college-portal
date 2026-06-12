<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📋 Admission Review Panel</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))<div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-200">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">Applications</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-100 border-b">
                            <tr class="text-xs uppercase text-gray-500">
                                <th class="px-4 py-3">Applicant</th>
                                <th class="px-4 py-3">Credentials</th>
                                <th class="px-4 py-3">Parent</th>
                                <th class="px-4 py-3">Class</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($applicants as $applicant)
                            <tr class="hover:bg-gray-50 align-top">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($applicant->passport)
                                            <img src="{{ $applicant->passport }}" class="w-12 h-12 rounded object-cover border" alt="">
                                        @else
                                            <div class="w-12 h-12 rounded bg-gray-100 flex items-center justify-center text-gray-300 font-black">{{ strtoupper(substr($applicant->full_name,0,1)) }}</div>
                                        @endif
                                        <div>
                                            <p class="font-bold text-gray-800">{{ $applicant->full_name }}</p>
                                            <p class="text-xs text-gray-400">{{ $applicant->gender }} · {{ $applicant->age() !== null ? $applicant->age().' yrs' : 'DOB '.$applicant->date_of_birth }}</p>
                                            @if($applicant->section)<p class="text-[10px] font-bold text-indigo-700">{{ $applicant->section }}</p>@endif
                                            @if($applicant->admission_number)<p class="text-xs font-mono text-green-700">{{ $applicant->admission_number }}</p>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-xs space-y-1">
                                    @if($applicant->birth_cert_path)<a href="{{ media_url($applicant->birth_cert_path) }}" target="_blank" class="block text-indigo-600 underline">Birth certificate</a>@endif
                                    @if($applicant->fslc_path)<a href="{{ media_url($applicant->fslc_path) }}" target="_blank" class="block text-indigo-600 underline">FSLC</a>@endif
                                    @if($applicant->junior_waec_path)<a href="{{ media_url($applicant->junior_waec_path) }}" target="_blank" class="block text-indigo-600 underline">Junior WAEC</a>@endif
                                    @if($applicant->indigene_letter_path)<a href="{{ media_url($applicant->indigene_letter_path) }}" target="_blank" class="block text-indigo-600 underline">Indigene letter</a>@endif
                                    @if(!$applicant->birth_cert_path && !$applicant->indigene_letter_path)<span class="text-gray-400">—</span>@endif
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600">
                                    {{ $applicant->parent_name }}<br>
                                    <span class="text-xs text-indigo-600">{{ $applicant->parent_email }}</span><br>
                                    <span class="text-xs text-gray-400">{{ $applicant->parent_phone }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm font-bold text-indigo-700">{{ $applicant->desired_class }}</td>
                                <td class="px-4 py-4">
                                    @php $b = ['pending'=>'bg-yellow-100 text-yellow-700','admitted'=>'bg-green-100 text-green-700','approved'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700'][$applicant->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                                    <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $b }}">{{ $applicant->status }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @can('manage_admissions')
                                        @if($applicant->status === 'pending')
                                        <div class="flex justify-center gap-2">
                                            <form action="{{ route('admission.approve', $applicant->id) }}" method="POST" onsubmit="return confirm('Admit {{ $applicant->full_name }} and create a student record?')">
                                                @csrf
                                                <button class="bg-green-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-green-700">Admit</button>
                                            </form>
                                            <details class="text-left">
                                                <summary class="cursor-pointer bg-red-100 text-red-700 px-3 py-1.5 rounded text-xs font-bold">Reject</summary>
                                                <form action="{{ route('admission.reject', $applicant->id) }}" method="POST" class="mt-2 w-44">
                                                    @csrf
                                                    <input name="reason" placeholder="Reason" class="w-full border-gray-300 rounded text-xs mb-1">
                                                    <button class="bg-red-600 text-white px-3 py-1 rounded text-xs font-bold w-full">Confirm Reject</button>
                                                </form>
                                            </details>
                                        </div>
                                        @else
                                        <span class="text-xs text-gray-400 italic">{{ ucfirst($applicant->status) }}</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400 italic">View only</span>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center p-8 text-gray-400 italic">No applications found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
