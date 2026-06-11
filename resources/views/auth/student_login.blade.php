<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <h2 class="text-lg font-bold text-gray-800 mb-1">Returning Students</h2>
    <p class="text-sm text-gray-500 mb-4">Log in to your student portal.</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
            <x-primary-button>{{ __('Log in') }}</x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t text-center">
        <p class="text-sm text-gray-600">New to the portal?</p>
        <a href="{{ route('student.register') }}" class="inline-block mt-2 text-sm font-bold text-emerald-700 hover:underline">
            Create a student account →
        </a>
    </div>
</x-guest-layout>
