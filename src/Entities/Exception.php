<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_exceptions")
 */
class Exception extends RailtrackerEntity implements RailtrackerEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=64, unique=true)
     */
    private $code;

    /**
     * @ORM\Column(type="bigint", unique=true)
     */
    private $line;

    /**
     * @ORM\Column(length=1064, name="exception_class")
     */
    private $exceptionClass;

    /**
     * @ORM\Column(length=1064)
     */
    private $file;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="text")
     */
    private $trace;

    /**
     * @ORM\Column(name="hash", length=128, unique=true)
     */
    protected $hash;

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code): void
    {
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * @param mixed $line
     */
    public function setLine($line): void
    {
        $this->line = $line;
    }

    /**
     * @return mixed
     */
    public function getExceptionClass()
    {
        return $this->exceptionClass;
    }

    /**
     * @param mixed $exceptionClass
     */
    public function setExceptionClass($exceptionClass): void
    {
        $this->exceptionClass = $exceptionClass;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file): void
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getTrace()
    {
        return $this->trace;
    }

    /**
     * @param mixed $trace
     */
    public function setTrace($trace): void
    {
        $this->trace = $trace;
    }

// ---------------------------------------------------------------------------------------------------------------------

    /**
     * @param $data
     * @return void
     */
    public function setFromData($data)
    {
        $this->setCode($data['code']);
        $this->setLine($data['line']);
        $this->setExceptionClass($data['exceptionClass']);
        $this->setFile($data['file']);
        $this->setMessage($data['message']);
        $this->setTrace($data['trace']);
    }

    /**
     * @return void
     */
    public function setHash()
    {
        $this->hash = md5(implode('-', [
            $this->getCode(),
            $this->getLine(),
            $this->getExceptionClass(),
            $this->getFile(),
            $this->getMessage(),
            $this->getTrace(),
        ]));
    }

    /**
     * @return boolean
     */
    public function allValuesAreEmpty()
    {
        return
            empty($this->getCode()) &&
            empty($this->getLine()) &&
            empty($this->getExceptionClass()) &&
            empty($this->getFile()) &&
            empty($this->getMessage()) &&
            empty($this->getTrace());
    }
}