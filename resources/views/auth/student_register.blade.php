@extends('layouts.school')

@section('content')
@php
    $progJson = $programs->map(fn($p) => [
        'id' => $p->id, 'name' => $p->name, 'department_id' => $p->department_id,
        'type' => $p->program_type, 'levels' => (int)($p->levels ?: 1),
    ])->values();
@endphp
<div class="max-w-3xl mx-auto py-10 px-4"
     x-data="{
        first:'', other:'', surname:'', deptId:'', programId:'',
        programs: {{ Illuminate\Support\Js::from($progJson) }},
        get username(){
            const s=(this.surname||'').replace(/\s+/g,'');
            if(!this.first||!s) return '';
            const u=(this.first[0]||'')+(this.other?this.other[0]:'')+s;
            return u.toLowerCase().replace(/[^a-z0-9]/g,'');
        },
        get filteredPrograms(){ return this.deptId ? this.programs.filter(p=>p.department_id==this.deptId) : []; },
        get currentProgram(){ return this.programs.find(p=>p.id==this.programId); },
        get levelCount(){ return this.currentProgram ? this.currentProgram.levels : 0; },
     }">
    <h1 class="text-3xl font-bold text-indigo-900 mb-2">Create Your Student Account</h1>
    <p class="text-gray-600 mb-6">{{ $college->name ?? 'Our College' }}</p>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside text-sm">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="bg-indigo-50 p-4 rounded-lg mb-6 border border-indigo-200 text-sm text-indigo-900">
        Fill your details, then pay a one-off platform registration fee of
        <span class="font-bold">{{ money(config('services.paystack.platform_registration_fee', 5000)) }}</span>
        to activate your account. The payment service charge is added at checkout.
    </div>

    <form method="POST" action="{{ route('student.register.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Bio Data</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <input name="surname" x-model="surname" value="{{ old('surname') }}" required placeholder="Surname *" class="border-gray-300 rounded-lg">
                <input name="first_name" x-model="first" value="{{ old('first_name') }}" required placeholder="First name *" class="border-gray-300 rounded-lg">
                <input name="other_name" x-model="other" value="{{ old('other_name') }}" placeholder="Other name" class="border-gray-300 rounded-lg">
                <input name="phone" value="{{ old('phone') }}" required placeholder="Phone number *" class="border-gray-300 rounded-lg">
                <input name="email" type="email" value="{{ old('email') }}" required placeholder="Email address *" class="border-gray-300 rounded-lg">
                <input name="address" value="{{ old('address') }}" required placeholder="Residential address *" class="border-gray-300 rounded-lg md:col-span-2">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Username (auto, locked)</label>
                    <input :value="username" readonly placeholder="generated from your name" class="bg-gray-100 border-gray-300 rounded-lg w-full text-gray-600 cursor-not-allowed">
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Academic</h2>
            <div class="grid md:grid-cols-2 gap-4">
                <input name="registration_number" value="{{ old('registration_number') }}" required placeholder="Student Registration Number *" class="border-gray-300 rounded-lg md:col-span-2">

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department *</label>
                    <select x-model="deptId" required class="w-full border-gray-300 rounded-lg">
                        <option value="">— Select department —</option>
                        @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study *</label>
                    <select name="program_id" x-model="programId" required class="w-full border-gray-300 rounded-lg">
                        <option value="">— Select course of study —</option>
                        <template x-for="p in filteredPrograms" :key="p.id">
                            <option :value="p.id" x-text="p.name + ' (' + p.type + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Program</label>
                    <input :value="currentProgram ? currentProgram.type : ''" readonly placeholder="UG / DIP / CERT" class="bg-gray-100 border-gray-300 rounded-lg w-full text-gray-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Level *</label>
                    <select name="level" required class="w-full border-gray-300 rounded-lg">
                        <option value="">— Select level —</option>
                        <template x-for="i in levelCount" :key="i">
                            <option :value="(i*100)" x-text="(i*100)"></option>
                        </template>
                    </select>
                </div>
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
</div>
@endsection
