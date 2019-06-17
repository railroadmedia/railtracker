<?php

namespace Railroad\Railtracker\Entities;

abstract class RailtrackerEntity
{
    public static $KEY;

    protected $hash;

    public function getKey()
    {
        return self::$KEY;
    }

    public function getHash()
    {
        return $this->hash;
    }
}
