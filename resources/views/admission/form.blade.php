@extends('layouts.school')

@section('content')
<div class="max-w-4xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-indigo-900 mb-2">Online Admission Application</h1>
    <p class="text-gray-600 mb-6">{{ $college->name ?? 'Our College' }}</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <p class="font-bold mb-1">Please correct the following:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="bg-indigo-50 p-5 rounded-lg mb-8 border border-indigo-200 text-sm text-indigo-900">
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

        {{-- Section C --}}
        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Section C — Sponsor Information</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <input name="sponsor_name" value="{{ old('sponsor_name') }}" required placeholder="Full name *" class="border-gray-300 rounded-lg">
                <input name="sponsor_relationship" value="{{ old('sponsor_relationship') }}" required placeholder="Relationship * (e.g. Self, Uncle)" class="border-gray-300 rounded-lg">
                <input name="sponsor_phone" value="{{ old('sponsor_phone') }}" required placeholder="Phone number *" class="border-gray-300 rounded-lg">
                <input name="sponsor_address" value="{{ old('sponsor_address') }}" placeholder="Address" class="border-gray-300 rounded-lg">
            </div>
        </section>

        {{-- Section D --}}
        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Section D — Passport Photograph</h2>
            <input name="passport" type="file" accept="image/*" required class="block w-full text-sm text-gray-600">
            <p class="text-xs text-gray-500 mt-1">Recent passport photograph (JPG/PNG, max 2MB).</p>
        </section>

        <div class="flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-xl p-5">
            <p class="text-sm text-emerald-800">You will be redirected to pay the application fee securely after submitting.</p>
            <button class="bg-emerald-600 text-white px-8 py-3 rounded-full font-bold hover:bg-emerald-700 shadow">
                Submit &amp; Pay Application Fee
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
