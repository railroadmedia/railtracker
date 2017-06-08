<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $table = 'tracker_routes';

    protected $fillable = [
        'name',
        'action',
    ];
}
