<?php

namespace App\Models;

use App\Models\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tier extends Model
{
    use HasFactory, Sortable;

    protected $guarded = [];

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'league_session_id');
    }

    public function sortableQuery($tier)
    {
        return $tier->session->tiers();
    }
}
