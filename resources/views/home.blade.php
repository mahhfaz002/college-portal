@extends('layouts.school')

@section('content')
    {{-- HERO --}}
    <div class="relative bg-gradient-to-r from-blue-900 via-indigo-800 to-emerald-700 text-white py-28 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="absolute left-0 top-0 h-full w-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                <polygon fill="white" points="0,0 50,0 0,100"/>
            </svg>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <span class="inline-block bg-white/20 text-white text-sm font-semibold px-4 py-1.5 rounded-full mb-4 backdrop-blur-sm">
                {{ $college->tagline ?? ($school['tagline'] ?? 'Knowledge • Service • Excellence') }}
            </span>
            <h2 class="text-4xl md:text-6xl font-extrabold mb-6 leading-tight tracking-tight">
                {{ $college->name ?? ($school['name'] ?? 'Our College') }}
            </h2>
            <p class="text-lg md:text-2xl mb-10 max-w-3xl mx-auto text-indigo-100">
                {{ $college->about ?? 'Training the next generation of professionals through quality education, modern facilities and hands-on practice.' }}
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('admission.form') }}" class="bg-yellow-400 text-indigo-950 px-10 py-4 rounded-full font-bold text-lg hover:bg-white transition duration-300 transform hover:-translate-y-1 shadow-2xl">
                    Apply for Admission
                </a>
                <a href="#programs" class="bg-indigo-950/50 text-white px-10 py-4 rounded-full font-semibold text-lg hover:bg-indigo-950 transition duration-300 backdrop-blur-sm">
                    View Programs
                </a>
            </div>
        </div>
    </div>

    {{-- WHY CHOOSE US --}}
    <div id="about" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-14">
                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Why {{ $college->acronym ?? 'Choose Us' }}?</h3>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ $college->motto ?? 'A focused institution combining academic rigour with practical, profession-ready training.' }}</p>
            </div>

            <div class="grid md:grid-cols-3 gap-10">
                <div class="bg-white p-8 rounded-3xl shadow-xl border-t-4 border-emerald-500">
                    <div class="bg-emerald-100 text-emerald-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6">🔬</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Modern Laboratories</h3>
                    <p class="text-gray-600 leading-relaxed">Well-equipped science, microbiology and clinical skills
                        laboratories for real, hands-on learning.</p>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-xl border-t-4 border-indigo-500">
                    <div class="bg-indigo-100 text-indigo-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6">🩺</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Accredited Programs</h3>
                    <p class="text-gray-600 leading-relaxed">ND, HND and certificate programs designed to meet national
                        regulatory and professional standards.</p>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-xl border-t-4 border-yellow-400">
                    <div class="bg-yellow-100 text-yellow-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6">👩‍🏫</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Experienced Faculty</h3>
                    <p class="text-gray-600 leading-relaxed">Dedicated lecturers and clinical instructors committed to
                        mentoring every student to success.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- PROGRAMS / SAMPLE COURSES --}}
    <div id="programs" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-14">
                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Our Programs</h3>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">Explore the courses and programs offered across our departments.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($programs as $program)
                    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hover:shadow-lg transition">
                        <span class="text-xs font-bold uppercase tracking-wide text-emerald-600">
                            {{ $program->department->name ?? 'Department' }}
                        </span>
                        <h4 class="text-xl font-bold text-gray-900 mt-1">{{ $program->name }}</h4>
                        <div class="flex flex-wrap gap-2 mt-3 text-xs">
                            @if($program->level_system)
                                <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full font-semibold">{{ $program->level_system }}</span>
                            @endif
                            <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded-full font-semibold">{{ $program->duration_years }} yr(s)</span>
                            @if($program->application_fee > 0)
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full font-semibold">App. fee {{ money($program->application_fee) }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    {{-- Fallback sample programs if none seeded yet. --}}
                    @foreach([
                        ['Community Health', 'ND Community Health Extension Worker', 'Certificate'],
                        ['Science Laboratory Technology', 'ND Science Laboratory Technology', 'ND'],
                        ['Public Health', 'ND Environmental Health Technology', 'ND'],
                        ['Nursing Sciences', 'ND Nursing (proposed)', 'ND'],
                        ['Pharmacy Technician', 'ND Pharmacy Technician', 'ND'],
                        ['Health Information Mgt.', 'ND Health Information Management', 'ND'],
                    ] as [$dept, $name, $level])
                        <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 hover:shadow-lg transition">
                            <span class="text-xs font-bold uppercase tracking-wide text-emerald-600">{{ $dept }}</span>
                            <h4 class="text-xl font-bold text-gray-900 mt-1">{{ $name }}</h4>
                            <span class="inline-block mt-3 bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full text-xs font-semibold">{{ $level }}</span>
                        </div>
                    @endforeach
                @endforelse
            </div>
        </div>
    </div>

    {{-- PROVOST'S MESSAGE --}}
    <div class="py-20 bg-gradient-to-br from-indigo-50 to-emerald-50">
        <div class="max-w-5xl mx-auto px-6">
            <div class="bg-white rounded-3xl shadow-xl p-8 md:p-12 flex flex-col md:flex-row gap-8 items-start">
                <div class="shrink-0 mx-auto md:mx-0">
                    <div class="w-32 h-32 rounded-2xl bg-gradient-to-br from-indigo-600 to-emerald-500 text-white flex items-center justify-center text-5xl">👩‍⚕️</div>
                    <p class="text-center mt-3 font-bold text-gray-900">{{ $provost['name'] }}</p>
                    <p class="text-center text-sm text-emerald-600 font-semibold">{{ $provost['title'] }}</p>
                </div>
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">Message from the Provost</h3>
                    <p class="text-gray-600 leading-relaxed text-lg">“{{ $provost['message'] }}”</p>
                </div>
            </div>
        </div>
    </div>

    {{-- CALENDAR OF EVENTS --}}
    <div class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-14">
                <h3 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Academic Calendar &amp; Admission Periods</h3>
                <p class="text-lg text-gray-600">Key dates for the 2026/2027 academic session.</p>
            </div>

            <div class="space-y-4">
                @foreach($calendar as $event)
                    <div class="flex items-center justify-between bg-gray-50 rounded-xl px-6 py-4 border-l-4 border-emerald-500">
                        <div class="flex items-center gap-4">
                            <span class="text-2xl">📅</span>
                            <span class="font-semibold text-gray-800">{{ $event['title'] }}</span>
                        </div>
                        <span class="text-sm font-bold text-indigo-700 bg-indigo-100 px-3 py-1 rounded-full">{{ $event['date'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('admission.form') }}" class="inline-block bg-emerald-600 text-white px-10 py-4 rounded-full font-bold text-lg hover:bg-emerald-700 transition shadow-lg">
                    Start Your Application
                </a>
            </div>
        </div>
    </div>
@endsection
