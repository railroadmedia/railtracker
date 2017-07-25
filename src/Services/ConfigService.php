<?php

namespace Railroad\Railtracker\Services;

class ConfigService
{
    /**
     * @var int
     */
    public static $cacheTime;

    /**
     * @var string
     */
    public static $databaseConnectionName;

    /**
     * @var string
     */
    public static $tableUrlProtocols;

    /**
     * @var string
     */
    public static $tableUrlDomains;

    /**
     * @var string
     */
    public static $tableUrlPaths;

    /**
     * @var string
     */
    public static $tableUrlQueries;

    /**
     * @var string
     */
    public static $tableUrls;

    /**
     * @var string
     */
    public static $tableRoutes;

    /**
     * @var string
     */
    public static $tableRequestMethods;

    /**
     * @var string
     */
    public static $tableRequestAgents;

    /**
     * @var string
     */
    public static $tableRequestDevices;

    /**
     * @var string
     */
    public static $tableRequestLanguages;

    /**
     * @var string
     */
    public static $tableGeoIP;

    /**
     * @var string
     */
    public static $tableRequests;

    /**
     * @var string
     */
    public static $tableResponses;

    /**
     * @var string
     */
    public static $tableResponseStatusCodes;

    /**
     * @var string
     */
    public static $tableExceptions;

    /**
     * @var string
     */
    public static $tableRequestExceptions;

    /**
     * @var string
     */
    public static $tableMediaPlaybackTypes;

    /**
     * @var string
     */
    public static $tableMediaPlaybackSessions;

    /**
     * @var array
     */
    public static $exclusionRegexPaths = [];

}