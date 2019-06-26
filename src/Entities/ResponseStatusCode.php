<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_response_status_codes")
 */
class ResponseStatusCode extends RailtrackerEntity implements RailtrackerEntityInterface
{
    public static $KEY = 'status_code';

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $code;

    /**
     * @ORM\Column(name="hash", length=128, unique=true)
     */
    protected $hash;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function setHash()
    {
        $this->hash = md5(implode(['-', $this->getCode()]));
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    public function setFromData($data)
    {
        $this->setCode($data['code']);
    }

    public function allValuesAreEmpty()
    {
        return
            empty($this->getCode()) &&
            empty($this->getHash());
    }
}
