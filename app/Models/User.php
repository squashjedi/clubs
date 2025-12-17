<?php

namespace App\Models;

use App\Enums\Gender;
use App\Traits\Helpers;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Helpers;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'gender',
        'dob',
        'tel_no',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'dob' => 'date',
            'gender' => Gender::class,
            'password' => 'hashed',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->first_name} {$this->last_name}"
        );
    }

    public function initials(): string
    {
        return Str::of($this->first_name)->substr(0, 1).Str::of($this->last_name)->substr(0, 1);
    }

    public function clubsAdmin(): HasMany
    {
        return $this->hasMany(Club::class);
    }

    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class)->withTimestamps();
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class)
            ->using(PlayerUser::class)
            ->withPivot('relationship');
    }

    public function playersNotInClub(Club $club)
    {
        return $this->players()
            ->whereDoesntHave('clubs', function ($q) use ($club) {
                $q->whereKey($club->id);
            })
            ->get();
    }
}