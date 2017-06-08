<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Protocol extends Model
{
    protected $table = 'tracker_protocols';

    protected $fillable = [
        'protocol',
    ];
}
