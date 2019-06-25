<?php

namespace Railroad\Railtracker\Entities;

interface RailtrackerEntityInterface
{
    public function getHash();

    public function getKey();

    public function setFromData($data);

    public function setHash();

    public function allValuesAreEmpty();
}
