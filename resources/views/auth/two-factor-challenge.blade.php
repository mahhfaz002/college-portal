<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        For your security, we emailed a 6-digit verification code to
        <span class="font-semibold">{{ $email }}</span>. Enter it below to finish signing in.
        The code expires in 10 minutes.
    </div>

    @if (session('success'))
        <div class="mb-4 text-sm font-medium text-green-600">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf
        <div>
            <x-input-label for="code" :value="'Verification code'" />
            <x-text-input id="code" class="block mt-1 w-full tracking-[0.5em] text-center text-lg"
                          type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                          maxlength="6" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <x-primary-button>Verify &amp; continue</x-primary-button>
        </div>
    </form>

    <div class="flex items-center justify-between mt-4 text-sm">
        <form method="POST" action="{{ route('two-factor.resend') }}">
            @csrf
            <button type="submit" class="text-gray-600 underline hover:text-gray-900">Resend code</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-gray-600 underline hover:text-gray-900">Cancel &amp; log out</button>
        </form>
    </div>
</x-guest-layout>
