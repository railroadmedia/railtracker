<?php

namespace Railroad\Railtracker\Entities;

abstract class RailtrackerEntity
{
    public static $KEY;

    public function getKey()
    {
        return self::$KEY;
    }
}
