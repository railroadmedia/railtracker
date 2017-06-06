<?php

namespace Railroad\Railtracker\Models;

class Path extends Base
{
    protected $table = 'tracker_paths';

    protected $fillable = [
        'path',
    ];
}
