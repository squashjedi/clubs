<?php

namespace App\Models;

use App\Models\Invitation;
use App\Enums\PlayerRelationship;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}"
        );
    }

    protected function guardian(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->player->users()->wherePivot('relationship', PlayerRelationship::Guardian)->first()
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function hasUser()
    {
        return $this->player->users()->exists();
    }


    public function invitation(): HasOne
    {
        return $this->hasOne(Invitation::class);
    }

    public function contestants(): HasMany
    {
        return $this->hasMany(Contestant::class)->withTrashed();
    }

    public function entrants(): HasMany
    {
        return $this->hasMany(Entrant::class);
    }

    public function hasCompeted()
    {
        return $this->entrants()->exists() || $this->contestants()->exists();
    }

    #[Scope]
    protected function sortByName(Builder $query): void
    {
        $query->orderBy('last_name')->orderBy('first_name');
    }

    #[Scope]
    protected function orderByName(Builder $query): void
    {
        $query->join('players', 'players.id', '=', 'members.player_id')
            ->with(['player' => fn ($q) => $q->withHasUser()])
            ->orderBy('players.last_name')
            ->orderBy('players.first_name')
            ->select('members.player_id', 'players.id', 'members.club_member_id');
    }

    public function permanentlyDelete()
    {
        DB::transaction(function () {
            Invitation::where('member_id', $this->id)->forceDelete();
            $this->forceDelete();
        });
    }

    public function isAssigned()
    {
        return $this->user_id !== null;
    }

    public function assign(User $user)
    {
        $this->update([
            'user_id' => $user->id,
        ]);
    }

    public function unassign()
    {
        $this->update([
            'user_id' => null,
        ]);
    }
}
