<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_request_devices")
 */
class RequestDevice
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
    private $kind;

    /**
     * @ORM\Column(length=64, unique=true)
     */
    private $model;

    /**
     * @ORM\Column(length=64, unique=true)
     */
    private $platform;

    /**
     * @ORM\Column(length=16, name="platform_version", unique=true)
     */
    private $platformVersion;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isMobile;

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param mixed $kind
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param mixed $platform
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
    }

    /**
     * @return mixed
     */
    public function getPlatformVersion()
    {
        return $this->platformVersion;
    }

    /**
     * @param mixed $platformVersion
     */
    public function setPlatformVersion($platformVersion)
    {
        $this->platformVersion = $platformVersion;
    }

    /**
     * @return mixed
     */
    public function getIsMobile()
    {
        return $this->isMobile;
    }

    /**
     * @param mixed $isMobile
     */
    public function setIsMobile($isMobile)
    {
        $this->isMobile = $isMobile;
    }
}