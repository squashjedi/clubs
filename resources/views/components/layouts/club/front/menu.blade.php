<flux:navlist {{ $attributes->merge(['class' => '']) }}>
    <flux:navlist.item :href="route('clubs.front', [ $club ])" :current="request()->routeIs('clubs.front')" wire:navigate>Home</flux:navlist.item>
</flux:navlist>