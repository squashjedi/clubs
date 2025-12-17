<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')]class extends Component
{
    //
};
?>

<div>
    @foreach (range(1, 1000) as $i)
        <flux:button wire:key="{{ $i }}">testing</flux:button>
    @endforeach
</div>