<flux:header container class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
    <div class="flex items-center">

        <div class="">
            <flux:brand :href="route('dashboard')" logo="https://fluxui.dev/img/demo/logo.png" name="{{ config('app.name') }}" class="dark:hidden" wire:navigate />
            <flux:brand :href="route('dashboard')" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="{{ config('app.name') }}" class="hidden dark:flex" wire:navigate />
        </div>
    </div>
    
    <flux:spacer />
    
    <div class="flex items-center gap-3">
        <livewire:__components.header-search />

        <flux:separator vertical class="my-3" />

        <x-auth-dropdown-menu />
    </div>
</flux:header>
