<?php

namespace Railroad\Railtracker\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="railtracker_geoip")
 */
class GeoIp extends RailtrackerEntity implements RailtrackerEntityInterface
{

    /* ----------------------------------------

        column names:
            latitude
            longitude
            country_code
            country_name
            region
            city
            postal_code
            ip_address
            timezone
            currency
            hash

        keys in data returned by ipapi.com:
            city
            country
            countryCode
            currency
            lat
            lon
            query
            region
            regionName
            status
            timezone
            zip

    ---------------------------------------- */

    public static $KEY = 'geoip';

    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column
     */
    protected $latitude;

    /**
     * @ORM\Column
     */
    protected $longitude;

    /**
     * @ORM\Column(name="country_code")
     */
    protected $countryCode;

    /**
     * @ORM\Column(name="country_name")
     */
    protected $countryName;

    /**
     * @ORM\Column
     */
    protected $region;

    /**
     * @ORM\Column
     */
    protected $city;

    /**
     * @ORM\Column(name="postal_code")
     */
    protected $postalCode;

    /**
     * @ORM\Column(name="ip_address")
     */
    protected $ipAddress;

    /**
     * @ORM\Column
     */
    protected $timezone;

    /**
     * @ORM\Column
     */
    protected $currency;

    /**
     * @ORM\Column
     */
    protected $hash;

    // -----------------------------------------------------------------------------------------------------------------


    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param mixed $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param mixed $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param mixed $countryCode
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
    }

    /**
     * @return mixed
     */
    public function getCountryName()
    {
        return $this->countryName;
    }

    /**
     * @param mixed $countryName
     */
    public function setCountryName($countryName)
    {
        $this->countryName = $countryName;
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param mixed $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return mixed
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param mixed $postalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ipAddress
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param mixed $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public static function generateHash($data)
    {
        return md5(implode('-', [
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['country_code'] ?? null,
            $data['country_name'] ?? null,
            $data['region'] ?? null,
            $data['city'] ?? null,
            $data['postal'] ?? null,
            $data['ip'] ?? null,
            $data['time_zone']['name'] ?? null,
            $data['currency']['name'] ?? null,
        ]));
    }

    /**
     *
     */
    public function setHash()
    {
        $this->hash = md5(implode('-', [
            $this->getLatitude(),
            $this->getLongitude(),
            $this->getCountryCode(),
            $this->getCountryName(),
            $this->getRegion(),
            $this->getCity(),
            $this->getPostalCode(),
            $this->getIpAddress(),
            $this->getTimezone(),
            $this->getCurrency(),
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
        $success = !empty($data['latitude']) && !empty($data['longitude']);

        $this->setIpAddress($data['ip']);

        if($success){
            $this->setLatitude($data['latitude']);
            $this->setLongitude($data['longitude']);
            $this->setCountryCode($data['country_code']);
            $this->setCountryName($data['country_name']);
            $this->setRegion($data['region']);
            $this->setCity($data['city']);
            $this->setPostalCode($data['postal']);
            $this->setTimezone($data['time_zone']['name']);
            $this->setCurrency($data['currency']['name']);
        }
    }

    public function allValuesAreEmpty()
    {
        return
            empty($this->getLatitude()) &&
            empty($this->getLongitude()) &&
            empty($this->getCountryCode()) &&
            empty($this->getCountryName()) &&
            empty($this->getRegion()) &&
            empty($this->getCity()) &&
            empty($this->getPostalCode()) &&
            empty($this->getIpAddress()) &&
            empty($this->getTimezone()) &&
            empty($this->getCurrency());
    }



}