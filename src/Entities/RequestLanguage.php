<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_request_languages")
 */
class RequestLanguage
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(length=12, unique=true)
     */
    private $preference;

    /**
     * @ORM\Column(name="language_range", length=180, unique=true)
     */
    private $languageRange;

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
    public function getPreference()
    {
        return $this->preference;
    }

    /**
     * @param string $preference
     */
    public function setPreference($preference)
    {
        $this->preference = $preference;
    }

    /**
     * @return string
     */
    public function getLanguageRange()
    {
        return $this->languageRange;
    }

    /**
     * @param string $languageRange
     */
    public function setLanguageRange($languageRange)
    {
        $this->languageRange = $languageRange;
    }
}
