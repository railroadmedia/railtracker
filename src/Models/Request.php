<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $table = 'tracker_requests';

    protected $fillable = [
        'uuid',
        'user_id',
        'url_id',
        'route_id',
        'device_id',
        'agent_id',
        'referer_url_id',
        'language_id',
        'geoip_id',
        'client_ip',
        'is_robot',
        'request_duration_ms',
        'request_time',
        'created_at',
        'updated_at',
    ];
}
