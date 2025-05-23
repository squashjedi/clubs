<?php

namespace App\Models;

use App\Traits\Helpers;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Club extends Model
{
    use HasFactory, HasSlug, Helpers;

    protected $guarded = [];

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function sports(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }

    public function user($user_id): User | null
    {
        return $this->users()->wherePivot('user_id', $user_id)->first();
    }

    public function join($user_id)
    {
        return $this->users()->attach($user_id);
    }

    public function authJoin()
    {
        return $this->join(auth()->id());
    }

    public function isAuthUser(): bool
    {
        return $this->user(auth()->id()) !== null;
    }

    public function leave($user_id)
    {
        return $this->users()->detach($user_id);
    }

    public function authLeave()
    {
        return $this->leave(auth()->id());
    }
}
