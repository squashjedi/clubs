@props([
    'collectionName',
])

<div class="flex flex-col items-center justify-center gap-6 mt-10">
    <flux:icon.x-mark />
    <div class="text-base text-gray-800 font-bold">No {{ $collectionName }}</div>
</div>