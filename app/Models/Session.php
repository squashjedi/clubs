<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Session extends Model
{
    /** @use HasFactory<\Database\Factories\LeagueSessionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $table = 'league_sessions';

    protected $appends = ['active_period'];

    protected function casts(): array
    {
        return [
            'starting_at' => 'datetime',
            'ending_at' => 'datetime',
            'published_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    protected function activePeriod(): Attribute
    {
        return Attribute::make(
            get: function () {
                $startingAt = $this->starting_at;
                $endingAt = $this->ending_at;

                if ($startingAt->format('Y') === $endingAt->format('Y')) {
                    if ($startingAt->format('M Y') === $endingAt->format('M Y')) {
                        if ($startingAt->format('j M Y') === $endingAt->format('j M Y')) {
                            return "{$startingAt->format('j M Y')}";
                        }

                        return "{$startingAt->format('j')}-{$endingAt->format('j M Y')}";
                    }

                    return "{$startingAt->format('j M')} - {$endingAt->format('j M Y')}";
                }

                return "{$startingAt->format('j M Y')} - {$endingAt->format('j M Y')}";
            }
        );
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}
