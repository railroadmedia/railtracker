<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $table = 'tracker_agents';

    protected $fillable = [
        'name',
        'browser',
        'browser_version',
    ];
}
