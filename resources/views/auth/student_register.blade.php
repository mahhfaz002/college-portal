@extends('layouts.school')

@section('content')
<div class="max-w-3xl mx-auto py-10 px-4">
    <h1 class="text-3xl font-bold text-indigo-900 mb-2">Create Your Student Account</h1>
    <p class="text-gray-600 mb-6">{{ $college->name ?? 'Our College' }}</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside text-sm">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if (empty($record))
        {{-- STEP 1 — identify by registration number --}}
        <div class="bg-indigo-50 p-4 rounded-lg mb-6 border border-indigo-200 text-sm text-indigo-900">
            Enter the <span class="font-bold">registration number</span> issued to you by the college to begin.
            Your details will be loaded automatically. If your number isn't recognised, contact the Registrar.
        </div>
        <form method="POST" action="{{ route('student.register.lookup') }}" class="bg-white rounded-xl shadow-sm border p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Registration Number *</label>
                <input name="registration_number" value="{{ old('registration_number') }}" required
                       placeholder="e.g. MAHHFAZ/2026/NUR/0001" class="w-full border-gray-300 rounded-lg">
            </div>
            <button class="bg-indigo-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-indigo-700">Continue →</button>
        </form>
    @else
        {{-- STEP 2 — prefilled from the admitted record; student completes the rest --}}
        <div class="bg-emerald-50 p-4 rounded-lg mb-6 border border-emerald-200 text-sm text-emerald-900">
            We found your admitted record. Confirm the details, complete the remaining fields, then pay the one-off
            platform registration fee of
            <span class="font-bold">{{ money(config('services.paystack.platform_registration_fee', 5000)) }}</span>
            (service charge added at checkout) to activate your account.
        </div>

        <form method="POST" action="{{ route('student.register.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="registration_number" value="{{ $record->registration_number }}">

            <section class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Your Admitted Record</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Full Name</label>
                        <input value="{{ $record->full_name }}" readonly class="bg-gray-100 border-gray-300 rounded-lg w-full text-gray-700">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Registration Number</label>
                        <input value="{{ $record->registration_number }}" readonly class="bg-gray-100 border-gray-300 rounded-lg w-full text-gray-700">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                        <input value="{{ $record->department ?: '—' }}" readonly class="bg-gray-100 border-gray-300 rounded-lg w-full text-gray-700">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                        <input value="{{ $record->level ?: '100' }}" readonly class="bg-gray-100 border-gray-300 rounded-lg w-full text-gray-700">
                    </div>
                </div>
            </section>

            <section class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Course &amp; Contact</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study *</label>
                        <select name="program_id" required class="w-full border-gray-300 rounded-lg">
                            <option value="">— Select your course of study —</option>
                            @foreach($programs as $p)
                                <option value="{{ $p->id }}" @selected(old('program_id') == $p->id)>
                                    {{ $p->name }}@if($p->department) — {{ $p->department->name }}@endif ({{ \App\Support\Sections::label($p->program_type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <input name="phone" value="{{ old('phone') }}" required placeholder="Phone number *" class="border-gray-300 rounded-lg">
                    <input name="email" type="email" value="{{ old('email') }}" required placeholder="Email address *" class="border-gray-300 rounded-lg">
                    <input name="address" value="{{ old('address') }}" required placeholder="Residential address *" class="border-gray-300 rounded-lg md:col-span-2">
                </div>
            </section>

            <section class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Security &amp; Photo</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <input name="password" type="password" required placeholder="Password *" class="border-gray-300 rounded-lg">
                    <input name="password_confirmation" type="password" required placeholder="Confirm password *" class="border-gray-300 rounded-lg">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Passport Photograph *</label>
                        <input name="passport" type="file" accept="image/*" required class="block w-full text-sm text-gray-600">
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-xl p-5">
                <p class="text-sm text-emerald-800">You'll be taken to pay the platform registration fee after creating your account.</p>
                <button class="bg-emerald-600 text-white px-8 py-3 rounded-full font-bold hover:bg-emerald-700 shadow">Create Account</button>
            </div>
        </form>
    @endif
</div>
@endsection
