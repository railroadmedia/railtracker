<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_request_exceptions")
 */
class RequestException
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Exception", cascade={"persist"})
     */
    private $exception;

    /**
     * @ORM\OneToOne(targetEntity="Request", cascade={"persist"})
     */
    private $request;

    /**
     * @ORM\Column(type="bigint", name="created_at_timestamp_ms", unique=true)
     */
    private $createdAtTimestampMs;

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
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param mixed $exception
     */
    public function setException($exception): void
    {
        $this->exception = $exception;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request): void
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getCreatedAtTimestampMs()
    {
        return $this->createdAtTimestampMs;
    }

    /**
     * @param mixed $createdAtTimestampMs
     */
    public function setCreatedAtTimestampMs($createdAtTimestampMs): void
    {
        $this->createdAtTimestampMs = $createdAtTimestampMs;
    }
}
