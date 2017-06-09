<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $table = 'railtracker_domains';

    protected $fillable = [
        'name',
    ];
}
