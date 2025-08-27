@props([
    'title' => '',
    'items' => []
])

@if(!empty($items))
    <div class="nav-section">
        @if($title)
            <div class="nav-section-title">{{ $title }}</div>
        @endif
        
        @foreach($items as $item)
            @include('components.nav-item', [
                'route' => $item['route'] ?? '',
                'icon' => $item['icon'] ?? 'fas fa-circle',
                'label' => $item['label'] ?? '',
                'permission' => $item['permission'] ?? null,
                'badge' => $item['badge'] ?? null,
                'badgeColor' => $item['badgeColor'] ?? 'danger'
            ])
        @endforeach
    </div>
@endif

<style>
.nav-section {
    margin: 15px 0;
}

.nav-section-title {
    color: rgba(255,255,255,0.6);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 15px 8px 15px;
    padding: 5px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.nav-section:first-child .nav-section-title {
    margin-top: 0;
}
</style>
