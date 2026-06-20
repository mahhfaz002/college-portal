<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $brand   = $college->primary_color ?? '#4F46E5';
        $cname   = $college->name ?? 'Our College';
        $acr     = $college->acronym ?? 'College';
        $tagline = $college->tagline ?? 'Knowledge • Service • Excellence';
        $about   = $college->about ?? 'Training the next generation of professionals through quality education, modern facilities and hands-on practice.';
        $year    = $college->established_year ?? null;
    @endphp
    <title>{{ $cname }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($about), 150) }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root { --brand: {{ $brand }}; }
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .font-display { font-family: 'Fraunces', Georgia, serif; }
        .brand { color: var(--brand); }
        .bg-brand { background-color: var(--brand); }
        .border-brand { border-color: var(--brand); }
        .hero-grad { background: radial-gradient(120% 120% at 80% -10%, color-mix(in srgb, var(--brand) 55%, #0b1120) 0%, #0b1120 60%); }
        .blob { filter: blur(60px); opacity: .45; }
        /* scroll reveal */
        .reveal { opacity: 0; transform: translateY(24px); transition: opacity .6s ease-out, transform .6s ease-out; }
        .reveal.in { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1 !important; transform: none !important; transition: none; } .animate-blob { animation: none !important; } }
        @keyframes blob { 0%,100% { transform: translate(0,0) scale(1);} 33% { transform: translate(20px,-30px) scale(1.1);} 66% { transform: translate(-20px,20px) scale(.95);} }
        .animate-blob { animation: blob 14s ease-in-out infinite; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden">

{{-- ================= NAV ================= --}}
<nav x-data="{ open:false, scrolled:false }" @scroll.window="scrolled = window.scrollY > 20"
     class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
     :class="scrolled ? 'bg-white/90 backdrop-blur border-b border-slate-200 shadow-sm' : 'bg-transparent'">
    <div class="max-w-7xl mx-auto px-5 sm:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="#top" class="flex items-center gap-2.5 min-w-0">
                @if($college?->logo_path)
                    <img src="{{ media_url($college->logo_path) }}" alt="{{ $acr }}" class="h-9 w-9 rounded-lg object-contain bg-white p-0.5 shadow">
                @else
                    <span class="h-9 w-9 rounded-lg grid place-items-center text-white font-bold bg-brand shadow shrink-0">{{ \Illuminate\Support\Str::substr($acr,0,2) }}</span>
                @endif
                <span class="font-bold truncate" :class="scrolled ? 'text-slate-900' : 'text-white'">{{ $acr }}</span>
            </a>

            <div class="hidden md:flex items-center gap-7 text-sm font-semibold" :class="scrolled ? 'text-slate-600' : 'text-white/85'">
                <a href="#about" class="hover:opacity-70 transition">About</a>
                <a href="#programs" class="hover:opacity-70 transition">Programs</a>
                <a href="#calendar" class="hover:opacity-70 transition">Admissions</a>
                <a href="{{ route('login') }}" class="hover:opacity-70 transition">Staff Login</a>
                <a href="{{ route('student.login') }}" class="hover:opacity-70 transition">Students</a>
                <a href="{{ route('admission.form') }}" class="bg-brand text-white px-4 py-2 rounded-full shadow hover:brightness-110 active:scale-95 transition">Apply Now</a>
            </div>

            <button @click="open=!open" class="md:hidden p-2 -mr-2" :class="scrolled ? 'text-slate-800' : 'text-white'" aria-label="Menu" :aria-expanded="open">
                <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>
    {{-- mobile menu --}}
    <div x-show="open" x-cloak x-transition.origin.top class="md:hidden bg-white border-b border-slate-200 shadow-lg">
        <div class="px-5 py-4 flex flex-col gap-1 text-slate-700 font-semibold">
            <a href="#about" @click="open=false" class="py-2.5 border-b border-slate-100">About</a>
            <a href="#programs" @click="open=false" class="py-2.5 border-b border-slate-100">Programs</a>
            <a href="#calendar" @click="open=false" class="py-2.5 border-b border-slate-100">Admissions</a>
            <a href="{{ route('login') }}" class="py-2.5 border-b border-slate-100">Staff Login</a>
            <a href="{{ route('student.login') }}" class="py-2.5 border-b border-slate-100">Returning Students</a>
            <a href="{{ route('admission.form') }}" class="mt-2 bg-brand text-white text-center px-4 py-3 rounded-full shadow">Apply for Admission</a>
        </div>
    </div>
</nav>

{{-- ================= HERO ================= --}}
<header id="top" class="hero-grad relative overflow-hidden text-white">
    <div class="absolute -top-24 -right-10 w-96 h-96 rounded-full bg-brand blob animate-blob"></div>
    <div class="absolute bottom-0 -left-20 w-80 h-80 rounded-full bg-brand blob animate-blob" style="animation-delay:-6s"></div>
    <div class="relative max-w-7xl mx-auto px-5 sm:px-8 pt-32 pb-20 md:pt-44 md:pb-28">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 bg-white/10 ring-1 ring-white/20 backdrop-blur px-4 py-1.5 rounded-full text-xs sm:text-sm font-semibold tracking-wide">
                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>{{ $tagline }}
            </span>
            <h1 class="font-display mt-6 text-4xl sm:text-6xl md:text-7xl font-700 leading-[1.05] tracking-tight">{{ $cname }}</h1>
            <p class="mt-6 text-base sm:text-xl text-white/80 max-w-2xl leading-relaxed">{{ $about }}</p>
            <div class="mt-9 flex flex-col sm:flex-row gap-3">
                <a href="{{ route('admission.form') }}" class="inline-flex justify-center items-center gap-2 bg-white text-slate-900 font-bold px-7 py-3.5 rounded-full shadow-xl hover:-translate-y-0.5 active:scale-95 transition">
                    Apply for Admission
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
                </a>
                <a href="#programs" class="inline-flex justify-center items-center gap-2 bg-white/10 ring-1 ring-white/25 backdrop-blur text-white font-semibold px-7 py-3.5 rounded-full hover:bg-white/20 transition">View Programs</a>
            </div>
            {{-- stat chips --}}
            <dl class="mt-12 grid grid-cols-3 gap-4 max-w-lg">
                @php $stats = [
                    [$programs->count(), 'Programmes'],
                    [$programs->pluck('department.section')->filter()->unique()->count() ?: count(\App\Support\Sections::ALL), 'Sections'],
                    [$year ?? '—', 'Established'],
                ]; @endphp
                @foreach($stats as [$v,$l])
                    <div class="rounded-2xl bg-white/5 ring-1 ring-white/10 px-4 py-3">
                        <dd class="text-2xl sm:text-3xl font-extrabold">{{ $v }}</dd>
                        <dt class="text-[11px] sm:text-xs uppercase tracking-wide text-white/60 font-semibold mt-0.5">{{ $l }}</dt>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>
    <svg class="block w-full text-slate-50" viewBox="0 0 1440 80" preserveAspectRatio="none" fill="currentColor"><path d="M0 80h1440V20C1080 60 720 0 360 30 240 40 120 50 0 40z"/></svg>
</header>

{{-- ================= WHY US ================= --}}
<section id="about" class="max-w-7xl mx-auto px-5 sm:px-8 py-16 md:py-24">
    <div class="reveal text-center max-w-2xl mx-auto">
        <p class="brand font-bold uppercase tracking-widest text-xs">Why {{ $acr }}</p>
        <h2 class="font-display text-3xl md:text-4xl font-600 text-slate-900 mt-2">{{ $college->motto ?? 'A focused institution built for real-world practice' }}</h2>
    </div>
    <div class="mt-12 grid md:grid-cols-3 gap-6">
        @php $feats = [
            ['Modern Laboratories','Well-equipped science, microbiology and clinical-skills labs for genuine, hands-on learning.','M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.14.31.22.65.22 1z'],
            ['Accredited Programmes','Diploma and certificate programmes designed to meet national regulatory and professional standards.','M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['Experienced Faculty','Dedicated lecturers and clinical instructors committed to mentoring every student to success.','M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
        ]; @endphp
        @foreach($feats as $i => [$t,$d,$icon])
            <div class="reveal group bg-white rounded-3xl p-7 border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300" style="transition-delay: {{ $i*80 }}ms">
                <div class="w-12 h-12 rounded-2xl grid place-items-center text-white bg-brand shadow-md group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                </div>
                <h3 class="font-bold text-lg text-slate-900 mt-5">{{ $t }}</h3>
                <p class="text-slate-500 mt-2 leading-relaxed text-sm">{{ $d }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ================= PROGRAMS ================= --}}
<section id="programs" class="bg-white border-y border-slate-100">
    <div class="max-w-7xl mx-auto px-5 sm:px-8 py-16 md:py-24">
        <div class="reveal flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <p class="brand font-bold uppercase tracking-widest text-xs">Academics</p>
                <h2 class="font-display text-3xl md:text-4xl font-600 text-slate-900 mt-2">Programmes & Courses of Study</h2>
            </div>
            <a href="{{ route('admission.form') }}" class="text-sm font-bold brand hover:opacity-70 inline-flex items-center gap-1">Apply to a programme
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
            </a>
        </div>

        <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($programs as $p)
                <div class="reveal group rounded-2xl border border-slate-100 bg-slate-50/60 p-6 hover:bg-white hover:shadow-lg hover:border-slate-200 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wide brand">{{ $p->department->name ?? 'Department' }}</span>
                        @if($p->program_type)<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-900 text-white">{{ $p->program_type }}</span>@endif
                    </div>
                    <h3 class="font-bold text-slate-900 mt-2 leading-snug">{{ $p->name }}</h3>
                    <div class="flex flex-wrap gap-2 mt-4 text-[11px]">
                        @if($p->levels)<span class="bg-white border border-slate-200 text-slate-600 px-2 py-1 rounded-full font-semibold">{{ $p->levels }} level(s)</span>@endif
                        @if($p->application_fee > 0)<span class="bg-amber-50 text-amber-700 px-2 py-1 rounded-full font-semibold">App fee {{ money($p->application_fee) }}</span>@endif
                    </div>
                </div>
            @empty
                <div class="sm:col-span-2 lg:col-span-3 text-center py-14 rounded-2xl border border-dashed border-slate-200 text-slate-400">
                    Programmes will be published here soon.
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- ================= PROVOST ================= --}}
@if($provost['message'])
<section class="max-w-5xl mx-auto px-5 sm:px-8 py-16 md:py-24">
    <div class="reveal relative bg-slate-900 text-white rounded-[2rem] p-8 md:p-12 overflow-hidden">
        <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full bg-brand opacity-30 blur-3xl"></div>
        <div class="relative flex flex-col md:flex-row gap-8 items-start">
            <div class="shrink-0 text-center mx-auto md:mx-0">
                @if(!empty($provost['photo']))
                    <img src="{{ $provost['photo'] }}" alt="{{ $provost['name'] }}" class="w-28 h-28 rounded-2xl object-cover shadow-xl">
                @else
                    <div class="w-28 h-28 rounded-2xl grid place-items-center text-3xl font-display font-700 bg-brand text-white shadow-xl">
                        {{ \Illuminate\Support\Str::substr($provost['name'],0,1) }}
                    </div>
                @endif
                <p class="font-bold mt-3">{{ $provost['name'] }}</p>
                <p class="text-xs brand font-semibold">{{ $provost['title'] }}</p>
            </div>
            <div>
                <svg class="w-10 h-10 text-white/20" fill="currentColor" viewBox="0 0 24 24"><path d="M7.17 6A5.17 5.17 0 002 11.17V18h6.83v-6.83H5.5A1.67 1.67 0 017.17 9.5zM18.17 6A5.17 5.17 0 0013 11.17V18h6.83v-6.83H16.5A1.67 1.67 0 0118.17 9.5z"/></svg>
                <p class="text-lg md:text-2xl leading-relaxed font-display text-white/90 mt-2">{{ $provost['message'] }}</p>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ================= CALENDAR ================= --}}
