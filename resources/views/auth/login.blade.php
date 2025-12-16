<x-guest-layout>
    <!-- Animated Header -->
    <div class="mb-10 text-center animate-fade-in">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm mb-4 p-3">
            <img src="/images/newlogo.png" alt="Logo Perusahaan" class="w-full h-full object-contain">
        </div>
        <h1 class="text-3xl font-bold text-white mb-2">SIVERA</h1>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-white" />
            <div class="mt-1 relative rounded-lg shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                </div>
                <x-text-input id="email"
                    class="block w-full pl-10 bg-white/10 border-white/20 text-white placeholder-gray-300 focus:border-blue-400 focus:ring-blue-400"
                    type="email" name="email" :value="old('email')" required autofocus
                    placeholder="you@example.com" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-300" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="text-white" />
            <div class="mt-1 relative rounded-lg shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <x-text-input id="password"
                    class="block w-full pl-10 bg-white/10 border-white/20 text-white placeholder-gray-300 focus:border-blue-400 focus:ring-blue-400"
                    type="password" name="password" required autocomplete="current-password"
                    placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-300" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded bg-white/10 border-white/20 text-blue-500 focus:ring-blue-400" name="remember">
                <span class="ms-2 text-sm text-gray-200">{{ __('Remember me') }}</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-4">
            <button type="submit"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-400 transition duration-200">
                {{ __('Sign in') }}
            </button>
        </div>
    </form>
</x-guest-layout>