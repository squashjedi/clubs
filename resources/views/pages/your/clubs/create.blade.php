<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.user')] class extends Component
{
    //
}; ?>

<section class="w-full">
    @include('partials.clubs-heading')

    <x-clubs.layout :heading="__('Create a Club')" :subheading="__('Complete the form to create a new club and become it\'s webmaster.')">
        <livewire:formz.club-form />
    </x-clubs.layout>
</section>
