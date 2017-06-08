<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class GeoIp extends Model
{
    protected $table = 'tracker_geoip';

    protected $fillable = [
        'latitude',
        'longitude',
        'country_code',
        'country_name',
        'region',
        'city',
        'postal_code',
    ];
}
