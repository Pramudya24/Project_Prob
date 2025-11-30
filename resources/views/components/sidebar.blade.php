<div class="sidebar">
    <!-- Logo/Brand -->
    <div class="sidebar-header">
        <h3>SIVERA</h3>
    </div>

    <!-- Menu Items -->
    <div class="sidebar-menu">
        @foreach($menus as $menu)
            @if(empty($menu['children']))
                {{-- Menu tanpa submenu --}}
                <a href="{{ $menu['route'] ? route($menu['route']) : '#' }}" 
                   class="sidebar-item {{ request()->routeIs($menu['route']) ? 'active' : '' }}"
                   @if($menu['tooltip'])
                       data-tippy-content="{{ $menu['tooltip'] }}"
                   @endif
                   wire:navigate>
                    @if($menu['icon'])
                        <i class="{{ $menu['icon'] }}"></i>
                    @endif
                    <span>{{ $menu['name'] }}</span>
                </a>
            @else
                {{-- Group/Divider dengan submenu --}}
                <div class="sidebar-group">
                    <div class="sidebar-divider">
                        {{ $menu['name'] }}
                    </div>
                    
                    @foreach($menu['children'] as $child)
                        <a href="{{ route($child['route']) }}" 
                           class="sidebar-item {{ request()->routeIs($child['route']) ? 'active' : '' }}"
                           @if($child['tooltip'])
                               data-tippy-content="{{ $child['tooltip'] }}"
                           @endif
                           wire:navigate>
                            @if($child['icon'])
                                <i class="{{ $child['icon'] }}"></i>
                            @endif
                            <span>{{ $child['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
</div>

<style>
.sidebar {
    width: 250px;
    height: 100vh;
    background-color: #1a1a1a;
    color: #fff;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
}

.sidebar-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid #333;
}

.sidebar-header h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.sidebar-menu {
    padding: 10px 0;
}

.sidebar-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #b0b0b0;
    text-decoration: none;
    transition: all 0.3s;
}

.sidebar-item:hover {
    background-color: #2a2a2a;
    color: #fff;
}

.sidebar-item.active {
    background-color: #3b82f6;
    color: #fff;
}

.sidebar-item i {
    margin-right: 12px;
    font-size: 16px;
    width: 20px;
    text-align: center;
}

.sidebar-divider {
    padding: 15px 20px 8px;
    font-size: 11px;
    text-transform: uppercase;
    color: #666;
    font-weight: bold;
    letter-spacing: 1px;
}

.sidebar-group {
    margin-bottom: 10px;
}
</style>