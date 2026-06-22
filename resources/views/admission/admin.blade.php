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
                                <th class="px-4 py-3">Guardian</th>
                                <th class="px-4 py-3">Programme</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($applicants as $applicant)
                            <tr class="hover:bg-gray-50 align-top" x-data="{ cred:false, offer:false }">
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
                                    <button type="button" @click="cred=true" class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded font-bold hover:bg-indigo-100">🔍 Show credentials</button>
                                    @if($applicant->birth_cert_path)<a href="{{ media_url($applicant->birth_cert_path) }}" target="_blank" class="block text-indigo-600 underline">Birth certificate</a>@endif
                                    @if($applicant->fslc_path)<a href="{{ media_url($applicant->fslc_path) }}" target="_blank" class="block text-indigo-600 underline">FSLC</a>@endif

                                    {{-- Credentials popup: everything the applicant filled in, for review. --}}
                                    <div x-show="cred" x-cloak @keydown.escape.window="cred=false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
                                        <div class="absolute inset-0 bg-black/50" @click="cred=false"></div>
                                        <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
                                            <div class="flex justify-between items-center px-6 py-4 border-b sticky top-0 bg-white">
                                                <h3 class="font-bold text-gray-800">Applicant Credentials — {{ $applicant->full_name }}</h3>
                                                <button type="button" @click="cred=false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                                            </div>
                                            <div class="p-6 space-y-5 text-sm">
                                                <div class="flex items-start gap-4">
                                                    @if($applicant->passport)<img src="{{ $applicant->passport }}" class="w-20 h-20 rounded-lg object-cover border">@endif
                                                    <div class="grid grid-cols-2 gap-x-6 gap-y-1">
                                                        <div><span class="text-gray-400">Full name</span><br><b>{{ $applicant->full_name }}</b></div>
                                                        <div><span class="text-gray-400">Gender</span><br>{{ $applicant->gender ?: '—' }}</div>
                                                        <div><span class="text-gray-400">Date of birth</span><br>{{ optional($applicant->date_of_birth)->format('d M Y') ?: '—' }}</div>
                                                        <div><span class="text-gray-400">Email</span><br>{{ $applicant->email ?: '—' }}</div>
                                                        <div><span class="text-gray-400">Phone</span><br>{{ $applicant->phone ?: '—' }}</div>
                                                        <div class="col-span-2"><span class="text-gray-400">Address</span><br>{{ $applicant->address ?: '—' }}</div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-600 uppercase text-[11px] mb-1">Programme choices</p>
                                                    <p>1st: <b>{{ optional($applicant->firstChoice)->name ?: $applicant->desired_class ?: '—' }}</b></p>
                                                    <p>2nd: {{ optional($applicant->secondChoice)->name ?: '—' }}</p>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-600 uppercase text-[11px] mb-1">Guardian / Sponsor</p>
                                                    <p>{{ $applicant->guardian_name ?: $applicant->parent_name }} ({{ $applicant->guardian_relationship ?: '—' }})</p>
                                                    <p class="text-gray-500">{{ $applicant->guardian_phone ?: $applicant->parent_phone }} · {{ $applicant->guardian_email ?: $applicant->parent_email }}</p>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-600 uppercase text-[11px] mb-1">O'Level results ({{ $applicant->exam_type ?: '—' }} {{ $applicant->exam_year }})</p>
                                                    @php $ol = is_array($applicant->olevel_results) ? $applicant->olevel_results : []; @endphp
                                                    @if(count($ol))
                                                        <table class="w-full text-[11px] border">
                                                            <thead class="bg-gray-50 text-gray-500"><tr>
                                                                <th class="px-2 py-1 text-left">Subject</th><th class="px-2 py-1">Grade</th>
                                                                <th class="px-2 py-1">Type</th><th class="px-2 py-1">Year</th><th class="px-2 py-1 text-left">Exam No.</th>
                                                            </tr></thead>
                                                            <tbody class="divide-y">
                                                                @foreach($ol as $r)<tr>
                                                                    <td class="px-2 py-1">{{ $r['subject'] ?? '' }}</td>
                                                                    <td class="px-2 py-1 text-center font-bold">{{ $r['grade'] ?? '' }}</td>
                                                                    <td class="px-2 py-1 text-center">{{ $r['exam_type'] ?? '—' }}</td>
                                                                    <td class="px-2 py-1 text-center">{{ $r['exam_year'] ?? '—' }}</td>
                                                                    <td class="px-2 py-1">{{ $r['exam_number'] ?? '—' }}</td>
                                                                </tr>@endforeach
                                                            </tbody>
                                                        </table>
                                                    @else <span class="text-gray-400">Not provided</span> @endif
                                                </div>
                                            </div>
                                            <div class="px-6 py-4 border-t text-right sticky bottom-0 bg-white">
                                                <button type="button" @click="cred=false" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-bold hover:bg-gray-200">Close</button>
                                            </div>
                                        </div>
                                    </div>
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
                                        @if(in_array($applicant->status, ['pending', 'admitted']))
                                            <button type="button" @click="offer=true" class="bg-indigo-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-indigo-700">View</button>
                                        @else
                                            <span class="text-xs text-gray-400 italic">{{ ucfirst($applicant->status) }}</span>
                                        @endif

                                        {{-- Offer / reject popup: pick the course of study to offer admission into. --}}
                                        <div x-show="offer" x-cloak @keydown.escape.window="offer=false" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display:none">
                                            <div class="absolute inset-0 bg-black/50" @click="offer=false"></div>
                                            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md text-left">
                                                <div class="flex justify-between items-center px-6 py-4 border-b">
                                                    <h3 class="font-bold text-gray-800">Admission decision — {{ $applicant->full_name }}</h3>
                                                    <button type="button" @click="offer=false" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
                                                </div>
                                                <div class="p-6 space-y-5">
                                                    <form action="{{ route('admissions.offer', $applicant->id) }}" method="POST" class="space-y-3">
                                                        @csrf
                                                        <label class="block text-xs font-bold text-gray-500 uppercase">Offer admission into course of study</label>
                                                        <select name="program_id" required class="w-full border-gray-300 rounded-lg text-sm">
                                                            <option value="">— Select course —</option>
                                                            @foreach($programs as $p)
                                                                <option value="{{ $p->id }}" @selected($applicant->first_choice_program_id == $p->id)>
                                                                    {{ $p->name }}@if($p->department) — {{ $p->department->name }}@endif
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button class="w-full bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-700">Offer Admission</button>
                                                    </form>
                                                    <div class="border-t pt-4">
                                                        <form action="{{ route('admissions.decline', $applicant->id) }}" method="POST" class="space-y-2">
                                                            @csrf
                                                            <label class="block text-xs font-bold text-gray-500 uppercase">Or reject — reason</label>
                                                            <input name="reason" placeholder="Reason for rejection" class="w-full border-gray-300 rounded-lg text-sm">
                                                            <button class="w-full bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700">Reject Application</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
