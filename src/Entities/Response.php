<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_responses")
 */
class Response
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="Request")
     * @ORM\JoinColumn(name="request_id", referencedColumnName="id")
     */
    protected $request;

    /**
     * @ORM\ManyToOne(targetEntity="ResponseStatusCode")
     * @ORM\JoinColumn(name="status_code_id", referencedColumnName="id")
     */
    protected $statusCode;

    /**
     * @ORM\Column(type="bigint", name="response_duration_ms")
     */
    protected $responseDurationMs;

    /**
     * @ORM\Column(type="bigint", name="responded_on")
     */
    protected $respondedOn;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param mixed $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @return mixed
     */
    public function getResponseDurationMs()
    {
        return $this->responseDurationMs;
    }

    /**
     * @param mixed $responseDurationMs
     */
    public function setResponseDurationMs($responseDurationMs)
    {
        $this->responseDurationMs = $responseDurationMs;
    }

    /**
     * @return mixed
     */
    public function getRespondedOn()
    {
        return $this->respondedOn;
    }

    /**
     * @param mixed $respondedOn
     */
    public function setRespondedOn($respondedOn)
    {
        $this->respondedOn = $respondedOn;
    }
}
