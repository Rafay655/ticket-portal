<x-guest-layout>
    <img src="{{ asset('website/img/logo.png') }}" alt="HRPSP Logo" class="mb-4 mx-auto" style="width: 150px;">
    
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address, and we will email you a 6-digit OTP (valid for 2 minutes) that you can use to reset your password.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.send-otp') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <button class="py-2 w-full text-white rounded-full text-center" style="background-color:#0A3AA9">
                {{ __('Send OTP') }}
            </button>
        </div>
    </form>
</x-guest-layout>
