<?php

use App\Models\Club;
use Livewire\Component;

new class extends Component
{
    public $search = '';

    public function redirectClub(Club $club)
    {
        $this->redirectRoute('club', [$club], navigate: true);
    }

    protected function applySearch($query)
    {
        return $this->search === ''
            ? $query->where('name', 'zqjqoejqeorjeiprwdqwoeiqwdjw')
            : $query->where('name', 'like', '%'.$this->search.'%')->orderBy('name');
    }

    public function with(): array
    {
        $query = Club::query()->orderBy('name');

        $query = $this->applySearch($query);

        return [
            'clubs' => $query->limit(9)->get(),
        ];
    }


}; ?>

<div>
    <flux:modal.trigger name="search">
        <flux:button variant="subtle" icon="magnifying-glass" class="cursor-pointer" />
    </flux:modal.trigger>

    <flux:modal name="search" variant="bare" class="w-full max-w-[30rem] my-[12vh] max-h-screen overflow-y-hidden">
        <flux:command class="border-none shadow-lg inline-flex flex-col max-h-[76vh]">
            <flux:command.input :filter="false" wire:model.live.debounce.500ms="search" autofocus placeholder="Find a club..." closable class="!py-8 !text-lg" />

            @if ($clubs->count() > 0 || !empty($search))
                <flux:command.items>
                    @foreach ($clubs as $club)
                        <flux:command.item wire:key="{{ $club->id }}" wire:click="redirectClub('{{ $club->slug }}')">
                            {{ $club->name }}
                        </flux:command.item>
                    @endforeach
                </flux:command.items>
            @endif
        </flux:command>
    </flux:modal>
</div>