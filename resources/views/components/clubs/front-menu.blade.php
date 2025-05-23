<flux:navlist {{ $attributes->merge(['class' => '']) }}>
{{--     <flux:navlist.item :href="route('groups.front.home', [ $club->group ])" :current="request()->routeIs('groups.front.home')" wire:navigate>Home</flux:navlist.item>
    <flux:navlist.item :href="route('groups.clubs.front.followers', [ $club ])" :current="request()->routeIs('groups.clubs.front.followers') || request()->routeIs('groups.clubs.front.followers.user')" wire:navigate>Followers</flux:navlist.item>
 --}}    <flux:navlist.item href="#" wire:navigate>Bookings</flux:navlist.item>
    <flux:navlist.item href="#" wire:navigate>Leagues</flux:navlist.item>
</flux:navlist>