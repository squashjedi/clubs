<?php

use Flux\Flux;
use App\Models\Club;
use Livewire\Volt\Component;
use App\Rules\ForbiddenSlugs;
use Livewire\Attributes\Layout;
use App\Livewire\Forms\ClubForm;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.user')] class extends Component
{
    public ClubForm $form;
}; ?>

<section class="w-full">
    @include('partials.clubs-heading')

    <x-clubs.layout :heading="__('Create a Club')" :subheading="__('Complete the form to create a new club and become it\'s webmaster.')">
        <livewire:__components.forms.club-form :$form />
    </x-clubs.layout>
</section>
