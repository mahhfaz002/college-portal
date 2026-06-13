<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $school['name'] ?? 'College Portal' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>:root { --brand: {{ $school['color'] ?? '#4F46E5' }}; } body { font-family:'Plus Jakarta Sans',system-ui,sans-serif; } [x-cloak]{display:none!important;}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased overflow-x-hidden">

    <x-public-header :solid="true" />

    <main class="pt-16 min-h-[70vh]">
        @yield('content')
    </main>

    <footer class="bg-slate-900 text-slate-400 mt-16">
        <div class="max-w-7xl mx-auto px-6 py-8 text-center text-sm">
            &copy; {{ date('Y') }} {{ $school['name'] ?? 'College Portal' }}. All rights reserved.
        </div>
    </footer>
</body>
</html>
