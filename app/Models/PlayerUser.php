<?php

namespace App\Models;

use App\Enums\PlayerRelationship;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PlayerUser extends Pivot
{
    protected $table = 'player_user';

    protected $casts = [
        'relationship' => PlayerRelationship::class,
    ];
}
