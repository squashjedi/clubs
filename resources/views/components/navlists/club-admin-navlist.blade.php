@props([
    'current' => false,
    'link',
    'icon',
    'rotate' => 0,
    'page',
])

<li>
    <!-- Current: "bg-white/5 text-white", Default: "text-gray-400 hover:text-white hover:bg-white/5" -->
    <a
        href="{{ $link }}"
        @class([
            'bg-white/5 text-white' => $current,
            'text-gray-400 hover:text-white hover:bg-white/5' => ! $current,
            'group flex gap-x-3 rounded-md p-2 text-sm/6 font-medium'
        ])
        wire:navigate
    >
    <flux:icon name="{{ $icon }}" class="rotate-{{ $rotate }}" />
    {{ $page }}
    </a>
</li>