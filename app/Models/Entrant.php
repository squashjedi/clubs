<?php

namespace App\Models;

use App\Models\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entrant extends Model
{
    use HasFactory, Sortable;

    protected $guarded = [];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'league_session_id');
    }

    #[Scope]
    protected function orderByName(Builder $query, $club): void
    {
        $query->join('players', 'players.id', '=', 'entrants.player_id')
            ->with([
                'player' => fn ($q) => $q->withHasUser()->withClubMember($club)
            ])
            ->orderBy('players.last_name')
            ->orderBy('players.first_name');
    }

    public function sortableQuery($entrant)
    {
        return $entrant->session->entrants()->with('member');
    }
}
