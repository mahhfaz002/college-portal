@props(['solid' => false])
@php
    $cname = $school['name'] ?? 'College Portal';
    $logo  = $school['logo'] ?? null;
    $acr   = current_college()?->acronym ?? \Illuminate\Support\Str::substr($cname, 0, 2);
@endphp
<nav x-data="{ open:false, scrolled:false, solid:{{ $solid ? 'true' : 'false' }} }"
     @scroll.window="scrolled = window.scrollY > 20"
     class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
     :class="(solid || scrolled) ? 'bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm' : 'bg-transparent'">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 min-w-0">
                @if($logo)
                    <img src="{{ media_url($logo) }}" alt="{{ $acr }}" class="h-9 w-9 rounded-lg object-contain bg-white p-0.5 shadow">
                @else
                    <span class="h-9 w-9 rounded-lg grid place-items-center text-white font-bold bg-brand shadow shrink-0">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($acr,0,2)) }}</span>
                @endif
                <span class="font-bold truncate max-w-[10rem] sm:max-w-xs" :class="(solid || scrolled) ? 'text-slate-900' : 'text-white'">{{ $cname }}</span>
            </a>

            <div class="hidden md:flex items-center gap-7 text-sm font-semibold" :class="(solid || scrolled) ? 'text-slate-600' : 'text-white/85'">
                <a href="{{ route('home') }}" class="hover:opacity-70 transition">Home</a>
                <a href="{{ route('home') }}#programs" class="hover:opacity-70 transition">Programmes</a>
                <a href="{{ route('home') }}#calendar" class="hover:opacity-70 transition">Admissions</a>
                <a href="{{ route('login') }}" class="hover:opacity-70 transition">Staff Login</a>
                <a href="{{ route('student.login') }}" class="hover:opacity-70 transition">Students</a>
                <a href="{{ route('admission.form') }}" class="btn-brand px-4 py-2 rounded-full shadow">Apply Now</a>
            </div>

            <button @click="open=!open" class="md:hidden p-2 -mr-2" :class="(solid || scrolled) ? 'text-slate-800' : 'text-white'" aria-label="Menu" :aria-expanded="open">
                <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>
    <div x-show="open" x-cloak x-transition.origin.top class="md:hidden bg-white border-b border-slate-200 shadow-lg">
        <div class="px-5 py-4 flex flex-col gap-1 text-slate-700 font-semibold">
            <a href="{{ route('home') }}" @click="open=false" class="py-2.5 border-b border-slate-100">Home</a>
            <a href="{{ route('home') }}#programs" @click="open=false" class="py-2.5 border-b border-slate-100">Programmes</a>
            <a href="{{ route('home') }}#calendar" @click="open=false" class="py-2.5 border-b border-slate-100">Admissions</a>
            <a href="{{ route('login') }}" class="py-2.5 border-b border-slate-100">Staff Login</a>
            <a href="{{ route('student.login') }}" class="py-2.5 border-b border-slate-100">Returning Students</a>
            <a href="{{ route('admission.form') }}" class="mt-2 btn-brand text-center px-4 py-3 rounded-full shadow">Apply for Admission</a>
        </div>
    </div>
</nav>
