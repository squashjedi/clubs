@php
    use App\Enums\Gender;
@endphp

@props([
    'gender'
])

<div class="flex items-center gap-1">
    @if ($gender !== Gender::Unknown)
        <span>{{ $gender->label() }}</span>
        <flux:icon
            name="{{ $gender->icon() }}"
            class="{{ $gender->color() }} size-5"
        />
    @else
        -
    @endif
</div>