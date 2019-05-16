<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_requests")
 */
class Request
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
    private $uuid;

    /**
     * @ORM\Column(type="bigint", name="user_id", nullable=true)
     */
    private $userId;

    /**
     * @ORM\Column(name="cookie_id", length=64, nullable=true)
     */
    private $cookieId;

    /**
     * @ORM\ManyToOne(targetEntity="Url")
     */
    private $url;

    /**
     * @ORM\ManyToOne(targetEntity="Route")
     */
    private $route;

    /**
     * @ORM\ManyToOne(targetEntity="RequestDevice")
     */
    private $device;

    /**
     * @ORM\ManyToOne(targetEntity="RequestAgent")
     */
    private $agent;

    /**
     * @ORM\ManyToOne(targetEntity="RequestMethod")
     */
    private $method;

    /**
     * @ORM\ManyToOne(targetEntity="RequestLanguage")
     */
    private $language;

    /**
     * @ORM\ManyToOne(targetEntity="Url")
     */
    private $refererUrl;

    /**
     * @ORM\Column(type="bigint", name="geoip_id", nullable=true)
     */
    private $geoip;

    /**
     * @ORM\Column(name="client_ip", length=64)
     */
    private $clientIp;

    /**
     * @ORM\Column(type="boolean", name="is_robot")
     */
    private $isRobot;

    /**
     * @ORM\Column(type="datetime", name="requested_on")
     */
    private $requestedOn;

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
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getCookieId()
    {
        return $this->cookieId;
    }

    /**
     * @param mixed $cookieId
     */
    public function setCookieId($cookieId)
    {
        $this->cookieId = $cookieId;
    }

    /**
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param mixed $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @return RequestDevice
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param mixed $device
     */
    public function setDevice($device)
    {
        $this->device = $device;
    }

    /**
     * @return RequestAgent
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * @param mixed $agent
     */
    public function setAgent($agent)
    {
        $this->agent = $agent;
    }

    /**
     * @return RequestMethod
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return RequestLanguage
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return Url
     */
    public function getRefererUrl()
    {
        return $this->refererUrl;
    }

    /**
     * @param mixed $refererUrl
     */
    public function setRefererUrl($refererUrl)
    {
        $this->refererUrl = $refererUrl;
    }

    /**
     * @return mixed
     */
    public function getGeoip()
    {
        return $this->geoip;
    }

    /**
     * @param mixed $geoip
     */
    public function setGeoip($geoip)
    {
        $this->geoip = $geoip;
    }

    /**
     * @return mixed
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * @param mixed $clientIp
     */
    public function setClientIp($clientIp)
    {
        $this->clientIp = $clientIp;
    }

    /**
     * @return mixed
     */
    public function getisRobot()
    {
        return $this->isRobot;
    }

    /**
     * @param mixed $isRobot
     */
    public function setIsRobot($isRobot)
    {
        $this->isRobot = $isRobot;
    }

    /**
     * @return mixed
     */
    public function getRequestedOn()
    {
        return $this->requestedOn;
    }

    /**
     * @param mixed $requestedOn
     */
    public function setRequestedOn($requestedOn)
    {
        $this->requestedOn = $requestedOn;
    }

}