<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_url_queries")
 */
class UrlQuery
{
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
}