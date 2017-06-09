<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'railtracker_devices';

    protected $fillable = [
        'kind',
        'model',
        'platform',
        'platform_version',
        'is_mobile',
    ];
}
