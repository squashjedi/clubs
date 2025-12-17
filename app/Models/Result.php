<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Result extends Model
{
    /** @use HasFactory<\Database\Factories\ResultFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'match_at' => 'datetime', // Carbon instance
    ];

    public function homeContestant(): BelongsTo
    {
        return $this->belongsTo(Contestant::class, 'home_contestant_id')->withTrashed();
    }

    public function awayContestant(): BelongsTo
    {
        return $this->belongsTo(Contestant::class, 'away_contestant_id')->withTrashed();
    }

    public function homePlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'home_player_id');
    }

    public function awayPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'away_player_id');
    }
}
