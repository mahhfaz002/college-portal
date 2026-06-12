<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏫 Register a College</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())<div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <form method="POST" action="{{ route('platform.colleges.store') }}" class="bg-white rounded-2xl shadow-sm border p-6 space-y-6">
                @csrf
                <div>
                    <h3 class="font-bold text-gray-800 mb-3">College Details</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <input name="name" value="{{ old('name') }}" required placeholder="College name *" class="border-gray-300 rounded-lg md:col-span-2">
                        <input name="acronym" value="{{ old('acronym') }}" required placeholder="Acronym * (e.g. ALBAZ)" class="border-gray-300 rounded-lg">
                        <input name="domain" value="{{ old('domain') }}" placeholder="Domain (e.g. albazchst.edu.ng)" class="border-gray-300 rounded-lg">
                        <input name="email" type="email" value="{{ old('email') }}" placeholder="Contact email" class="border-gray-300 rounded-lg">
                        <input name="phone" value="{{ old('phone') }}" placeholder="Phone" class="border-gray-300 rounded-lg">
                        <input name="address" value="{{ old('address') }}" placeholder="Address" class="border-gray-300 rounded-lg md:col-span-2">
                        <input name="tagline" value="{{ old('tagline') }}" placeholder="Tagline (shown on homepage)" class="border-gray-300 rounded-lg">
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-gray-500 uppercase">Brand colour</label>
                            <input name="primary_color" type="color" value="{{ old('primary_color','#1d4ed8') }}" class="h-9 w-16 border-gray-300 rounded">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">The domain makes this college's landing page open under its own address (point the DNS / add the domain in hosting). Leave blank to use the shared address.</p>
                </div>

                <div class="border-t pt-5">
                    <h3 class="font-bold text-gray-800 mb-3">First Administrator (Registrar)</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <input name="admin_name" value="{{ old('admin_name') }}" required placeholder="Admin full name *" class="border-gray-300 rounded-lg">
                        <input name="admin_email" type="email" value="{{ old('admin_email') }}" required placeholder="Admin email *" class="border-gray-300 rounded-lg">
                        <input name="admin_password" required placeholder="Temporary password *" class="border-gray-300 rounded-lg md:col-span-2">
                    </div>
                    <p class="text-xs text-gray-400 mt-2">This Registrar account can then register the college's staff and build its academic structure. They will be prompted to change the password on first login.</p>
                </div>

                <button class="bg-emerald-600 text-white px-8 py-2.5 rounded-full font-bold hover:bg-emerald-700">Register College</button>
            </form>
        </div>
    </div>
</x-app-layout>
