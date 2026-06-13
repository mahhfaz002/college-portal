<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $school['name'] ?? 'College Portal' }}</title>
        <style>:root { --brand: {{ $school['color'] ?? '#4F46E5' }}; } [x-cloak]{display:none!important;}</style>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased overflow-x-hidden">
        <x-public-header :solid="true" />

        <div class="min-h-screen flex flex-col justify-center items-center px-4 pt-24 pb-10 bg-gradient-to-b from-slate-50 to-slate-100">
            <div class="flex flex-col items-center text-center">
                <a href="/" class="flex flex-col items-center">
                    @if($school['logo'])
                        <img src="{{ media_url($school['logo']) }}" alt="Logo" class="w-16 h-16 object-contain rounded-xl bg-white p-1 shadow">
                    @else
                        <span class="w-16 h-16 rounded-2xl grid place-items-center text-white text-2xl font-bold bg-brand shadow">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(current_college()?->acronym ?? ($school['name'] ?? 'CP'), 0, 2)) }}
                        </span>
                    @endif
                </a>
                <h1 class="mt-4 text-xl font-bold text-slate-800">{{ $school['name'] ?? 'College Portal' }}</h1>
                @if(!empty($school['tagline']))
                    <p class="text-xs text-slate-500 mt-1">{{ $school['tagline'] }}</p>
                @endif
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-xl border border-slate-100 overflow-hidden rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
