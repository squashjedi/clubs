<div>
    @if ($approved)
        <flux:badge
            size="sm"
            color="green"
            variant="pill"
            icon-trailing="check"
        >
            Approved
        </flux:badge>
    @else
        <flux:badge
            size="sm"
            color="amber"
            variant="pill"
            icon-trailing="hourglass"
        >
            Pending
        </flux:badge>
    @endif
</div>