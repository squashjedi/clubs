<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SportTallyUnit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = "sport_tally_unit";
}
