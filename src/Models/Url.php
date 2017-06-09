<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Url extends Model
{
    protected $table = 'railtracker_urls';

    protected $fillable = [
        'protocol_id',
        'domain_id',
        'path_id',
        'query_id',
    ];
}
