<div class="flex items-center justify-start gap-3 px-6 py-4">
    <!-- Logo -->
    <div class="flex-shrink-0">
        <img 
            src="{{ asset('images/logo.jpg') }}" 
            alt="SIVERA Logo" 
            class="h-10 w-10 object-contain"
            @if(file_exists(public_path('images/logo.jpg')))
                style="filter: brightness(0) invert(1);" <!-- Untuk logo putih di dark mode -->
            @endif
        />
    </div>
    
    <!-- Nama Aplikasi -->
    <div class="flex flex-col">
        <span class="text-xl font-bold tracking-tight text-white">
            SIVERA
        </span>
        @if(config('app.env') !== 'production')
            <span class="text-xs text-gray-300">
                {{ strtoupper(config('app.env')) }}
            </span>
        @endif
    </div>
</div>