<div class="md:hidden">
    <flux:button
        variant="filled"
        size="sm"
        href="{{ route('club.admin', [$club]) }}"
        icon-trailing="pencil"
        icon-variant="outline"
        wire:navigate
        />
    </div>
    <div class="hidden md:block">
        <flux:button
        variant="filled"
        size="sm"
        href="{{ route('club.admin', [$club]) }}"
        icon-trailing="pencil"
        icon-variant="outline"
        wire:navigate
    >Admin</flux:button>
</div>