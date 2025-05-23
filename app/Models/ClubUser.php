<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClubUser extends Pivot
{
    use HasFactory;

    protected $table = 'club_user';

    protected function casts(): array
    {
        return [
            'approved' => Status::class,
        ];
    }
}
