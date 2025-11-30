<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SIVERA') }}</title>
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Component -->
        <x-sidebar />
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header/Topbar -->
            <header class="topbar">
                <div class="topbar-content">
                    <h2>{{ $title ?? 'Dashboard' }}</h2>
                    <div class="user-info">
                        <span>{{ Auth::user()->name ?? 'User' }}</span>
                        <button class="btn-logout">Logout</button>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="content">
                {{ $slot }}
            </main>
        </div>
    </div>
    
    @livewireScripts
</body>
</html>

<style>
.app-container {
    display: flex;
}

.main-content {
    margin-left: 250px;
    flex: 1;
    min-height: 100vh;
    background-color: #f5f5f5;
}

.topbar {
    background-color: #fff;
    padding: 15px 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.topbar-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.content {
    padding: 30px;
}
</style>