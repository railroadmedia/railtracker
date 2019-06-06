<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_request_agents")
 */
class RequestAgent
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(length=180, unique=true)
     */
    protected $name;

    /**
     * @ORM\Column(length=64, unique=true)
     */
    protected $browser;

    /**
     * @ORM\Column(name="browser_version", length=32, unique=true)
     */
    protected $browserVersion;

    /**
     * @ORM\Column(name="hash", length=128, unique=true)
     */
    protected $hash;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @param mixed $browser
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;
    }

    /**
     * @return mixed
     */
    public function getBrowserVersion()
    {
        return $this->browserVersion;
    }

    /**
     * @param mixed $browserVersion
     */
    public function setBrowserVersion($browserVersion)
    {
        $this->browserVersion = $browserVersion;
    }

    public function setHash()
    {
        $this->hash = md5(implode('-', [$this->getName(), $this->getBrowser(), $this->getBrowserVersion()]));
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }
}