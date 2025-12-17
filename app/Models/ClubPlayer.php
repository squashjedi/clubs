<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClubPlayer extends Pivot
{
    use SoftDeletes;

    protected $table = 'club_player';
}