<section id="calendar" class="bg-white border-y border-slate-100">
    <div class="max-w-4xl mx-auto px-5 sm:px-8 py-16 md:py-24">
        <div class="reveal text-center">
            <p class="brand font-bold uppercase tracking-widest text-xs">Admissions</p>
            <h2 class="font-display text-3xl md:text-4xl font-600 text-slate-900 mt-2">Key Dates & Timeline</h2>
        </div>
        <ol class="mt-12 relative border-l-2 border-slate-100 ml-3 space-y-7">
            @foreach($calendar as $i => $event)
                <li class="reveal pl-8 relative" style="transition-delay: {{ $i*60 }}ms">
                    <span class="absolute -left-[11px] top-1 w-5 h-5 rounded-full bg-brand ring-4 ring-white"></span>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <span class="font-semibold text-slate-800">{{ $event['title'] }}</span>
                        <span class="text-sm font-bold brand">{{ $event['date'] }}</span>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ================= CTA ================= --}}
<section class="max-w-7xl mx-auto px-5 sm:px-8 py-16 md:py-24">
    <div class="reveal hero-grad text-white rounded-[2rem] px-7 py-12 md:p-16 text-center relative overflow-hidden">
        <div class="absolute -bottom-20 -left-10 w-72 h-72 rounded-full bg-brand blob"></div>
        <div class="relative">
            <h2 class="font-display text-3xl md:text-5xl font-600">Begin your journey at {{ $acr }}</h2>
            <p class="text-white/75 mt-4 max-w-xl mx-auto">Complete your application online, pay securely, and track your admission every step of the way.</p>
            <a href="{{ route('admission.form') }}" class="mt-8 inline-flex items-center gap-2 bg-white text-slate-900 font-bold px-8 py-4 rounded-full shadow-xl hover:-translate-y-0.5 active:scale-95 transition">
                Start your application
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ================= FOOTER ================= --}}
<footer class="bg-slate-900 text-slate-300">
    <div class="max-w-7xl mx-auto px-5 sm:px-8 py-14 grid md:grid-cols-3 gap-10">
        <div>
            <div class="flex items-center gap-2.5">
                @if($college?->logo_path)<img src="{{ media_url($college->logo_path) }}" class="h-9 w-9 rounded-lg object-contain bg-white p-0.5">
                @else<span class="h-9 w-9 rounded-lg grid place-items-center text-white font-bold bg-brand">{{ \Illuminate\Support\Str::substr($acr,0,2) }}</span>@endif
                <span class="font-bold text-white">{{ $acr }}</span>
            </div>
            <p class="text-sm text-slate-400 mt-4 leading-relaxed max-w-xs">{{ $cname }}</p>
        </div>
        <div>
            <h4 class="text-white font-bold text-sm uppercase tracking-wide">Quick Links</h4>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a href="#about" class="hover:text-white transition">About</a></li>
                <li><a href="#programs" class="hover:text-white transition">Programmes</a></li>
                <li><a href="{{ route('admission.form') }}" class="hover:text-white transition">Apply for Admission</a></li>
                <li><a href="{{ route('student.login') }}" class="hover:text-white transition">Returning Students</a></li>
                <li><a href="{{ route('login') }}" class="hover:text-white transition">Staff Login</a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-white font-bold text-sm uppercase tracking-wide">Contact</h4>
            <ul class="mt-4 space-y-2 text-sm text-slate-400">
                @if($college?->address)<li>{{ $college->address }}</li>@endif
                @if($college?->phone)<li>{{ $college->phone }}</li>@endif
                @if($college?->email)<li><a href="mailto:{{ $college->email }}" class="hover:text-white">{{ $college->email }}</a></li>@endif
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-5 sm:px-8 py-5 text-xs text-slate-500 flex flex-col sm:flex-row justify-between gap-2">
            <span>© {{ date('Y') }} {{ $cname }}. All rights reserved.</span>
            <span>Powered by the College Management Platform</span>
        </div>
    </div>
</footer>

<script>
    // Scroll reveal (respects reduced-motion via CSS)
    (function () {
        const els = document.querySelectorAll('.reveal');
        if (!('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('in')); return; }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold: 0.12 });
        els.forEach(e => io.observe(e));
    })();
</script>
</body>
</html>
