<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\PlayerRelationship;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Player extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'gender' => Gender::class,
        'dob' => 'date',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}"
        );
    }

    protected function guardian(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->users()->wherePivot('relationship', PlayerRelationship::Guardian)->first()
        );
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class)
            ->using(ClubPlayer::class)
            ->withTimestamps()
            ->withPivot([
                'club_player_id',
                'deleted_at'
            ]);
    }

    public function contestants(): HasMany
    {
        return $this->hasMany(Contestant::class)->withTrashed();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(PlayerUser::class)
            ->withPivot('relationship');
    }

    public function isUser(User $user): bool
    {
        return $this->users->contains(function ($playerUser) use ($user) {
            return $playerUser->id === $user->id;
        });
    }

    public function restoreInClub($club)
    {
        $this->clubs()->updateExistingPivot($club->id, [
            'deleted_at' => null,
        ]);
    }

    public function archiveInClub($club)
    {
        $this->clubs()->updateExistingPivot($club->id, [
            'deleted_at' => now(),
        ]);
    }

    #[Scope]
    protected function orderByName(Builder $query)
    {
        return $query->orderBy('last_name')->orderBy('first_name');
    }

    #[Scope]
    protected function withClubMember(Builder $query, $club)
    {
        return $query->with('members', fn ($q) => $q->where('club_id', $club->id));
    }

    #[Scope]
    protected function withHasUser(Builder $query)
    {
        return $query->withExists('users as has_user');
    }
}
