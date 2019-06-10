<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_url_domains")
 */
class UrlDomain extends RailtrackerEntity implements RailtrackerEntityInterface
{
    public static $KEY = 'domain';

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param $url
     * @return static
     */
    public static function createFromUrl($url)
    {
        $domain = new static;

        $domain->setName(substr(parse_url($url)['host'] ?? '', 0, 180));
        $domain->setHash();

        return $domain;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function setHash()
    {
        $this->hash = md5(implode('-', [
            $this->getName(),
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
        $this->setName($data['name']);
    }
}
