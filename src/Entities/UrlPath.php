<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_url_paths")
 */
class UrlPath
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(length=180, unique=true)
     */
    protected $path;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param string $url
     * @return static
     */
    public static function createFromUrl($url)
    {
        $pathEntitiy = new static;

        $path = parse_url($url)['path'] ?? '';
        if (!empty($path)) {
            $pathEntitiy->setPath(substr($path, 0, 180));
        }else{
            $pathEntitiy->setPath('');
        }

        return $pathEntitiy;
    }
}