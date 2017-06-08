<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Path extends Model
{
    protected $table = 'tracker_paths';

    protected $fillable = [
        'path',
    ];
}
