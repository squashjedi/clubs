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
    wire:model.change="selectedClub"
    class="max-w-sm"
    size="sm"
>
    @foreach ($clubs as $club)
        <flux:select.option value="{{ $club->id }}">{{ $club->name }}</flux:select.option>
    @endforeach
</flux:select>