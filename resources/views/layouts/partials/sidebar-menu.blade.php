@php
    $routeName = request()->route()?->getName();
@endphp

<ul class="nav flex-column gap-1">
    @foreach($menuItems as $item)
        @php
            $indexPattern = str_ends_with($item['route'], '.index')
                ? str_replace('.index', '.*', $item['route'])
                : null;

            $isActive = request()->routeIs($item['route']) || ($indexPattern && request()->routeIs($indexPattern));
        @endphp

        <li class="nav-item">
            <a href="{{ route($item['route']) }}" class="nav-link panel-nav-link {{ $isActive ? 'active' : '' }}">
                {{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>
