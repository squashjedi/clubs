<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contestant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

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

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function notify()
    {
        $this->update([
            'notified_at' => now()
        ]);
    }

    #[Scope]
    protected function notNotified(Builder $query): void
    {
        $query->whereNull('notified_at');
    }

    public function deleteFromDivision()
    {
        $division = $this->division;

        $division->results()
            ->where(function (Builder $query) {
                return $query
                    ->where('home_player_id', $this->id)
                    ->OrWhere('away_player_id', $this->id);
            })
            ->get()
            ->each(function ($result) {
                $result->forceDelete();
            });

        $this->forceDelete();
    }
}
