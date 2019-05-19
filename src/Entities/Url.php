<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_urls")
 */
class Url
{
    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="UrlProtocol", cascade={"persist"})
     */
    protected $protocol;

    /**
     * @ORM\ManyToOne(targetEntity="UrlDomain", cascade={"persist"})
     */
    protected $domain;

    /**
     * @ORM\ManyToOne(targetEntity="UrlPath", cascade={"persist"})
     * @ORM\JoinColumn(name="path_id", referencedColumnName="id", nullable=true)
     */
    protected $path;

    /**
     * @ORM\ManyToOne(targetEntity="UrlQuery", cascade={"persist"})
     * @ORM\JoinColumn(name="query_id", referencedColumnName="id", nullable=true)
     */
    protected $query;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return UrlProtocol
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param UrlProtocol $protocol
     */
    public function setProtocol(UrlProtocol $protocol)
    {
        $this->protocol = $protocol;
    }

    /**
     * @return string
     */
    public function getProtocolValue()
    {
        return $this->getProtocol()->getProtocol();
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return UrlDomain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param UrlDomain $domain
     */
    public function setDomain(UrlDomain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getDomainValue()
    {
        return $this->getDomain()->getName();
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return UrlPath
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param UrlPath $path
     */
    public function setPath(UrlPath $path)
    {
        $this->path = $path;
    }

    /**
     * @return null|string
     */
    public function getPathValue()
    {
        return $this->getPath()->getPath();
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return UrlQuery
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param UrlQuery $query
     */
    public function setQuery(UrlQuery $query)
    {
        $this->query = $query;
    }

    /**
     * @return string|null
     */
    public function getQueryValue()
    {
        return $this->getQuery()->getString();
    }

    // -----------------------------------------------------------------------------------------------------------------


}