<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Query extends Model
{
    protected $table = 'tracker_queries';

    protected $fillable = [
        'string',
    ];
}
