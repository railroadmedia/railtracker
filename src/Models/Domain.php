<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $table = 'tracker_domains';

    protected $fillable = [
        'name',
    ];
}
