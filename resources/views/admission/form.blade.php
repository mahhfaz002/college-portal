@extends('layouts.school')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-slate-900 mb-2">Online Admission Application</h1>
    <p class="text-gray-600 mb-6">{{ $college->name ?? 'Our College' }}</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <p class="font-bold mb-1">Please correct the following:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="bg-brand-soft p-5 rounded-lg mb-8 border border-slate-200 text-sm text-slate-700">
        Complete all sections, choose your first and second program of interest, upload your passport photograph,
        then proceed to pay the <span class="font-bold">application fee</span> online. After successful payment an
        applicant account is created for you to track your admission.
    </div>

    @if($programs->isEmpty())
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg">
            Applications are not open yet — no programs have been published. Please check back soon.
        </div>
    @else
    <form method="POST" action="{{ route('admission.submit') }}" enctype="multipart/form-data" class="space-y-8"
          x-data="{ fee: '' }">
        @csrf
        <input type="hidden" name="college_id" value="{{ $college->id }}">

        {{-- Program choices --}}
        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Programs of Interest</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">First Choice *</label>
                    <select name="first_choice_program_id" required class="w-full border-gray-300 rounded-lg"
                            @change="fee = $event.target.selectedOptions[0].dataset.fee">
                        <option value="">— Select program —</option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}" data-fee="{{ $p->application_fee }}" @selected(old('first_choice_program_id')==$p->id)>
                                {{ $p->name }} ({{ $p->department->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1" x-show="fee">Application fee: ₦<span x-text="Number(fee).toLocaleString()"></span></p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Second Choice</label>
                    <select name="second_choice_program_id" class="w-full border-gray-300 rounded-lg">
                        <option value="">— Optional —</option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}" @selected(old('second_choice_program_id')==$p->id)>
                                {{ $p->name }} ({{ $p->department->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        {{-- Section A --}}
        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Section A — Applicant Information</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <input name="surname" value="{{ old('surname') }}" required placeholder="Surname *" class="border-gray-300 rounded-lg">
                <input name="first_name" value="{{ old('first_name') }}" required placeholder="First name *" class="border-gray-300 rounded-lg">
                <input name="other_name" value="{{ old('other_name') }}" placeholder="Other name" class="border-gray-300 rounded-lg">
                <input name="phone" value="{{ old('phone') }}" required placeholder="Phone number *" class="border-gray-300 rounded-lg">
                <input name="email" type="email" value="{{ old('email') }}" required placeholder="Email address * (your login)" class="border-gray-300 rounded-lg">
                <select name="gender" required class="border-gray-300 rounded-lg">
                    <option value="">Gender *</option>
                    <option @selected(old('gender')=='Male')>Male</option>
                    <option @selected(old('gender')=='Female')>Female</option>
                </select>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Date of birth *</label>
                    <input name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required class="w-full border-gray-300 rounded-lg">
                </div>
                <input name="address" value="{{ old('address') }}" required placeholder="Residential address *" class="border-gray-300 rounded-lg md:col-span-2">
            </div>
        </section>

        {{-- Section B --}}
        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Section B — Parent / Guardian Information</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <input name="guardian_name" value="{{ old('guardian_name') }}" required placeholder="Full name *" class="border-gray-300 rounded-lg">
                <input name="guardian_relationship" value="{{ old('guardian_relationship') }}" required placeholder="Relationship * (e.g. Father)" class="border-gray-300 rounded-lg">
                <input name="guardian_phone" value="{{ old('guardian_phone') }}" required placeholder="Phone number *" class="border-gray-300 rounded-lg">
                <input name="guardian_email" type="email" value="{{ old('guardian_email') }}" placeholder="Email address" class="border-gray-300 rounded-lg">
                <input name="guardian_occupation" value="{{ old('guardian_occupation') }}" placeholder="Occupation" class="border-gray-300 rounded-lg">
                <input name="guardian_address" value="{{ old('guardian_address') }}" placeholder="Address" class="border-gray-300 rounded-lg">
            </div>
        </section>

        {{-- Section C — O'Level results --}}
        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-1">Section C — O'Level Results</h2>
            <p class="text-sm text-gray-500 mb-4">The first five subjects are compulsory; add up to four more. You may combine sittings — set the exam body, year and examination number for each subject.</p>

            @php
                $fixed = ['English Language', 'Mathematics', 'Physics', 'Chemistry', 'Biology'];
                $grades = ['A1','B2','B3','C4','C5','C6','D7','E8','F9'];
                $bodies = ['WAEC','NECO','NABTEB'];
            @endphp
            <div class="overflow-x-auto border rounded-xl">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2 text-left">#</th>
                            <th class="px-3 py-2 text-left">Subject</th>
                            <th class="px-3 py-2 text-left">Grade</th>
                            <th class="px-3 py-2 text-left">Exam Type</th>
                            <th class="px-3 py-2 text-left">Exam Year</th>
                            <th class="px-3 py-2 text-left">Examination No.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @php $rowOld = fn($i,$k,$d='') => old("results.$i.$k", $d); @endphp
                        @foreach($fixed as $i => $subj)
                            <tr>
                                <td class="px-3 py-2 text-gray-400">{{ $i+1 }}</td>
                                <td class="px-3 py-2">
                                    <input type="hidden" name="results[{{ $i }}][subject]" value="{{ $subj }}">
                                    <span class="font-semibold text-gray-800">{{ $subj }}</span>
                                </td>
                                <td class="px-3 py-2">
                                    <select name="results[{{ $i }}][grade]" required class="border-gray-300 rounded-lg text-sm py-1">
                                        <option value="">—</option>
                                        @foreach($grades as $g)<option value="{{ $g }}" @selected($rowOld($i,'grade')==$g)>{{ $g }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <select name="results[{{ $i }}][exam_type]" required class="border-gray-300 rounded-lg text-sm py-1">
                                        <option value="">—</option>
                                        @foreach($bodies as $b)<option value="{{ $b }}" @selected($rowOld($i,'exam_type')==$b)>{{ $b }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <select name="results[{{ $i }}][exam_year]" required class="border-gray-300 rounded-lg text-sm py-1">
                                        <option value="">—</option>
                                        @for($y = (int)date('Y'); $y >= (int)date('Y')-15; $y--)
                                            <option value="{{ $y }}" @selected($rowOld($i,'exam_year')==$y)>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input name="results[{{ $i }}][exam_number]" value="{{ $rowOld($i,'exam_number') }}" required
                                           placeholder="Exam no." class="border-gray-300 rounded-lg text-sm py-1 w-32">
                                </td>
                            </tr>
                        @endforeach
                        @for($j = 5; $j < 9; $j++)
                            <tr>
                                <td class="px-3 py-2 text-gray-400">{{ $j+1 }}</td>
                                <td class="px-3 py-2">
                                    <input name="results[{{ $j }}][subject]" value="{{ $rowOld($j,'subject') }}" placeholder="Type subject" autocomplete="off"
                                           class="border-gray-300 rounded-lg text-sm py-1 w-full" style="text-transform:uppercase">
                                </td>
                                <td class="px-3 py-2">
                                    <select name="results[{{ $j }}][grade]" class="border-gray-300 rounded-lg text-sm py-1">
                                        <option value="">—</option>
                                        @foreach($grades as $g)<option value="{{ $g }}" @selected($rowOld($j,'grade')==$g)>{{ $g }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <select name="results[{{ $j }}][exam_type]" class="border-gray-300 rounded-lg text-sm py-1">
                                        <option value="">—</option>
                                        @foreach($bodies as $b)<option value="{{ $b }}" @selected($rowOld($j,'exam_type')==$b)>{{ $b }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <select name="results[{{ $j }}][exam_year]" class="border-gray-300 rounded-lg text-sm py-1">
                                        <option value="">—</option>
                                        @for($y = (int)date('Y'); $y >= (int)date('Y')-15; $y--)
                                            <option value="{{ $y }}" @selected($rowOld($j,'exam_year')==$y)>{{ $y }}</option>
                                        @endfor
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input name="results[{{ $j }}][exam_number]" value="{{ $rowOld($j,'exam_number') }}"
                                           placeholder="Exam no." class="border-gray-300 rounded-lg text-sm py-1 w-32">
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Section D --}}
        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Section D — Passport Photograph</h2>
            <input name="passport" type="file" accept="image/*" required class="block w-full text-sm text-gray-600">
            <p class="text-xs text-gray-500 mt-1">Recent passport photograph (JPG/PNG, max 2MB).</p>
        </section>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-brand-soft border border-slate-200 rounded-xl p-5">
            <p class="text-sm text-slate-600">You will be redirected to pay the application fee securely after submitting.</p>
            <button class="btn-brand px-8 py-3 rounded-full shadow whitespace-nowrap">
                Submit &amp; Pay Application Fee
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
