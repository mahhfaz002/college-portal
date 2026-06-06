@extends('layouts.school')

@section('content')
<div class="bg-gray-900 py-20 text-center text-white relative overflow-hidden">
    <div class="relative z-10">
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">Our Story & Mission</h1>
        <p class="mt-4 text-gray-400 max-w-2xl mx-auto px-4">Founded on the principles of excellence, integrity, and discipline.</p>
    </div>
    <div class="absolute top-0 left-0 w-full h-full opacity-20">
        <svg class="h-full w-full" fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none">
            <polygon points="0,100 100,0 100,100" />
        </svg>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 -mt-10 relative z-20">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 text-center">
            <div class="text-3xl font-bold text-indigo-600">15+</div>
            <div class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Years Experience</div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 text-center">
            <div class="text-3xl font-bold text-indigo-600">500+</div>
            <div class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Graduates</div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 text-center">
            <div class="text-3xl font-bold text-indigo-600">40+</div>
            <div class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Expert Teachers</div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 text-center">
            <div class="text-3xl font-bold text-indigo-600">100%</div>
            <div class="text-sm text-gray-500 uppercase tracking-wider font-semibold">WAEC Success</div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-6 py-20 grid md:grid-cols-2 gap-16 items-center">
    <div>
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Moulding Future Leaders</h2>
        <p class="text-gray-600 leading-relaxed mb-6">
            {{ $school['name'] ?? 'Our school' }} was established to bridge the gap between academic theory and practical life skills. Our environment is tailored to foster curiosity, critical thinking, and character development.
        </p>
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="bg-indigo-100 text-indigo-600 p-2 rounded-lg mr-4">
                    👁️
                </div>
                <div>
                    <h4 class="font-bold text-gray-800">Our Vision</h4>
                    <p class="text-gray-600 text-sm">To be the leading institution in Northern Nigeria for holistic education.</p>
                </div>
            </div>
            <div class="flex items-start">
                <div class="bg-green-100 text-green-600 p-2 rounded-lg mr-4">
                    🎯
                </div>
                <div>
                    <h4 class="font-bold text-gray-800">Our Mission</h4>
                    <p class="text-gray-600 text-sm">To provide a world-class learning experience that empowers students to compete globally.</p>
                </div>
            </div>
        </div>
    </div>
    <div class="rounded-3xl overflow-hidden shadow-2xl rotate-2 hover:rotate-0 transition-transform duration-500">
        <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=1470&auto=format&fit=crop" alt="Students learning" class="w-full">
    </div>
</div>


[Image of students learning in a classroom]

@endsection
