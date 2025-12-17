@php
use App\Enums\ModelStatus;
@endphp

<flux:radio.group wire:model.live="wireModel" variant="cards" :indicator="false" class="flex gap-4">
    <x-clubs.backend.indexs.radio value="{{ ModelStatus::Active }}" heading="Active" count="" />
    <x-clubs.backend.indexs.radio value="{{ ModelStatus::Trashed }}" heading="Archived" count="" />
    <x-clubs.backend.indexs.radio value="{{ ModelStatus::All }}" heading="All" count="" />
</flux:radio.group>