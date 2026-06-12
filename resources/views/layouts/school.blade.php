<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ current_college()?->name ?? ($school['name'] ?? 'College Portal') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .hero-bg {
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1541829070776-ceec59b25f0c?q=80&w=1974&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

<nav class="bg-white shadow-md">
    <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <a href="{{ route('home') }}" class="text-2xl font-bold text-indigo-900 flex items-center gap-2">
            @php $brandLogo = current_college()?->logo_path ?? ($school['logo'] ?? null); @endphp
            @if($brandLogo)<img src="{{ media_url($brandLogo) }}" class="h-8 w-8 object-contain">@endif
            {{ current_college()?->name ?? ($school['name'] ?? 'College Portal') }}
        </a>

        <div class="space-x-4 flex items-center">
            <a href="{{ route('home') }}" class="text-gray-600 hover:text-indigo-600">Home</a>
            <a href="{{ route('about') }}" class="text-gray-600 hover:text-indigo-600">About</a>
            <a href="{{ route('admission.form') }}" class="text-gray-600 hover:text-indigo-600">Admission</a>
            <a href="{{ route('student.login') }}" class="text-gray-600 hover:text-indigo-600">Returning Students</a>
            <a href="{{ route('login') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-full hover:bg-indigo-700">Staff Login</a>
        </div>
    </div>
</nav>

    <main>
        @yield('content')
    </main>

    <footer class="bg-indigo-900 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p>&copy; {{ date('Y') }} {{ current_college()?->name ?? ($school['name'] ?? 'College Portal') }}. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>
