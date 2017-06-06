<?php

namespace Railroad\Railtracker\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Symfony\Component\Console\Application;

class Base extends Eloquent
{
    protected $hidden = ['config'];
}
