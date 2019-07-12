<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_routes")
 */
class Route extends RailtrackerEntity implements RailtrackerEntityInterface
{
    public static $KEY = 'route';

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=180, unique=true)
     */
    private $name;

    /**
     * @ORM\Column(length=180, unique=true)
     */
    private $action;

    /**
     * @ORM\Column(name="hash", length=128, unique=true)
     */
    protected $hash;

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
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function setHash()
    {
        $this->hash = md5(implode('-', [
            $this->getName(),
            $this->getAction(),
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
        $this->setAction($data['action']);
    }

    public function allValuesAreEmpty()
    {
        return
            empty($this->getName()) &&
            empty($this->getAction());
    }
}
