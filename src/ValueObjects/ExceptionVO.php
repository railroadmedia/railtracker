<?php

namespace Railroad\Railtracker\ValueObjects;

use Exception;

class ExceptionVO
{
    public $uuid;
    public $code;
    public $line;
    public $class;
    public $file;
    public $message;
    public $trace;

    /**
     * ExceptionVO constructor.
     * @param Exception $exception
     * @param $uuid
     */
    public function __construct(Exception $exception, $uuid)
    {
        $this->uuid = $uuid;
        $this->code = $exception->getCode();
        $this->line = $exception->getLine();
        $this->class = get_class($exception);
        $this->file = $exception->getFile();
        $this->message = $exception->getMessage();
        $this->trace = $exception->getTraceAsString();
    }
}
