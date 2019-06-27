<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_urls")
 */
class Url extends RailtrackerEntity implements RailtrackerEntityInterface
{
    public static $KEY = 'url';
    public static $REFERER_URL_KEY = 'refererUrl';

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

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return string
     */
    public function getProtocolValue()
    {
        return $this->getProtocol()
            ->getProtocol();
    }

    /**
     * @return string
     */
    public function getDomainValue()
    {
        return $this->getDomain()
            ->getName();
    }

    /**
     * @return null|string
     */
    public function getPathValue()
    {
        return $this->getPath()
            ->getPath();
    }

    /**
     * @return string|null
     */
    public function getQueryValue()
    {
        return $this->getQuery()
            ->getString();
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function setHash()
    {
        $this->hash = md5(
            implode(
                '-',
                [
                    $this->getProtocol()
                        ->getProtocol(),
                    $this->getDomain()
                        ->getName(),
                    !empty($this->getPath()) ?
                        $this->getPath()
                            ->getPath() : null,
                    !empty($this->getQuery()) ?
                        $this->getQuery()
                            ->getString() : null,
                ]
            )
        );
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param Url $data
     */
    public function setFromData($data)
    {
        $this->setProtocol($data['protocol']);
        $this->setDomain($data['domain']);
        if (!empty($data['path'])) {
            $this->setPath($data['path']);
        }
        if (!empty($data['query'])) {
            $this->setQuery($data['query']);
        }
    }

    public function allValuesAreEmpty()
    {
        return empty($this->getProtocol()) && empty($this->getDomain());
    }

}
