<x-guest-layout>
    <img src="{{ asset('website/img/logo.png') }}" alt="HRPSP Logo" class="mb-4 mx-auto" style="width: 150px;">
    <div class=" mb-5">
        <h2 class="fw-bold " style="font-size:26px">Reset Your <span class="text-primary">Password.</span></h2>
    </div>
    <form method="POST" action="{{ route('password.reset') }}">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                name="password_confirmation" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-start mt-4">
        <button class=" py-2 w-full text-white rounded-full text-center" style="background-color:#0A3AA9">Reset Password</button>
        </div>
    </form>
</x-guest-layout>
