<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $table = 'railtracker_languages';

    protected $fillable = ['preference', 'language_range'];
}
