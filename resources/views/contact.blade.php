@extends('layouts.school')

@section('content')
<div class="bg-indigo-900 py-16 text-center text-white">
    <h1 class="text-4xl font-extrabold">Contact Our Team</h1>
    <p class="mt-2 text-indigo-200">Have questions? We are here to help.</p>
</div>

<div class="max-w-7xl mx-auto px-6 py-16 grid md:grid-cols-2 gap-12">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <h2 class="text-2xl font-bold mb-6 text-indigo-900">Send us a Message</h2>
        <form action="#" method="POST" class="space-y-4">
            @csrf
            <input type="text" placeholder="Your Name" class="w-full p-3 border rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
            <input type="email" placeholder="Email Address" class="w-full p-3 border rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
            <textarea placeholder="How can we help you?" rows="5" class="w-full p-3 border rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition">Send Message</button>
        </form>
    </div>

    <div class="space-y-8">
        <div>
            <h3 class="text-xl font-bold text-gray-800 mb-4">Visit Us</h3>
            <p class="text-gray-600">{{ setting('school_address', '—') }}</p>
        </div>
        <div>
            <h3 class="text-xl font-bold text-gray-800 mb-4">Call/Email</h3>
            <p class="text-gray-600">📞 {{ setting('school_phone', '—') }}</p>
            <p class="text-gray-600">✉️ {{ setting('school_email', '—') }}</p>
        </div>
        <div class="h-64 bg-gray-200 rounded-2xl overflow-hidden flex items-center justify-center border">
            <span class="text-gray-500">Google Map Placeholder</span>
        </div>
    </div>
</div>
@endsection
