<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SIVERA - Login</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .animate-fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="font-sans text-gray-900 antialiased">
    <!-- Background dengan Glass Effect -->
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0"
        style="background-image: url('/images/login-bg.jpg'); background-size: 100% 100%; background-position: center; background-repeat: no-repeat;">

        <!-- Glass Morphism Form -->
        <div class="w-full sm:max-w-md mt-6 px-8 py-10 bg-white/15 backdrop-blur-xl border border-white/20 shadow-2xl shadow-blue-500/20 overflow-hidden rounded-3xl">
            {{ $slot }}
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-black/90 font-medium backdrop-blur-sm px-4 py-2 rounded-full">
                &copy; {{ date('Y') }} SIVERA
            </p>
        </div>
    </div>
</body>

</html>