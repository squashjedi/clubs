<flux:navlist {{ $attributes->merge(['class' => '']) }}>
    <flux:navlist.item href="{{ route('club', [$club]) }}" :current="request()->routeIs('club')" wire:navigate>Home</flux:navlist.item>
</flux:navlist>