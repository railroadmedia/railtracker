<?php

namespace Railroad\Railtracker\ValueObjects;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

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

// todo: keep or delete?

//    /**
//     * @return array
//     */
//    public function returnArrayForDatabaseInteraction()
//    {
//        $array = [
//            'code' => $this->code,
//            'line' => $this->line,
//            'class' => $this->class,
//            'file' => $this->file,
//            'message' => $this->message,
//            'trace' => $this->trace,
//            'created_at' => ,
//        ];
//
//        return $array;
//    }
}
