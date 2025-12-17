<?php

namespace App\Models;

use App\Models\Club;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class League extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'template' => 'array',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function tallyUnit(): BelongsTo
    {
        return $this->belongsTo(TallyUnit::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function latestSession(): HasOne
    {
        return $this->hasOne(Session::class)->latestOfMany();
    }

    public function lastSession(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    #[Scope]
    protected function mostRecentSession(Builder $query): void
    {
        $query->addSelect(['last_session_id' => Session::select('id')
            ->whereColumn('league_id', 'leagues.id')
            ->latest()
            ->take(1),
        ]);
    }

    #[Scope]
    protected function mostRecentEndsAt(Builder $query): void
    {
        $query->addSelect(['ends_at' => Session::select('ends_at')
            ->whereColumn('league_id', 'leagues.id')
            ->latest()
            ->take(1),
        ]);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->with('sessions')
            ->firstOrFail();
    }

}
