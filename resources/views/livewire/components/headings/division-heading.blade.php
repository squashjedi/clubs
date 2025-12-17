<?php

use App\Models\Division;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public Division $division;

    #[On('tier-name-updated')]
    public function refreshDivisionName()
    { }
};
?>

<div class="relative">
    <x-headings.page-heading>{{ $this->division->name() }}</x-headings.page-heading>
    <div.flex wire:loading class="absolute inset-0 bg-white opacity-50" />
</div>

