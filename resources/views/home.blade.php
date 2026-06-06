@extends('layouts.school')

@section('content')
    <div class="relative bg-gradient-to-r from-indigo-800 via-purple-700 to-pink-600 text-white py-32 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="absolute left-0 top-0 h-full w-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                <polygon fill="white" points="0,0 50,0 0,100"/>
            </svg>
        </div>

        <div class="relative max-w-7xl mx-auto px-6 text-center">
            <span class="inline-block bg-white/20 text-white text-sm font-semibold px-4 py-1.5 rounded-full mb-4 backdrop-blur-sm">
                {{ $school['tagline'] ?? 'Knowledge • Integrity • Excellence' }}
            </span>
            <h2 class="text-6xl md:text-7xl font-extrabold mb-6 leading-tight tracking-tight">
                Welcome to <span class="text-yellow-300">{{ $school['name'] ?? 'Our School' }}</span>
            </h2>
            <p class="text-xl md:text-2xl mb-12 max-w-3xl mx-auto text-indigo-100">
                Empowering the next generation through world-class education, modern facilities, and holistic character development.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('admission.form') }}" class="bg-yellow-400 text-indigo-950 px-10 py-4 rounded-full font-bold text-lg hover:bg-white transition duration-300 transform hover:-translate-y-1 shadow-2xl">
                    Apply for Admission
                </a>
                <a href="#about" class="bg-indigo-950/50 text-white px-10 py-4 rounded-full font-semibold text-lg hover:bg-indigo-950 transition duration-300 backdrop-blur-sm">
                    Discover More
                </a>
            </div>
        </div>
    </div>

    <div id="about" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h3 class="text-4xl font-bold text-gray-900 mb-4">Why Choose {{ $school['name'] ?? 'Us' }}?</h3>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">We provide a nurturing environment where every child is challenged to achieve their highest potential.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-10">
                <div class="bg-white p-8 rounded-3xl shadow-xl border-t-4 border-yellow-400 transform transition hover:scale-105 duration-300">
                    <div class="bg-yellow-100 text-yellow-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6">🏆</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Academic Excellence</h3>
                    <p class="text-gray-600 leading-relaxed">Our British-Nigerian curriculum is designed to foster critical thinking, creativity, and a lifelong passion for learning.</p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-xl border-t-4 border-indigo-500 transform transition hover:scale-105 duration-300">
                    <div class="bg-indigo-100 text-indigo-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6">💡</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Modern Facilities</h3>
                    <p class="text-gray-600 leading-relaxed">From fully equipped science labs to modern ICT centers, we provide the tools necessary for 21st-century education.</p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-xl border-t-4 border-pink-500 transform transition hover:scale-105 duration-300">
                    <div class="bg-pink-100 text-pink-600 w-16 h-16 rounded-2xl flex items-center justify-center text-3xl mb-6">🎨</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Holistic Growth</h3>
                    <p class="text-gray-600 leading-relaxed">We believe in education beyond the classroom, offering robust sports, arts, and leadership programs.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
