<div class="relative mb-6 w-full">
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Your Clubs') }}</flux:heading>
            <flux:subheading size="lg" class="mb-6">{{ __('Clubs you follow and administrate.') }}</flux:subheading>
        </div>
        <div class="flex justify-end">
            <flux:button
                :href="route('user.clubs.create')"
                variant="primary"
                wire:navigate
            >
              {{ __('Create a Club') }}
            </flux:button>
        </div>
    </div>
    <flux:separator variant="subtle" />
</div>
