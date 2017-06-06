<?php

namespace Railroad\Railtracker\Models;

class Domain extends Base
{
    protected $table = 'tracker_domains';

    protected $fillable = [
        'name',
    ];
}
