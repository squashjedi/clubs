<?php

use App\Models\Club;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public Club $club;

    public int|string $selectedClub = '';

    public function mount()
    {
        $this->selectedClub = $this->club->id;
    }

    public function updatedSelectedClub($clubId)
    {
        $club = Auth::user()->clubsAdmin()->find($clubId);
        if (! $club) {
            return;
        }
        $this->redirectRoute('club.admin', [$club], navigate: true);
    }

    public function with(): array
    {
        return [
            'clubs' => Auth::user()->clubsAdmin,
        ];
    }
};
?>

<flux:select
    variant="listbox"
    wire:model.live="selectedClub"
    class="-ml-4 sm:-ml-6 lg:-ml-8 !max-w-80"
>
    @foreach ($clubs as $club)
        <flux:select.option value="{{ $club->id }}">{{ $club->name }}</flux:select.option>
    @endforeach
</flux:select>