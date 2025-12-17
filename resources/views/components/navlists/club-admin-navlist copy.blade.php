<flux:sidebar.nav>
    <flux:sidebar.item icon="home" href="{{ route('club.admin', [$club]) }}" :current="request()->routeIs('club.admin')" wire:navigate>Dashboard</flux:sidebar.item>
    <flux:sidebar.item icon="settings-2" href="{{ route('club.admin.profile', [$club]) }}" :current="request()->routeIs('club.admin.profile')" wire:navigate>Profile</flux:sidebar.item>
    <flux:sidebar.item icon="users" href="{{ route('club.admin.members', [$club]) }}" :current="request()->routeIs('club.admin.members')" wire:navigate>Members</flux:sidebar.item>
    <flux:sidebar.item icon="rows-3" href="{{ route('club.admin.leagues', [$club]) }}" :current="request()->routeIs('club.admin.leagues')" wire:navigate>Leagues</flux:sidebar.item>
</flux:sidebar.nav>