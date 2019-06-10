<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_url_protocols")
 */
class UrlProtocol extends RailtrackerEntity implements RailtrackerEntityInterface
{
    public static $KEY = 'protocol';

    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(length=6, unique=true)
     */
    protected $protocol;

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
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param string $protocol
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @param string $url
     * @return static
     */
    public static function createFromUrl($url)
    {
        $protocol = new static;

        $protocol->setProtocol(substr(parse_url($url)['scheme'] ?? '', 0, 6));
        $protocol->setHash();

        return $protocol;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function setHash()
    {
        $this->hash = md5(implode('-', [
            $this->getProtocol(),
        ]));
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    public function setFromData($data)
    {
        $this->setProtocol($data['protocol']);
    }
}
