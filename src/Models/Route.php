<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'railtracker_routes';

    protected $fillable = [
        'name',
        'action',
    ];
}
