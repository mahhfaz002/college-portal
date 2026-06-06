<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $school['name'] ?? 'School Portal' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">

    <header class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex-shrink-0 flex items-center">
                    <span class="text-2xl font-bold text-indigo-800 tracking-tight">{{ $school['name'] ?? 'School Portal' }}</span>
                </div>

                <nav class="hidden md:flex space-x-8 font-medium text-gray-700">
                    <a href="/" class="hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-600 transition">Home</a>
                    <a href="{{ route('admission.form') }}" class="hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-600 transition">Prospective Students</a>
                    <a href="{{ route('login') }}" class="hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-600 transition">Student Login</a>
                    <a href="{{ route('login') }}" class="hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-600 transition">Admin Login</a>
                    <a href="/contact" class="hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-600 transition">Contact Us</a>
                </nav>

                <div class="md:hidden">
                    <button class="text-gray-600">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h3 class="text-xl font-bold mb-4">{{ $school['name'] ?? 'School Portal' }}</h3>
            <p class="text-gray-400 mb-6">{{ $school['tagline'] ?? 'Moulding the leaders of tomorrow with character and knowledge.' }}</p>
            <div class="flex justify-center space-x-6 text-2xl mb-6">
                <a href="#" class="hover:text-blue-400"><i class="fa-brands fa-facebook"></i></a>
                <a href="#" class="hover:text-pink-400"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" class="hover:text-blue-300"><i class="fa-brands fa-twitter"></i></a>
            </div>
            <p class="text-sm text-gray-500">&copy; 2026 Powered by YourSoftwareName</p>
        </div>
    </footer>
</body>
</html>
