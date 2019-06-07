<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_url_queries")
 */
class UrlQuery extends RailtrackerEntity implements RailtrackerEntityInterface
{
    public static $KEY = 'query';

    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(length=840, unique=true)
     */
    protected $string;

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
    public function getString()
    {
        return $this->string;
    }

    /**
     * @param string $string
     */
    public function setString($string)
    {
        $this->string = $string;
    }

    /**
     * @param $url
     * @return static
     */
    public static function createFromUrl($url)
    {
        $queryEntity = new static;
        $queryString = parse_url($url)['query'] ?? '';

        if (!empty($queryString)) {
            $queryEntity->setString(substr($queryString, 0, 840));
        }else{
            $queryEntity->setString('');
        }

        return $queryEntity;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function setHash()
    {
        $this->hash = md5(implode('-', [
            $this->getString(),
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
        $this->setString($data['query']);
    }
}
